<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/MySqlTestDatabase.php';

use PHPUnit\Framework\TestCase;

class FinanceServiceIntegrationTest extends TestCase
{
    private MySqlTestDatabase $db;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new MySqlTestDatabase();
        $this->db->installFinanceSchema();
        $this->pdo = $this->db->pdo();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    protected function tearDown(): void
    {
        $this->db->drop();
        parent::tearDown();
    }

    public function testBalancesUseRealRowsInMysql(): void
    {
        $this->seedCompany(1, 1000);
        $this->seedWithdrawal(1, 50, 'pending');
        $this->seedWithdrawal(1, 200, 'completed');
        $this->seedNegotiation(1, 300, 'accepted');
        $this->seedNegotiation(1, 40, 'cancelled');

        $balances = FinanceService::balances($this->pdo, 1);

        $this->assertSame(1000.0, $balances['available']);
        $this->assertSame(50.0, $balances['reserved']);
        $this->assertSame(200.0, $balances['withdrawn']);
        $this->assertSame(300.0, $balances['future']);
    }

    public function testRequestWithdrawalPersistsAllEffects(): void
    {
        $this->seedCompany(1, 500);
        $withdrawalId = FinanceService::requestWithdrawal($this->pdo, 1, 7, $this->validWithdrawalData());

        $withdrawal = $this->pdo->query('SELECT * FROM withdrawals WHERE id = ' . (int) $withdrawalId)->fetch();
        $companyBalance = (float) $this->pdo->query('SELECT balance FROM companies WHERE id = 1')->fetchColumn();
        $transaction = $this->pdo->query("SELECT * FROM financial_transactions WHERE withdrawal_id = " . (int) $withdrawalId)->fetch();
        $audit = $this->pdo->query("SELECT * FROM audit_logs WHERE entity_id = " . (int) $withdrawalId . " AND action = 'WITHDRAWAL_REQUESTED'")->fetch();

        $this->assertSame('pending', $withdrawal['status']);
        $this->assertSame('400.00', $withdrawal['balance_after']);
        $this->assertSame(400.0, $companyBalance);
        $this->assertSame('withdrawal', $transaction['type']);
        $this->assertSame('pending', $transaction['status']);
        $this->assertSame('WITHDRAWAL_REQUESTED', $audit['action']);
    }

    public function testRequestWithdrawalRollsBackOnInsufficientBalance(): void
    {
        $this->seedCompany(1, 25);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('excede');

        try {
            FinanceService::requestWithdrawal($this->pdo, 1, 7, $this->validWithdrawalData(['amount' => 100]));
        } finally {
            $this->assertSame(25.0, (float) $this->pdo->query('SELECT balance FROM companies WHERE id = 1')->fetchColumn());
            $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM withdrawals')->fetchColumn());
        }
    }

    public function testRequestWithdrawalRejectsDuplicateToken(): void
    {
        $this->seedCompany(1, 500);
        $data = $this->validWithdrawalData();
        FinanceService::requestWithdrawal($this->pdo, 1, 7, $data);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('registrada');

        FinanceService::requestWithdrawal($this->pdo, 1, 7, $data);
    }

    public function testReviewApproveAndRejectChangeStatusAndBalance(): void
    {
        $this->seedCompany(1, 500);
        $withdrawalId = FinanceService::requestWithdrawal($this->pdo, 1, 7, $this->validWithdrawalData());

        $approved = FinanceService::review($this->pdo, $withdrawalId, 99, true, null);
        $this->assertSame('pending', $approved['status']);
        $this->assertSame('completed', $this->withdrawalStatus($withdrawalId));
        $this->assertSame(400.0, (float) $this->pdo->query('SELECT balance FROM companies WHERE id = 1')->fetchColumn());
        $this->assertSame('completed', $this->transactionStatus($withdrawalId));

        $withdrawalId2 = FinanceService::requestWithdrawal($this->pdo, 1, 7, $this->validWithdrawalData(['request_token' => 'token-2']));
        $rejected = FinanceService::review($this->pdo, $withdrawalId2, 99, false, 'Dados bancários divergentes');
        $this->assertSame('pending', $rejected['status']);
        $this->assertSame('rejected', $this->withdrawalStatus($withdrawalId2));
        $this->assertSame(400.0, (float) $this->pdo->query('SELECT balance FROM companies WHERE id = 1')->fetchColumn());
        $this->assertSame('failed', $this->transactionStatus($withdrawalId2));
    }

    private function seedCompany(int $id, float $balance): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO companies (id, razao_social, nome_fantasia, balance) VALUES (?, ?, ?, ?)');
        $stmt->execute([$id, 'Empresa ' . $id, 'Empresa ' . $id, $balance]);
    }

    private function seedWithdrawal(int $companyId, float $amount, string $status): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO withdrawals (company_id, amount, request_token, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$companyId, $amount, uniqid('tok', true), $status]);
    }

    private function seedNegotiation(int $sellerCompanyId, float $total, string $status): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO negotiations (seller_company_id, proposed_total, status) VALUES (?, ?, ?)');
        $stmt->execute([$sellerCompanyId, $total, $status]);
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
            'request_token' => 'token-1',
        ];
    }

    private function withdrawalStatus(int $withdrawalId): string
    {
        return (string) $this->pdo->query('SELECT status FROM withdrawals WHERE id = ' . (int) $withdrawalId)->fetchColumn();
    }

    private function transactionStatus(int $withdrawalId): string
    {
        return (string) $this->pdo->query('SELECT status FROM financial_transactions WHERE withdrawal_id = ' . (int) $withdrawalId)->fetchColumn();
    }
}
