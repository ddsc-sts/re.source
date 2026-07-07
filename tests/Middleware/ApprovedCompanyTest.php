<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ApprovedCompanyTest extends TestCase
{
    public function testStatusPendingEChangesRequestedRedirecionamParaAguardandoAprovacao(): void
    {
        $this->assertSame('/aguardando-aprovacao', $this->simulateApprovedCompany('pending')['redirect']);
        $this->assertStringContainsString('aprovação', $this->simulateApprovedCompany('pending')['flash']);
        $this->assertSame('/aguardando-aprovacao', $this->simulateApprovedCompany('changes_requested')['redirect']);
        $this->assertStringContainsString('Revise', $this->simulateApprovedCompany('changes_requested')['flash']);
    }

    public function testStatusSuspendedInactiveRejectedDerrubamSessao(): void
    {
        foreach (['suspended', 'inactive', 'rejected'] as $status) {
            $result = $this->simulateApprovedCompany($status);

            $this->assertSame('/login', $result['redirect']);
            $this->assertTrue($result['destroy_session']);
        }
    }

    public function testStatusActiveLiberaAcessoEAtualizaSessao(): void
    {
        $result = $this->simulateApprovedCompany('active');

        $this->assertNull($result['redirect']);
        $this->assertSame('active', $result['session_status']);
        $this->assertFalse($result['destroy_session']);
    }

    private function simulateApprovedCompany(?string $status): array
    {
        if ($status === null) {
            return ['redirect' => '/login', 'flash' => 'Sua sessão não é mais válida.', 'destroy_session' => true];
        }
        if (in_array($status, ['pending', 'changes_requested'], true)) {
            return [
                'redirect' => '/aguardando-aprovacao',
                'flash' => $status === 'changes_requested'
                    ? 'Revise os dados solicitados e reenvie o cadastro para análise.'
                    : 'Este recurso será liberado após a aprovação da empresa.',
                'destroy_session' => false,
            ];
        }
        if ($status !== 'active') {
            return ['redirect' => '/login', 'flash' => 'Empresa suspensa ou inativa.', 'destroy_session' => true];
        }

        return ['redirect' => null, 'flash' => null, 'destroy_session' => false, 'session_status' => 'active'];
    }
}
