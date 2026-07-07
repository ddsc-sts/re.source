<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HomeControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(HomeController::class));
    }

    public function testIndexUsaViewHomeIndex(): void
    {
        $this->assertStringEndsWith('app/Views/home/index.php', str_replace('\\', '/', $this->homeIndexViewPath()));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function homeIndexViewPath(): string
    {
        return dirname(__DIR__, 2) . '/app/Views/home/index.php';
    }
}
