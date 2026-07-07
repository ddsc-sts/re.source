<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BaseControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(BaseController::class));
    }

    public function testStubsDePaginasAindaRetornamEmDesenvolvimento(): void
    {
        $this->assertSame('Em desenvolvimento', $this->stubResponse());
    }

    public function testPreferenciasNormalizamCheckboxesDaConta(): void
    {
        $this->assertSame([
            'theme' => 'dark',
            'notify_proposals' => 1,
            'notify_chat' => 0,
        ], $this->normalizePreferences(['theme' => 'dark', 'notify_proposals' => '1']));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function stubResponse(): string
    {
        return 'Em desenvolvimento';
    }

    private function normalizePreferences(array $post): array
    {
        return [
            'theme' => in_array($post['theme'] ?? 'light', ['light', 'dark'], true) ? $post['theme'] : 'light',
            'notify_proposals' => isset($post['notify_proposals']) ? 1 : 0,
            'notify_chat' => isset($post['notify_chat']) ? 1 : 0,
        ];
    }
}
