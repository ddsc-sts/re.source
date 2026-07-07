<?php
// app/Controllers/AuthController.php
//
// Correções aplicadas nesta migração:
// - Removido o require_once direto de config/conexao.php — o $pdo já vem
//   carregado globalmente pelo bootstrap.php (evita risco de redeclaração).
// - Todos os redirecionamentos hardcoded /re.source/*.php trocados por
//   rotas limpas (/login, /cadastro, /pendente, /admin, /).
//
// TODO IMPORTANTE: os arquivos JS (login.js, cadastro.js, pendente.js,
// reset.js em public/js/) provavelmente fazem fetch() pra URLs como
// 'process.php?action=login'. Eles precisam ser atualizados pra apontar
// pros novos endpoints (/login/process, /cadastro/process, etc.)
// definidos em routes/web.php — senão o AJAX para de funcionar.

class AuthController
{
    /** Confirma no backend que o CNPJ possui situacao cadastral ativa. */
    private static function validateActiveCnpj(string $cnpj): array
    {
        $cacheFile = ROOT_PATH . '/storage/cache/' . $cnpj . '.json';
        $data = null;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $data = json_decode((string) file_get_contents($cacheFile), true);
        }

        if (!is_array($data)) {
            $ch = curl_init('https://brasilapi.com.br/api/cnpj/v1/' . $cnpj);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'Re.Source/1.0',
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($response)) {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    if (!is_dir(dirname($cacheFile))) {
                        mkdir(dirname($cacheFile), 0755, true);
                    }
                    file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            }
        }

        if (!is_array($data)) {
            return [false, 'Não foi possível validar o CNPJ agora. Tente novamente em alguns instantes.'];
        }

        $status = $data['descricao_situacao_cadastral']
            ?? $data['situacao']
            ?? ($data['estabelecimento']['situacao_cadastral'] ?? '');
        $normalized = strtoupper(trim((string) $status));

        if ($normalized !== 'ATIVA') {
            return [false, 'O CNPJ precisa estar com situação ATIVA para realizar o cadastro.'];
        }

        return [true, null];
    }

    // ══════════════════════════════════════════
    // VIEWS
    // ══════════════════════════════════════════

    public static function login(): void
    {
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public static function cadastro(): void
    {
        require_once __DIR__ . '/../Views/auth/cadastro.php';
    }

    public static function showReset(): void
    {
        require_once __DIR__ . '/../Views/auth/reset.php';
    }

    public static function pendente(): void
    {
        require_once __DIR__ . '/../Views/auth/pendente.php';
    }

    public static function aguardandoAprovacao(): void
    {
        global $pdo;

        $companyId = (int) ($_SESSION['user']['company_id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT razao_social, nome_fantasia, status, review_notes, reviewed_at
             FROM companies WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            self::logout();
        }

        if ($company['status'] === 'active') {
            $_SESSION['user']['company_status'] = 'active';
            redirect_to('/base');
        }

        if (!in_array($company['status'], ['pending', 'changes_requested'], true)) {
            self::logout();
        }

        $_SESSION['user']['company_status'] = $company['status'];
        require_once __DIR__ . '/../Views/auth/aguardando_aprovacao.php';
    }

    public static function reenviarAnalise(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }
        if (!csrf_validate()) {
            flash('error', 'A sessão do formulário expirou. Tente novamente.');
            redirect_to('/aguardando-aprovacao');
        }

        $companyId = (int) ($_SESSION['user']['company_id'] ?? 0);
        if (!$companyId) {
            redirect_to('/login');
        }

        global $pdo;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "UPDATE companies
                 SET status = 'pending', review_notes = NULL, reviewed_at = NULL,
                     reviewed_by_user_id = NULL
                 WHERE id = ? AND status = 'changes_requested'"
            );
            $stmt->execute([$companyId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('O cadastro não está aguardando correções.');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs
                    (user_id, company_id, action, severity, entity_type, entity_id,
                     old_values_json, new_values_json, ip_address, user_agent)
                 VALUES (?, ?, 'COMPANY_RESUBMITTED', 'info', 'company', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                (int) ($_SESSION['user']['id'] ?? 0),
                $companyId,
                $companyId,
                json_encode(['status' => 'changes_requested'], JSON_UNESCAPED_UNICODE),
                json_encode(['status' => 'pending'], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
            $pdo->commit();
            $_SESSION['user']['company_status'] = 'pending';
            flash('success', 'Cadastro reenviado para análise.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', $e instanceof DomainException ? $e->getMessage() : 'Não foi possível reenviar o cadastro.');
        }

        redirect_to('/aguardando-aprovacao');
    }

    // ══════════════════════════════════════════
    // PROCESS LOGIN
    // ══════════════════════════════════════════

    public static function processLogin(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_validate()) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Sessão expirada. Recarregue a página e tente novamente.']);
            exit;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['password'] ?? '';

        if (empty($email) || empty($senha)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
            exit;
        }

        global $pdo;

        try {
            $stmt = $pdo->prepare("
                SELECT
                    u.id,
                    u.company_id,
                    u.name,
                    u.email,
                    u.password_hash,
                    u.role,
                    u.is_active,
                    c.status AS company_status
                FROM users u
                INNER JOIN companies c ON c.id = u.company_id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
                exit;
            }

            if (!$user['is_active']) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Usuário desativado. Entre em contato com o suporte.']);
                exit;
            }

            if (in_array($user['company_status'], ['suspended', 'inactive', 'rejected'], true)) {
                ob_clean();
                $blockedMessage = match ($user['company_status']) {
                    'rejected' => 'O cadastro da empresa foi rejeitado. Entre em contato com o suporte.',
                    'suspended' => 'Empresa suspensa. Entre em contato com o suporte.',
                    default => 'Empresa inativa. Entre em contato com o suporte.',
                };
                echo json_encode(['success' => false, 'message' => $blockedMessage]);
                exit;
            }

            if (!password_verify($senha, $user['password_hash'])) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
                exit;
            }

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'         => $user['id'],
                'company_id' => $user['company_id'],
                'name'       => $user['name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'company_status' => $user['company_status'],
            ];

            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $pdo->prepare("
                INSERT INTO user_sessions (user_id, token_hash, ip_address, user_agent, expires_at)
                VALUES (:user_id, :token_hash, :ip, :ua, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ")->execute([
                ':user_id'    => $user['id'],
                ':token_hash' => $tokenHash,
                ':ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                ':ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);

            setcookie('remember_token', $token, [
                'expires'  => time() + (60 * 60 * 24 * 30),
                'path'     => '/',
                'httponly' => true,
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
            ]);

            $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id")
                ->execute([':id' => $user['id']]);

            ob_clean();

            if (in_array($user['role'], ['admin', 'staff'], true)) {
                $redirect = '/re.source/admin';
            } elseif (in_array($user['company_status'], ['pending', 'changes_requested'], true)) {
                $redirect = '/re.source/aguardando-aprovacao';
            } else {
                $redirect = '/re.source/base';
            }

            echo json_encode([
                'success'  => true,
                'message'  => 'Login realizado com sucesso.',
                'redirect' => $redirect,
            ]);

        } catch (PDOException $e) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno no servidor. Tente novamente.',
            ]);
        }
    }

    // ══════════════════════════════════════════
    // PROCESS CADASTRO
    // ══════════════════════════════════════════

    public static function processCadastro(): void
    {
        ob_start();

        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../../config/mailer.php';

        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_GET['tipo'] ?? '') !== 'empresa') {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'erro' => 'Requisição inválida.']);
            exit;
        }

        function voltarComErro(string $msg, array $campos = []): void
        {
            $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            ob_clean();
            if ($xhr) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'erro' => $msg, 'campos' => $campos]);
            } else {
                header('Location: /re.source/cadastro?erro=' . urlencode($msg));
            }
            exit;
        }

        function voltarComSucesso(string $url): void
        {
            $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            ob_clean();
            if ($xhr) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'redirect' => $url]);
            } else {
                header("Location: $url");
            }
            exit;
        }

        if (!csrf_validate()) {
            voltarComErro('Sua sessão expirou. Recarregue a página e tente novamente.');
        }

        $nome      = trim($_POST['nome']        ?? '');
        $sobrenome = trim($_POST['sobrenome']    ?? '');
        $email     = strtolower(trim($_POST['email']    ?? ''));
        $senha     = $_POST['senha']             ?? '';
        $senhaConf = $_POST['senha_conf']        ?? '';
        $telefone  = preg_replace('/\D/', '', trim($_POST['telefone']  ?? ''));
        $estado    = trim($_POST['estado']       ?? '');
        $cnpj      = preg_replace('/\D/', '', trim($_POST['cnpj']      ?? ''));
        $razao     = trim($_POST['razao_social'] ?? '');

        $nomeFantasia = trim($_POST['nome_fantasia'] ?? '');
        $cidade       = trim($_POST['cidade']        ?? '');
        $segmento     = trim($_POST['segmento']      ?? '');
        $cep          = preg_replace('/\D/', '', trim($_POST['cep']    ?? ''));
        $endereco     = trim($_POST['endereco']      ?? '');
        $numero       = trim($_POST['numero']        ?? '');
        $complemento  = trim($_POST['complemento']   ?? ''); // opcional

        $erros  = [];
        $campos = [];

        if (!$nome)      { $erros[] = 'Nome é obrigatório.';         $campos[] = ['field' => 'nome',      'msg' => 'Nome é obrigatório.']; }
        if (!$sobrenome) { $erros[] = 'Sobrenome é obrigatório.';    $campos[] = ['field' => 'sobrenome', 'msg' => 'Sobrenome é obrigatório.']; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = 'E-mail inválido.'; $campos[] = ['field' => 'email', 'msg' => 'E-mail inválido.']; }
        if (strlen($cnpj) !== 14) { $erros[] = 'CNPJ inválido.';    $campos[] = ['field' => 'cnpj',  'msg' => 'CNPJ deve ter 14 dígitos.']; }
        if (!$razao)     { $erros[] = 'Razão social é obrigatória.'; $campos[] = ['field' => 'razao', 'msg' => 'Razão social é obrigatória.']; }
        if (!$nomeFantasia) { $erros[] = 'Nome fantasia é obrigatório.'; $campos[] = ['field' => 'nomeFantasia', 'msg' => 'Informe o nome fantasia.']; }
        if (strlen($cep) !== 8) { $erros[] = 'CEP inválido.'; $campos[] = ['field' => 'cep', 'msg' => 'CEP deve ter 8 dígitos.']; }
        if (!$endereco)  { $erros[] = 'Endereço é obrigatório.'; $campos[] = ['field' => 'endereco', 'msg' => 'Informe o endereço.']; }
        if (!$numero)    { $erros[] = 'Número é obrigatório.'; $campos[] = ['field' => 'numero', 'msg' => 'Informe o número.']; }
        if (strlen($senha) < 8) { $erros[] = 'Senha deve ter ao menos 8 caracteres.'; $campos[] = ['field' => 'senha', 'msg' => 'Senha deve ter ao menos 8 caracteres.']; }
        if ($senha !== $senhaConf) { $erros[] = 'As senhas não coincidem.'; $campos[] = ['field' => 'senhaConf', 'msg' => 'As senhas não coincidem.']; }
        if (!$estado)    { $erros[] = 'Estado é obrigatório.';       $campos[] = ['field' => 'estado', 'msg' => 'Selecione seu estado.']; }
        if (!$cidade)    { $erros[] = 'Cidade é obrigatória.';       $campos[] = ['field' => 'cidade', 'msg' => 'Informe a cidade.']; }
        if (!$segmento)  { $erros[] = 'Segmento é obrigatório.';     $campos[] = ['field' => 'segmento', 'msg' => 'Selecione o segmento.']; }

        if ($erros) voltarComErro(implode(' · ', $erros), $campos);

        $dominiosBloqueados = $pdo->query('SELECT domain FROM blocked_email_domains')->fetchAll(PDO::FETCH_COLUMN);
        $dominio = explode('@', $email)[1] ?? '';
        if (in_array($dominio, $dominiosBloqueados)) {
            voltarComErro('Use um e-mail corporativo. Domínios gratuitos não são aceitos.', [['field' => 'email', 'msg' => 'Use um e-mail corporativo.']]);
        }

        $stmt = $pdo->prepare('SELECT id FROM companies WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            voltarComErro('Este e-mail já está cadastrado.', [['field' => 'email', 'msg' => 'Este e-mail já está cadastrado.']]);
        }

        $stmt = $pdo->prepare('SELECT id FROM companies WHERE cnpj = ?');
        $stmt->execute([$cnpj]);
        if ($stmt->rowCount() > 0) {
            voltarComErro('Este CNPJ já está cadastrado.', [['field' => 'cnpj', 'msg' => 'CNPJ já cadastrado.']]);
        }

        function cnpjValido(string $cnpj): bool
        {
            if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;
            $calc = function (string $cnpj, int $len): int {
                $soma = 0; $pos = $len - 7;
                for ($i = $len; $i >= 1; $i--) {
                    $soma += (int)$cnpj[$len - $i] * $pos--;
                    if ($pos < 2) $pos = 9;
                }
                $resto = $soma % 11;
                return $resto < 2 ? 0 : 11 - $resto;
            };
            return $calc($cnpj, 12) === (int)$cnpj[12] && $calc($cnpj, 13) === (int)$cnpj[13];
        }

        if (!cnpjValido($cnpj)) {
            voltarComErro('CNPJ inválido. Verifique os números informados.', [['field' => 'cnpj', 'msg' => 'CNPJ inválido.']]);
        }

        [$cnpjAtivo, $cnpjErro] = self::validateActiveCnpj($cnpj);
        if (!$cnpjAtivo) {
            voltarComErro((string) $cnpjErro, [['field' => 'cnpj', 'msg' => (string) $cnpjErro]]);
        }

        $passwordHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['cadastro_pendente'] = [
            'codigo'        => $codigo,
            'expires_at'    => time() + 3600,
            'ultimo_envio'  => time(),
            'nome'          => $nome,
            'sobrenome'     => $sobrenome,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'telefone'      => $telefone,
            'estado'        => $estado,
            'cnpj'          => $cnpj,
            'razao'         => $razao,
            'nome_fantasia' => $nomeFantasia,
            'cidade'        => $cidade,
            'segmento'      => $segmento,
            'cep'           => $cep,
            'endereco'      => $endereco,
            'numero'        => $numero,
            'complemento'   => $complemento,
        ];

        $enviou = enviarEmailCodigo($email, $nome, $codigo);
        if (!$enviou) {
            voltarComErro('Não foi possível enviar o e-mail de verificação. Tente novamente.');
        }

        voltarComSucesso('/re.source/pendente');
    }

    // ══════════════════════════════════════════
    // PROCESS REENVIAR
    // ══════════════════════════════════════════

    public static function processReenviar(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../../config/mailer.php';

        function jsonOk(string $msg): void
        {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'msg' => $msg]);
            exit;
        }

        function jsonErro(string $msg): void
        {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'erro' => $msg]);
            exit;
        }

        $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$isXhr) {
            header('Location: /re.source/cadastro');
            exit;
        }

        $pendente = &$_SESSION['cadastro_pendente'];
        if (!$pendente) jsonErro('Sessão expirada. Refaça o cadastro.');

        $ultimoEnvio = $pendente['ultimo_envio'] ?? 0;
        if ((time() - $ultimoEnvio) < 60) {
            $falta = 60 - (time() - $ultimoEnvio);
            jsonErro("Aguarde {$falta}s antes de reenviar.");
        }

        $novoCodigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pendente['codigo']       = $novoCodigo;
        $pendente['expires_at']   = time() + 3600;
        $pendente['ultimo_envio'] = time();

        $enviou = enviarEmailCodigo($pendente['email'], $pendente['nome'], $novoCodigo);
        if (!$enviou) jsonErro('Falha ao enviar o e-mail. Tente novamente.');

        jsonOk("Novo código enviado para {$pendente['email']}.");
    }

    // ══════════════════════════════════════════
    // PROCESS RESET
    // ══════════════════════════════════════════

    public static function processReset(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['validate'])) {
            header('Content-Type: application/json; charset=utf-8');
            $token = trim($_GET['token'] ?? '');
            if ($token === '') {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Token não informado.']);
                exit;
            }
            try {
                $tokenHash = hash('sha256', $token);
                $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
                $stmt->execute([':hash' => $tokenHash]);
                ob_clean();
                echo json_encode(['success' => (bool) $stmt->fetch()]);
            } catch (PDOException $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Erro interno.']);
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            ob_clean();
            self::showReset();
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $token    = trim($_POST['token']            ?? '');
        $senha    = trim($_POST['password']         ?? '');
        $confirma = trim($_POST['password_confirm'] ?? '');

        if ($token === '' || $senha === '') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            exit;
        }

        if (strlen($senha) < 8) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
            exit;
        }

        if ($senha !== $confirma) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
            exit;
        }

        $tokenHash = hash('sha256', $token);

        try {
            $stmt = $pdo->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
            $stmt->execute([':hash' => $tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado.']);
                exit;
            }

            $novoHash = password_hash($senha, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
                ->execute([':hash' => $novoHash, ':id' => $reset['user_id']]);

            $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
                ->execute([':id' => $reset['id']]);

            $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid")
                ->execute([':uid' => $reset['user_id']]);

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Senha redefinida com sucesso!']);

        } catch (PDOException $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
        }
    }

    // ══════════════════════════════════════════
    // PROCESS RECOVER
    // ══════════════════════════════════════════

    public static function processRecover(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../../config/mailer.php';

        global $pdo;

        header('Content-Type: application/json; charset=utf-8');

        function responder(bool $ok, string $msg): void
        {
            ob_clean();
            echo json_encode(['success' => $ok, 'message' => $msg]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            responder(false, 'Método não permitido.');
        }

        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($email === '') responder(false, 'Informe seu e-mail.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responder(false, 'E-mail inválido.');

        try {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                responder(true, 'Se este e-mail estiver cadastrado, você receberá o link em breve.');
            }

            $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL")
                ->execute([':uid' => $user['id']]);

            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $pdo->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:uid, :hash, :exp)
            ")->execute([
                ':uid'  => $user['id'],
                ':hash' => $tokenHash,
                ':exp'  => $expiresAt,
            ]);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // TODO: ambiente XAMPP precisa do prefixo /re.source/ na URL.
            // Se mudar de ambiente, remover esse prefixo.
            $link   = "{$scheme}://{$host}/re.source/reset?token={$token}";

            $enviado = enviarEmailRecuperacao($email, $user['name'], $link);
            if (!$enviado) responder(false, 'Falha ao enviar o e-mail. Tente novamente.');

            responder(true, 'Se este e-mail estiver cadastrado, você receberá o link em breve.');

        } catch (PDOException $e) {
            responder(false, 'Erro interno. Tente novamente.');
        }
    }

    // ══════════════════════════════════════════
    // SESSAO INFO
    // ══════════════════════════════════════════

    public static function sessaoInfo(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        header('Content-Type: application/json; charset=utf-8');

        $pendente = $_SESSION['cadastro_pendente'] ?? null;

        if (!$pendente) {
            ob_clean();
            echo json_encode(['ok' => false]);
            exit;
        }

        ob_clean();
        echo json_encode([
            'ok'    => true,
            'email' => $pendente['email'] ?? '',
        ]);
    }

    // ══════════════════════════════════════════
    // PROCESS VERIFICAR
    // ══════════════════════════════════════════

    public static function processVerificar(): void
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) session_start();

        global $pdo;

        $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        function responderErro(string $msg): void
        {
            $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            ob_clean();
            if ($xhr) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'erro' => $msg]);
            } else {
                header('Location: /re.source/pendente?erro=' . urlencode($msg));
            }
            exit;
        }

        function responderSucesso(string $url): void
        {
            $xhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
            ob_clean();
            if ($xhr) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'redirect' => $url]);
            } else {
                header("Location: $url");
            }
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /re.source/cadastro');
            exit;
        }

        $codigoDigitado = trim($_POST['codigo'] ?? '');
        if ($codigoDigitado === '') {
            $body = json_decode(file_get_contents('php://input'), true);
            $codigoDigitado = trim($body['codigo'] ?? '');
        }

        $pendente = $_SESSION['cadastro_pendente'] ?? null;

        if (!$pendente) responderErro('Sessão expirada. Faça o cadastro novamente.');
        if (time() > $pendente['expires_at']) {
            unset($_SESSION['cadastro_pendente']);
            responderErro('Código expirado. Faça o cadastro novamente.');
        }
        if ($codigoDigitado === '') responderErro('Nenhum código recebido. Tente novamente.');
        if ($codigoDigitado !== $pendente['codigo']) responderErro('Código incorreto. Verifique e tente novamente.');

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO addresses (zip_code, street, number, complement, district, city, state)
                 VALUES (?,?,?,?,'',?,?)"
            )->execute([
                $pendente['cep']         ?? '',
                $pendente['endereco']    ?? '',
                $pendente['numero']      ?? '',
                $pendente['complemento'] ?? '', // opcional — pode ficar vazio
                $pendente['cidade']      ?? '',
                $pendente['estado'],
            ]);
            $addressId = $pdo->lastInsertId();

            $nomeCompleto = $pendente['nome'] . ' ' . $pendente['sobrenome'];

            $pdo->prepare(
                "INSERT INTO companies (cnpj, razao_social, nome_fantasia, segment, email, phone, address_id, plan_id, responsible_name, status, email_verified_at)
                 VALUES (?,?,?,?,?,?,?,1,?,'pending',NOW())"
            )->execute([
                $pendente['cnpj'],
                $pendente['razao'],
                $pendente['nome_fantasia'] ?? '',
                $pendente['segmento']      ?? '',
                $pendente['email'],
                $pendente['telefone'],
                $addressId,
                $nomeCompleto,
            ]);
            $companyId = $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO users (company_id, name, email, password_hash, role)
                 VALUES (?,?,?,?,'admin_company')"
            )->execute([
                $companyId,
                $nomeCompleto,
                $pendente['email'],
                $pendente['password_hash'],
            ]);

            $pdo->commit();

        } catch (\Throwable $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                unset($_SESSION['cadastro_pendente']);
                responderSucesso('/re.source/login?aviso=' . urlencode('Esta conta já está ativa. Faça login.'));
            }
            responderErro('Erro interno ao salvar. Tente novamente.');
        }

        unset($_SESSION['cadastro_pendente']);
        responderSucesso('/re.source/login?sucesso=' . urlencode('Conta confirmada! Faça login para acompanhar a aprovação.'));
    }

    // ══════════════════════════════════════════
    // LOGOUT
    // ══════════════════════════════════════════

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        global $pdo;

        // Remove sessão do banco
        if (isset($_COOKIE['remember_token'])) {

            $tokenHash = hash('sha256', $_COOKIE['remember_token']);

            try {
                $stmt = $pdo->prepare("
                    DELETE FROM user_sessions
                    WHERE token_hash = :token
                ");

                $stmt->execute([
                    ':token' => $tokenHash
                ]);

            } catch (\Throwable $e) {
                // ignora erro
            }

            setcookie(
                'remember_token',
                '',
                time() - 3600,
                '/'
            );
        }

        // Limpa a sessão
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /re.source/login');
        exit;
    }
        // ══════════════════════════════════════════
    // DISPATCHER — substitui o antigo process.php
    // ══════════════════════════════════════════
    public static function process(): void
    {
        $action = $_GET['action'] ?? '';

        match ($action) {
            'login'       => self::processLogin(),
            'cadastro'    => self::processCadastro(),
            'recover'     => self::processRecover(),
            'reenviar'    => self::processReenviar(),
            'reset'       => self::processReset(),
            'verificar'   => self::processVerificar(),
            'sessao-info' => self::sessaoInfo(),
            'logout'      => self::logout(),
            default       => http_response_code(404),
        };
    }
}