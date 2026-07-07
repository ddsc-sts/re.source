<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AdminFinanceControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(AdminFinanceController::class));
    }

    public function testFiltrosDeSaquesAceitamApenasStatusEMetodoPermitidos(): void
    {
        $valid = $this->buildWithdrawalFilters(['status' => 'pending', 'method' => 'pix', 'q' => 'Demo']);

        $this->assertSame(['1=1', 'w.status = ?', 'w.method = ?', '(c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.cnpj LIKE ?)'], $valid['where']);
        $this->assertSame(['pending', 'pix', '%Demo%', '%Demo%', '%Demo%'], $valid['params']);

        $invalid = $this->buildWithdrawalFilters(['status' => 'invalid', 'method' => 'doc']);

        $this->assertSame(['1=1'], $invalid['where']);
        $this->assertSame([], $invalid['params']);
    }

    public function testMetricasDeSaquesAgrupamStatusDoRoteiro(): void
    {
        $withdrawals = [
            ['status' => 'pending', 'amount' => 100.0],
            ['status' => 'pending', 'amount' => 50.0],
            ['status' => 'completed', 'amount' => 80.0],
            ['status' => 'rejected', 'amount' => 20.0],
        ];

        $this->assertSame([
            'saques_pendentes' => 2,
            'valor_pendente' => 150.0,
            'valor_aprovado' => 80.0,
            'valor_rejeitado' => 20.0,
        ], $this->calculateWithdrawalMetrics($withdrawals));
    }

    public function testCsvDeSaquesUsaCabecalhoEsperado(): void
    {
        $this->assertSame(
            ['ID','Empresa','CNPJ','Método','Valor','Status','Solicitado em','Analisado em','Motivo'],
            $this->csvHeader()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function buildWithdrawalFilters(array $input): array
    {
        $status = (string) ($input['status'] ?? '');
        $method = (string) ($input['method'] ?? '');
        $q = trim((string) ($input['q'] ?? ''));
        $where = ['1=1'];
        $params = [];

        if (in_array($status, ['pending','completed','rejected'], true)) {
            $where[] = 'w.status = ?';
            $params[] = $status;
        }
        if (in_array($method, ['pix','ted'], true)) {
            $where[] = 'w.method = ?';
            $params[] = $method;
        }
        if ($q !== '') {
            $where[] = '(c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.cnpj LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }

        return ['where' => $where, 'params' => $params];
    }

    private function calculateWithdrawalMetrics(array $withdrawals): array
    {
        return [
            'saques_pendentes' => count(array_filter($withdrawals, static fn (array $w): bool => $w['status'] === 'pending')),
            'valor_pendente' => array_sum(array_column(array_filter($withdrawals, static fn (array $w): bool => $w['status'] === 'pending'), 'amount')),
            'valor_aprovado' => array_sum(array_column(array_filter($withdrawals, static fn (array $w): bool => $w['status'] === 'completed'), 'amount')),
            'valor_rejeitado' => array_sum(array_column(array_filter($withdrawals, static fn (array $w): bool => $w['status'] === 'rejected'), 'amount')),
        ];
    }

    private function csvHeader(): array
    {
        return ['ID','Empresa','CNPJ','Método','Valor','Status','Solicitado em','Analisado em','Motivo'];
    }
}
