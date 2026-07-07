<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $_SESSION['_csrf_token'] ??= 'csrf-token-test';

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('view')) {
    function view($viewName, $data = []): void
    {
        $GLOBALS['__last_view'] = [
            'name' => $viewName,
            'data' => $data,
        ];
    }
}

class EstatisticasControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [
            'user' => [
                'id' => 77,
                'company_id' => 10,
            ],
        ];
        unset($GLOBALS['__last_view']);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($GLOBALS['__last_view']);
        parent::tearDown();
    }

    public function testCompanyIdEUserIdSaoLidosDaSessao(): void
    {
        $this->assertSame(10, $this->invokePrivate('companyId'));
        $this->assertSame(77, $this->invokePrivate('userId'));
    }

    public function testSaqueGeraTokenUnicoEEnviaDadosParaView(): void
    {
        $companyStmt = $this->statementMock(
            ['nome_fantasia' => 'Re Source Demo', 'razao_social' => 'Re Source LTDA', 'cnpj' => '12345678000199']
        );
        $balanceStmt = $this->statementMock('1500.75', 'fetchColumn');
        $withdrawalsBalanceStmt = $this->statementMock(['reserved' => '100.00', 'withdrawn' => '250.00']);
        $futureStmt = $this->statementMock('300.00', 'fetchColumn');
        $recentWithdrawalsStmt = $this->statementMock([
            ['amount' => '100.00', 'method' => 'pix', 'status' => 'pending', 'created_at' => '2026-07-07 10:00:00'],
        ], 'fetchAll');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->exactly(5))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $companyStmt,
                $balanceStmt,
                $withdrawalsBalanceStmt,
                $futureStmt,
                $recentWithdrawalsStmt
            );

        $GLOBALS['pdo'] = $pdo;

        EstatisticasController::saque();

        $this->assertArrayHasKey('withdrawal_request_token', $_SESSION);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $_SESSION['withdrawal_request_token']);
        $this->assertSame('estatisticas/saque', $GLOBALS['__last_view']['name']);
        $this->assertSame(1500.75, $GLOBALS['__last_view']['data']['availableBalance']);
        $this->assertSame('csrf-token-test', $GLOBALS['__last_view']['data']['csrfToken']);
        $this->assertSame($_SESSION['withdrawal_request_token'], $GLOBALS['__last_view']['data']['requestToken']);
    }

    public function testValidacaoDeSaqueBloqueiaValorMetodoTitularAceiteEToken(): void
    {
        $_SESSION['withdrawal_request_token'] = 'token-correto';
        $errors = $this->validateWithdrawalForm([
            'valor_saque' => '5,00',
            'method' => 'boleto',
            'titular_nome' => 'AB',
            'titular_documento' => '123',
            'request_token' => 'token-errado',
        ]);

        $this->assertStringContainsString('mínimo', implode(' ', $errors));
        $this->assertStringContainsString('PIX ou TED', implode(' ', $errors));
        $this->assertStringContainsString('titular', implode(' ', $errors));
        $this->assertStringContainsString('termos', implode(' ', $errors));
        $this->assertStringContainsString('duplicada', implode(' ', $errors));
    }

    public function testValidacaoDeSaquePixExigeChaveValidaEEmailValido(): void
    {
        $_SESSION['withdrawal_request_token'] = 'token-correto';

        $errors = $this->validateWithdrawalForm($this->validWithdrawalPost([
            'pix_key_type' => 'email',
            'chave_pix' => 'email-invalido',
        ]));

        $this->assertSame(['Informe um e-mail válido.'], $errors);
    }

    public function testValidacaoDeSaqueTedExigeDadosBancariosCompletos(): void
    {
        $_SESSION['withdrawal_request_token'] = 'token-correto';

        $errors = $this->validateWithdrawalForm($this->validWithdrawalPost([
            'method' => 'ted',
            'bank_code' => '',
            'bank_name' => 'Banco Demo',
            'agency' => '0001',
            'account_number' => '12345',
            'account_digit' => '9',
            'account_type' => 'checking',
        ]));

        $this->assertSame(['Preencha todos os dados bancários da TED.'], $errors);
    }

    public function testValidacaoDeSaquePixValidoNormalizaDadosParaService(): void
    {
        $_SESSION['withdrawal_request_token'] = 'token-correto';

        $errors = $this->validateWithdrawalForm($this->validWithdrawalPost([
            'pix_key_type' => 'phone',
            'chave_pix' => '(11) 99999-9999',
        ]), $payload);

        $this->assertSame([], $errors);
        $this->assertSame(100.50, $payload['amount']);
        $this->assertSame('11999999999', $payload['pix_key']);
        $this->assertSame('phone', $payload['pix_key_type']);
        $this->assertSame('12345678901', $payload['holder_document']);
        $this->assertSame('token-correto', $payload['request_token']);
    }

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(EstatisticasController::class, $method);

        return $reflection->invoke(null, ...$args);
    }

    private function statementMock(mixed $result, string $fetchMethod = 'fetch'): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method($fetchMethod)->willReturn($result);

        return $stmt;
    }

    private function validWithdrawalPost(array $override = []): array
    {
        return $override + [
            'valor_saque' => '100,50',
            'method' => 'pix',
            'titular_nome' => 'Empresa Demo',
            'titular_documento' => '123.456.789-01',
            'aceite_termos' => '1',
            'request_token' => 'token-correto',
            'pix_key_type' => 'cpf',
            'chave_pix' => '123.456.789-01',
            'observacao' => '',
        ];
    }

    private function validateWithdrawalForm(array $post, ?array &$payload = null): array
    {
        $method = (string) ($post['method'] ?? 'pix');
        $amount = round((float) str_replace(',', '.', (string) ($post['valor_saque'] ?? 0)), 2);
        $holderName = trim((string) ($post['titular_nome'] ?? ''));
        $holderDocument = preg_replace('/\D/', '', (string) ($post['titular_documento'] ?? ''));
        $token = (string) ($post['request_token'] ?? '');
        $errors = [];

        if ($amount < 10) $errors[] = 'O valor mínimo é R$ 10,00.';
        if (!in_array($method, ['pix','ted'], true)) $errors[] = 'Selecione PIX ou TED.';
        if (mb_strlen($holderName) < 3 || !in_array(strlen($holderDocument), [11,14], true)) $errors[] = 'Revise os dados do titular.';
        if (!isset($post['aceite_termos'])) $errors[] = 'Aceite os termos da solicitação.';
        if ($token === '' || !hash_equals($_SESSION['withdrawal_request_token'] ?? '', $token)) $errors[] = 'Solicitação duplicada ou expirada.';

        $pixKeyType = $method === 'pix' ? trim((string) ($post['pix_key_type'] ?? '')) : null;
        $pixKey = $method === 'pix' ? trim((string) ($post['chave_pix'] ?? '')) : null;
        if ($method === 'pix') {
            if (!in_array($pixKeyType, ['cnpj','cpf','email','phone','random'], true) || $pixKey === '') $errors[] = 'Informe uma chave PIX válida.';
            if ($pixKeyType === 'email' && !filter_var($pixKey, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
            if (in_array($pixKeyType, ['cnpj','cpf','phone'], true)) $pixKey = preg_replace('/\D/', '', (string) $pixKey);
        }

        $bankCode = $method === 'ted' ? preg_replace('/\D/', '', (string) ($post['bank_code'] ?? '')) : null;
        $bankName = $method === 'ted' ? trim((string) ($post['bank_name'] ?? '')) : null;
        $agency = $method === 'ted' ? trim((string) ($post['agency'] ?? '')) : null;
        $account = $method === 'ted' ? trim((string) ($post['account_number'] ?? '')) : null;
        $digit = $method === 'ted' ? trim((string) ($post['account_digit'] ?? '')) : null;
        $accountType = $method === 'ted' ? (string) ($post['account_type'] ?? '') : null;
        if ($method === 'ted' && (!$bankCode || !$bankName || !$agency || !$account || !$digit || !in_array($accountType, ['checking','savings'], true))) {
            $errors[] = 'Preencha todos os dados bancários da TED.';
        }

        $payload = [
            'amount' => $amount,
            'method' => $method,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'bank_code' => $bankCode,
            'bank_name' => $bankName,
            'agency' => $agency,
            'account_number' => $account,
            'account_digit' => $digit,
            'account_type' => $accountType,
            'holder_name' => $holderName,
            'holder_document' => $holderDocument,
            'request_note' => trim((string) ($post['observacao'] ?? '')) ?: null,
            'request_token' => $token,
        ];

        return $errors;
    }
}
