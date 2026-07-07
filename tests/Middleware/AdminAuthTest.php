<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AdminAuthTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testAdminPossuiTodasPermissoesCriticas(): void
    {
        $_SESSION['user'] = ['role' => 'admin'];

        $this->assertTrue(AdminAuth::can('view_financial'));
        $this->assertTrue(AdminAuth::can('view_settings'));
        $this->assertTrue(AdminAuth::can('company_approve'));
        $this->assertTrue(AdminAuth::can('company_suspend'));
        $this->assertTrue(AdminAuth::isAdmin());
        $this->assertFalse(AdminAuth::isStaff());
    }

    public function testStaffPossuiPermissoesRestritas(): void
    {
        $_SESSION['user'] = ['role' => 'staff'];

        $this->assertTrue(AdminAuth::can('view_reports'));
        $this->assertTrue(AdminAuth::can('listing_approve'));
        $this->assertFalse(AdminAuth::can('view_financial'));
        $this->assertFalse(AdminAuth::can('view_settings'));
        $this->assertFalse(AdminAuth::can('company_suspend'));
        $this->assertFalse(AdminAuth::isAdmin());
        $this->assertTrue(AdminAuth::isStaff());
    }

    public function testRoleAusenteNaoTemPermissoes(): void
    {
        $_SESSION['user'] = [];

        $this->assertFalse(AdminAuth::can('view_financial'));
        $this->assertSame([], AdminAuth::user());
    }
}
