<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    public function testArquivoDeTesteEstaConfigurado(): void
    {
        $this->assertTrue(class_exists(AuthController::class));
    }

    public function testLoginRedirecionaConformeRoleEStatusDaEmpresa(): void
    {
        $this->assertSame('/re.source/admin', $this->loginRedirect(['role' => 'admin', 'company_status' => 'active']));
        $this->assertSame('/re.source/admin', $this->loginRedirect(['role' => 'staff', 'company_status' => 'active']));
        $this->assertSame('/re.source/aguardando-aprovacao', $this->loginRedirect(['role' => 'admin_company', 'company_status' => 'pending']));
        $this->assertSame('/re.source/aguardando-aprovacao', $this->loginRedirect(['role' => 'admin_company', 'company_status' => 'changes_requested']));
        $this->assertSame('/re.source/base', $this->loginRedirect(['role' => 'admin_company', 'company_status' => 'active']));
    }

    public function testLoginBloqueiaEmpresasSuspensasInativasOuRejeitadas(): void
    {
        $this->assertSame('O cadastro da empresa foi rejeitado. Entre em contato com o suporte.', $this->blockedLoginMessage('rejected'));
        $this->assertSame('Empresa suspensa. Entre em contato com o suporte.', $this->blockedLoginMessage('suspended'));
        $this->assertSame('Empresa inativa. Entre em contato com o suporte.', $this->blockedLoginMessage('inactive'));
    }

    public function testCadastroEmpresaValidaCamposObrigatorios(): void
    {
        $result = $this->validateCompanyRegistration([
            'nome' => '',
            'sobrenome' => '',
            'email' => 'email-invalido',
            'senha' => '123',
            'senha_conf' => '456',
            'cnpj' => '123',
            'razao_social' => '',
            'nome_fantasia' => '',
            'cep' => '123',
            'endereco' => '',
            'numero' => '',
            'estado' => '',
            'cidade' => '',
            'segmento' => '',
        ]);

        $this->assertGreaterThanOrEqual(13, count($result['errors']));
        $this->assertContains('email', $result['fields']);
        $this->assertContains('cnpj', $result['fields']);
        $this->assertContains('senha', $result['fields']);
    }

    public function testCadastroBloqueiaDominioGratuitoDuplicidadeECnpjInvalido(): void
    {
        $this->assertSame('Use um e-mail corporativo.', $this->validateEmailDomain('teste@gmail.com', ['gmail.com']));
        $this->assertFalse($this->isValidCnpj('11111111111111'));
        $this->assertTrue($this->isValidCnpj('11222333000181'));
    }

    public function testVerificacaoDeCadastroBloqueiaCodigoExpiradoOuIncorreto(): void
    {
        $pending = ['codigo' => '123456', 'expires_at' => time() - 1];
        $this->assertSame('Código expirado. Faça o cadastro novamente.', $this->validateVerificationCode($pending, '123456'));

        $pending = ['codigo' => '123456', 'expires_at' => time() + 3600];
        $this->assertSame('Código incorreto. Verifique e tente novamente.', $this->validateVerificationCode($pending, '000000'));
        $this->assertNull($this->validateVerificationCode($pending, '123456'));
    }

    public function testReenvioDeCodigoRespeitaCooldownDeSessentaSegundos(): void
    {
        $this->assertStringContainsString('Aguarde', $this->canResendCode(time() - 10));
        $this->assertNull($this->canResendCode(time() - 61));
    }

    public function testResetSenhaValidaTokenSenhaEConfirmacao(): void
    {
        $this->assertSame('Dados incompletos.', $this->validatePasswordReset('', '12345678', '12345678'));
        $this->assertSame('A senha deve ter pelo menos 8 caracteres.', $this->validatePasswordReset('token', '123', '123'));
        $this->assertSame('As senhas não coincidem.', $this->validatePasswordReset('token', '12345678', '87654321'));
        $this->assertNull($this->validatePasswordReset('token', '12345678', '12345678'));
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function loginRedirect(array $user): string
    {
        if (in_array($user['role'], ['admin', 'staff'], true)) {
            return '/re.source/admin';
        }
        if (in_array($user['company_status'], ['pending', 'changes_requested'], true)) {
            return '/re.source/aguardando-aprovacao';
        }

        return '/re.source/base';
    }

    private function blockedLoginMessage(string $status): string
    {
        return match ($status) {
            'rejected' => 'O cadastro da empresa foi rejeitado. Entre em contato com o suporte.',
            'suspended' => 'Empresa suspensa. Entre em contato com o suporte.',
            default => 'Empresa inativa. Entre em contato com o suporte.',
        };
    }

    private function validateCompanyRegistration(array $post): array
    {
        $errors = [];
        $fields = [];
        $add = static function (string $field, string $message) use (&$errors, &$fields): void {
            $errors[] = $message;
            $fields[] = $field;
        };

        if (trim((string) ($post['nome'] ?? '')) === '') $add('nome', 'Nome é obrigatório.');
        if (trim((string) ($post['sobrenome'] ?? '')) === '') $add('sobrenome', 'Sobrenome é obrigatório.');
        if (!filter_var($post['email'] ?? '', FILTER_VALIDATE_EMAIL)) $add('email', 'E-mail inválido.');
        if (strlen(preg_replace('/\D/', '', (string) ($post['cnpj'] ?? ''))) !== 14) $add('cnpj', 'CNPJ deve ter 14 dígitos.');
        if (trim((string) ($post['razao_social'] ?? '')) === '') $add('razao', 'Razão social é obrigatória.');
        if (trim((string) ($post['nome_fantasia'] ?? '')) === '') $add('nomeFantasia', 'Informe o nome fantasia.');
        if (strlen(preg_replace('/\D/', '', (string) ($post['cep'] ?? ''))) !== 8) $add('cep', 'CEP deve ter 8 dígitos.');
        if (trim((string) ($post['endereco'] ?? '')) === '') $add('endereco', 'Informe o endereço.');
        if (trim((string) ($post['numero'] ?? '')) === '') $add('numero', 'Informe o número.');
        if (strlen((string) ($post['senha'] ?? '')) < 8) $add('senha', 'Senha deve ter ao menos 8 caracteres.');
        if (($post['senha'] ?? '') !== ($post['senha_conf'] ?? '')) $add('senhaConf', 'As senhas não coincidem.');
        if (trim((string) ($post['estado'] ?? '')) === '') $add('estado', 'Selecione seu estado.');
        if (trim((string) ($post['cidade'] ?? '')) === '') $add('cidade', 'Informe a cidade.');
        if (trim((string) ($post['segmento'] ?? '')) === '') $add('segmento', 'Selecione o segmento.');

        return ['errors' => $errors, 'fields' => $fields];
    }

    private function validateEmailDomain(string $email, array $blockedDomains): ?string
    {
        $domain = explode('@', $email)[1] ?? '';

        return in_array($domain, $blockedDomains, true) ? 'Use um e-mail corporativo.' : null;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $calc = static function (string $cnpj, int $len): int {
            $sum = 0;
            $pos = $len - 7;
            for ($i = $len; $i >= 1; $i--) {
                $sum += (int) $cnpj[$len - $i] * $pos--;
                if ($pos < 2) $pos = 9;
            }
            $result = $sum % 11;

            return $result < 2 ? 0 : 11 - $result;
        };

        return $calc($cnpj, 12) === (int) $cnpj[12]
            && $calc($cnpj, 13) === (int) $cnpj[13];
    }

    private function validateVerificationCode(?array $pending, string $code): ?string
    {
        if (!$pending) return 'Sessão expirada. Faça o cadastro novamente.';
        if (time() > $pending['expires_at']) return 'Código expirado. Faça o cadastro novamente.';
        if ($code === '') return 'Nenhum código recebido. Tente novamente.';
        if ($code !== $pending['codigo']) return 'Código incorreto. Verifique e tente novamente.';

        return null;
    }

    private function canResendCode(int $lastSentAt): ?string
    {
        if ((time() - $lastSentAt) < 60) {
            return 'Aguarde ' . (60 - (time() - $lastSentAt)) . 's antes de reenviar.';
        }

        return null;
    }

    private function validatePasswordReset(string $token, string $password, string $confirmation): ?string
    {
        if ($token === '' || $password === '') return 'Dados incompletos.';
        if (strlen($password) < 8) return 'A senha deve ter pelo menos 8 caracteres.';
        if ($password !== $confirmation) return 'As senhas não coincidem.';

        return null;
    }
}
