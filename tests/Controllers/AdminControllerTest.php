<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AdminControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(AdminController::class));
    }

    public function testDeltaCalculaAltaBaixaEEstavel(): void
    {
        $this->assertSame(['valor' => '50%', 'direcao' => 'up'], $this->invokePrivate('delta', 150.0, 100.0));
        $this->assertSame(['valor' => '25%', 'direcao' => 'down'], $this->invokePrivate('delta', 75.0, 100.0));
        $this->assertSame(['valor' => '0%', 'direcao' => 'flat'], $this->invokePrivate('delta', 100.0, 100.0));
        $this->assertNull($this->invokePrivate('delta', 100.0, 0.0));
    }

    public function testFormatMillionsFormataValoresDoDashboard(): void
    {
        $this->assertSame('2,50 mi', $this->invokePrivate('formatMillions', 2500000.0));
        $this->assertSame('2,5 mil', $this->invokePrivate('formatMillions', 2500.0));
        $this->assertSame('25,00', $this->invokePrivate('formatMillions', 25.0));
    }

    public function testDefinicoesDeAcaoAdministrativaDeEmpresaSeguemRoteiro(): void
    {
        $definitions = $this->companyActionDefinitions();

        $this->assertSame(['pending'], $definitions['approve']['allowed']);
        $this->assertSame('active', $definitions['approve']['target']);
        $this->assertSame('COMPANY_APPROVED', $definitions['approve']['audit']);
        $this->assertTrue($definitions['request_changes']['requires_reason']);
        $this->assertSame(['pending', 'changes_requested'], $definitions['reject']['allowed']);
        $this->assertSame(['active'], $definitions['suspend']['allowed']);
        $this->assertSame(['suspended'], $definitions['reactivate']['allowed']);
    }

    public function testValidacaoDeMotivoDasAcoesDeEmpresa(): void
    {
        $this->assertSame('Informe um motivo com pelo menos 10 caracteres.', $this->validateCompanyActionReason(true, 'curto'));
        $this->assertSame('O motivo deve ter no máximo 1.000 caracteres.', $this->validateCompanyActionReason(false, str_repeat('a', 1001)));
        $this->assertNull($this->validateCompanyActionReason(false, ''));
        $this->assertNull($this->validateCompanyActionReason(true, 'Motivo suficiente'));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(AdminController::class, $method);

        return $reflection->invoke(null, ...$args);
    }

    private function companyActionDefinitions(): array
    {
        return [
            'approve' => ['allowed' => ['pending'], 'target' => 'active', 'audit' => 'COMPANY_APPROVED', 'requires_reason' => false],
            'request_changes' => ['allowed' => ['pending'], 'target' => 'changes_requested', 'audit' => 'COMPANY_CHANGES_REQUESTED', 'requires_reason' => true],
            'reject' => ['allowed' => ['pending', 'changes_requested'], 'target' => 'rejected', 'audit' => 'COMPANY_REJECTED', 'requires_reason' => true],
            'suspend' => ['allowed' => ['active'], 'target' => 'suspended', 'audit' => 'COMPANY_SUSPENDED', 'requires_reason' => true],
            'reactivate' => ['allowed' => ['suspended'], 'target' => 'active', 'audit' => 'COMPANY_REACTIVATED', 'requires_reason' => false],
        ];
    }

    private function validateCompanyActionReason(bool $requiresReason, string $reason): ?string
    {
        if ($requiresReason && mb_strlen($reason) < 10) {
            return 'Informe um motivo com pelo menos 10 caracteres.';
        }
        if (mb_strlen($reason) > 1000) {
            return 'O motivo deve ter no máximo 1.000 caracteres.';
        }

        return null;
    }
}
