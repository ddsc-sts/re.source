<?php

use PHPUnit\Framework\TestCase;

class FinanceServiceTest extends TestCase
{
    public function testBalancesRetornaValoresCorretos(): void
    {
        // Arrange — um "dublê" de PDOStatement para cada consulta que o método faz
        $stmtBalance = $this->createMock(PDOStatement::class);
        $stmtBalance->method('execute')->willReturn(true);
        $stmtBalance->method('fetchColumn')->willReturn('1000.00');

        $stmtWithdrawals = $this->createMock(PDOStatement::class);
        $stmtWithdrawals->method('execute')->willReturn(true);
        $stmtWithdrawals->method('fetch')->willReturn([
            'reserved' => '50.00',
            'withdrawn' => '200.00',
        ]);

        $stmtNegotiations = $this->createMock(PDOStatement::class);
        $stmtNegotiations->method('execute')->willReturn(true);
        $stmtNegotiations->method('fetchColumn')->willReturn('300.00');

        // O PDO "fake" devolve um stmt diferente a cada prepare(), NA ORDEM em que o código chama
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $stmtBalance,
            $stmtWithdrawals,
            $stmtNegotiations
        );

        // Act
        $result = FinanceService::balances($pdo, 1);

        // Assert
        $this->assertEquals(1000.00, $result['available']);
        $this->assertEquals(50.00, $result['reserved']);
        $this->assertEquals(200.00, $result['withdrawn']);
        $this->assertEquals(300.00, $result['future']);
    }

    public function testRequestWithdrawalCriaSaqueDebitaSaldoERegistraAuditoria(): void
    {
        $executions = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);
        $pdo->expects($this->never())->method('rollBack');
        $pdo->method('lastInsertId')->willReturn('99');
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $this->stmt('500.00', 'fetchColumn'),
            $this->stmt(false, 'fetchColumn'),
            $this->capturingStmt($executions, 'insert_withdrawal'),
            $this->capturingStmt($executions, 'update_balance'),
            $this->capturingStmt($executions, 'insert_transaction'),
            $this->capturingStmt($executions, 'audit')
        );

        $id = FinanceService::requestWithdrawal($pdo, 1, 7, $this->validWithdrawalData(['amount' => 100.0]));

        $this->assertSame(99, $id);
        $this->assertSame(400.0, $executions['insert_withdrawal'][0][16]);
        $this->assertSame([400.0, 1], $executions['update_balance'][0]);
        $this->assertSame([1, 99, 100.0], $executions['insert_transaction'][0]);
        $this->assertSame('WITHDRAWAL_REQUESTED', $executions['audit'][0][2]);
    }

    public function testRequestWithdrawalBloqueiaValorMaiorQueSaldoERollback(): void
    {
        $pdo = $this->pdoExpectingRollback();
        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt('50.00', 'fetchColumn'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('saldo');

        FinanceService::requestWithdrawal($pdo, 1, 7, $this->validWithdrawalData(['amount' => 100.0]));
    }

    public function testRequestWithdrawalBloqueiaTokenRepetidoERollback(): void
    {
        $pdo = $this->pdoExpectingRollback();
        $pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $this->stmt('500.00', 'fetchColumn'),
                $this->stmt('12', 'fetchColumn')
            );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('registrada');

        FinanceService::requestWithdrawal($pdo, 1, 7, $this->validWithdrawalData());
    }

    public function testRequestWithdrawalBloqueiaEmpresaInexistenteERollback(): void
    {
        $pdo = $this->pdoExpectingRollback();
        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt(false, 'fetchColumn'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Empresa');

        FinanceService::requestWithdrawal($pdo, 1, 7, $this->validWithdrawalData());
    }

    public function testReviewAprovaSaquePendente(): void
    {
        $executions = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $this->stmt($this->pendingWithdrawal(), 'fetch'),
            $this->capturingStmt($executions, 'update_withdrawal'),
            $this->capturingStmt($executions, 'update_transaction'),
            $this->capturingStmt($executions, 'notification'),
            $this->capturingStmt($executions, 'audit')
        );

        $withdrawal = FinanceService::review($pdo, 99, 1, true);

        $this->assertSame('pending', $withdrawal['status']);
        $this->assertSame([1, null, 99], $executions['update_withdrawal'][0]);
        $this->assertSame([99], $executions['update_transaction'][0]);
        $this->assertSame('withdrawal_approved', $executions['notification'][0][1]);
        $this->assertSame('WITHDRAWAL_APPROVED', $executions['audit'][0][2]);
    }

    public function testReviewRecusaSaqueEDevolveSaldo(): void
    {
        $executions = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $this->stmt($this->pendingWithdrawal(), 'fetch'),
            $this->stmt('400.00', 'fetchColumn'),
            $this->capturingStmt($executions, 'refund_balance'),
            $this->capturingStmt($executions, 'update_withdrawal'),
            $this->capturingStmt($executions, 'update_transaction'),
            $this->capturingStmt($executions, 'notification'),
            $this->capturingStmt($executions, 'audit')
        );

        FinanceService::review($pdo, 99, 1, false, 'Dados divergentes');

        $this->assertSame([100.0, 1], $executions['refund_balance'][0]);
        $this->assertSame([1, 'Dados divergentes', 99], $executions['update_withdrawal'][0]);
        $this->assertSame([99], $executions['update_transaction'][0]);
        $this->assertSame('withdrawal_rejected', $executions['notification'][0][1]);
        $this->assertSame('WITHDRAWAL_REJECTED', $executions['audit'][0][2]);
    }

    public function testReviewBloqueiaSaqueInexistenteOuJaAnalisado(): void
    {
        $pdo = $this->pdoExpectingRollback();
        $pdo->expects($this->once())->method('prepare')->willReturn($this->stmt(false, 'fetch'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('encontrada');

        FinanceService::review($pdo, 99, 1, true);
    }

    public function testReviewBloqueiaRecusaSemMotivoMinimo(): void
    {
        $pdo = $this->pdoExpectingRollback();
        $pdo->expects($this->once())->method('prepare')->willReturn($this->stmt($this->pendingWithdrawal(), 'fetch'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('motivo');

        FinanceService::review($pdo, 99, 1, false, 'abc');
    }

    private function validWithdrawalData(array $override = []): array
    {
        return $override + [
            'amount' => 100.0,
            'method' => 'pix',
            'pix_key' => '11999999999',
            'pix_key_type' => 'phone',
            'bank_code' => null,
            'bank_name' => null,
            'agency' => null,
            'account_number' => null,
            'account_digit' => null,
            'account_type' => null,
            'holder_name' => 'Empresa Demo',
            'holder_document' => '12345678901',
            'request_note' => null,
            'request_token' => 'token-unico',
        ];
    }

    private function pendingWithdrawal(): array
    {
        return [
            'id' => 99,
            'company_id' => 1,
            'amount' => 100.0,
            'status' => 'pending',
            'nome_fantasia' => 'Demo',
            'razao_social' => 'Demo LTDA',
        ];
    }

    private function pdoExpectingRollback(): PDO
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);
        $pdo->expects($this->never())->method('commit');

        return $pdo;
    }

    private function stmt(mixed $result, string $method): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method($method)->willReturn($result);

        return $stmt;
    }

    private function capturingStmt(array &$executions, string $key): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturnCallback(function (array $params) use (&$executions, $key): bool {
            $executions[$key][] = $params;

            return true;
        });

        return $stmt;
    }
}
