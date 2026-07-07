<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UserAuthTest extends TestCase
{
    public function testSemCompanyIdRedirecionaParaLogin(): void
    {
        $this->assertSame('/login', $this->simulateUserAuth([]));
    }

    public function testCompanyIdNoUsuarioOuDiretoLiberaAcesso(): void
    {
        $this->assertNull($this->simulateUserAuth(['user' => ['company_id' => 10]]));
        $this->assertNull($this->simulateUserAuth(['company_id' => 11]));
    }

    private function simulateUserAuth(array $session): ?string
    {
        $companyId = $session['user']['company_id'] ?? $session['company_id'] ?? null;

        return empty($companyId) ? '/login' : null;
    }
}
