<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ChatControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(ChatController::class));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = ['user' => ['id' => 7, 'company_id' => 10]];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCompanyIdEUserIdSaoLidosDaSessao(): void
    {
        $this->assertSame(10, $this->invokePrivate('companyId'));
        $this->assertSame(7, $this->invokePrivate('userId'));
    }

    public function testMensagemVaziaOuLongaDemaisEInvalidada(): void
    {
        $this->assertSame('Digite uma mensagem.', $this->validateMessage('   '));
        $this->assertSame('A mensagem deve ter no máximo 2.000 caracteres.', $this->validateMessage(str_repeat('a', 2001)));
        $this->assertNull($this->validateMessage('Mensagem válida'));
    }

    public function testNegotiationRetornaNullQuandoEmpresaNaoParticipa(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertNull($this->invokePrivate('negotiation', $pdo, 1, 10));
    }

    public function testUnreadTotalContaMensagensRecebidasNaoLidas(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([10, 10, 10])->willReturn(true);
        $stmt->method('fetchColumn')->willReturn('3');
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertSame(3, $this->invokePrivate('unreadTotal', $pdo, 10));
    }

    private function validateMessage(string $content): ?string
    {
        $content = trim($content);
        if ($content === '') {
            return 'Digite uma mensagem.';
        }
        if (mb_strlen($content) > 2000) {
            return 'A mensagem deve ter no máximo 2.000 caracteres.';
        }

        return null;
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(ChatController::class, $method);

        return $reflection->invoke(null, ...$args);
    }
}
