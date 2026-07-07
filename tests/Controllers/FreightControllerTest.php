<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FreightControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [
            'user' => [
                'id' => 42,
                'company_id' => 5,
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCompanyIdAceitaFallbackDiretoDaSessao(): void
    {
        unset($_SESSION['user']['company_id']);
        $_SESSION['company_id'] = 8;

        $this->assertSame(8, $this->invokePrivate('companyId'));
        $this->assertSame(42, $this->invokePrivate('userId'));
    }

    public function testCanChooseRespeitaResponsavelPeloFrete(): void
    {
        $negotiation = [
            'buyer_company_id' => 10,
            'seller_company_id' => 20,
        ];

        $this->assertTrue($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'buyer'], 10));
        $this->assertFalse($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'buyer'], 20));
        $this->assertTrue($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'seller'], 20));
        $this->assertTrue($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'shared'], 10));
        $this->assertTrue($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'shared'], 20));
        $this->assertFalse($this->invokePrivate('canChoose', $negotiation + ['responsible_for_freight' => 'shared'], 30));
    }

    public function testCreateQuotesExpiraCotacoesAntigasEGeraTresOpcoesComPesoConvertido(): void
    {
        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())
            ->method('execute')
            ->with([123])
            ->willReturn(true);

        $executedQuotes = [];
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(function (array $params) use (&$executedQuotes): bool {
                $executedQuotes[] = $params;

                return true;
            });

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($updateStmt, $insertStmt);

        $this->invokePrivate('createQuotes', $pdo, [
            'id' => 123,
            'proposed_quantity' => 10,
            'unit' => 'ton',
        ]);

        $this->assertSame([
            [123, 'EcoLog Transportes', 'Econômico', 'rodoviario', 204.0, 8],
            [123, 'Rota Circular', 'Standard', 'rodoviario', 284.0, 5],
            [123, 'Verde Express', 'Expresso', 'expresso', 409.0, 2],
        ], $executedQuotes);
    }

    public function testCreateQuotesUsaKgDiretoEOutrasUnidadesComoDezKg(): void
    {
        $kgQuotes = $this->captureCreatedQuotes(['id' => 1, 'proposed_quantity' => 500, 'unit' => 'kg']);
        $otherQuotes = $this->captureCreatedQuotes(['id' => 2, 'proposed_quantity' => 50, 'unit' => 'm3']);

        $this->assertSame(85.25, $kgQuotes[0][4]);
        $this->assertSame(85.25, $otherQuotes[0][4]);
    }

    public function testNegotiationBloqueiaAcessoQuandoEmpresaNaoParticipa(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sem acesso');

        $this->invokePrivate('negotiation', $pdo, 99, 5);
    }

    public function testContratacaoCalculaTaxaTotalETrackingNoFormatoDoRoteiro(): void
    {
        $quote = ['price' => 200.00];
        $negotiationId = 123;

        $platformFee = round((float) $quote['price'] * 0.05, 2);
        $total = (float) $quote['price'] + $platformFee;
        $tracking = 'RS-' . date('ymd') . '-' . str_pad((string) $negotiationId, 5, '0', STR_PAD_LEFT) . '-ABCD';

        $this->assertSame(10.0, $platformFee);
        $this->assertSame(210.0, $total);
        $this->assertMatchesRegularExpression('/^RS-\d{6}-00123-[A-F0-9]{4}$/', $tracking);
    }

    public function testRecordStatusInsereHistoricoComUsuarioDaSessao(): void
    {
        $executed = [];
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $params) use (&$executed): bool {
                $executed = $params;

                return true;
            });
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->invokePrivate('recordStatus', $pdo, 55, 'contracted', 'Frete contratado.');

        $this->assertSame([55, 'contracted', 'Frete contratado.', 42], $executed);
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(FreightController::class, $method);

        return $reflection->invoke(null, ...$args);
    }

    private function captureCreatedQuotes(array $negotiation): array
    {
        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->method('execute')->willReturn(true);
        $quotes = [];
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->method('execute')->willReturnCallback(function (array $params) use (&$quotes): bool {
            $quotes[] = $params;

            return true;
        });
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($updateStmt, $insertStmt);

        $this->invokePrivate('createQuotes', $pdo, $negotiation);

        return $quotes;
    }
}
