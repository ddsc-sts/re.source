<?php
// BackEnd/auth/cadastro.php
// FLUXO: valida → gera código 6 dígitos → salva sessão → envia e-mail via Mailtrap SMTP → redireciona

ob_start();
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/mailer.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /cadastro.php");
    exit;
}

$isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

function voltarComErro(string $msg, array $campos = []): void {
    global $isXhr;
    if ($isXhr) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => $msg, 'campos' => $campos]);
    } else {
        header("Location: /cadastro.php?erro=" . urlencode($msg));
    }
    exit;
}

function voltarComSucesso(string $url): void {
    global $isXhr;
    if ($isXhr) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'redirect' => $url]);
    } else {
        header("Location: $url");
    }
    exit;
}

// ── 1. Coleta e sanitização ───────────────────────────────────
$nome      = trim($_POST["nome"]         ?? '');
$sobrenome = trim($_POST["sobrenome"]    ?? '');
$email     = strtolower(trim($_POST["email"]    ?? ''));
$senha     = $_POST["senha"]             ?? '';
$senhaConf = $_POST["senha_conf"]        ?? '';
$telefone  = preg_replace('/\D/', '', trim($_POST["telefone"]  ?? ''));
$estado    = trim($_POST["estado"]       ?? '');
$cnpj      = preg_replace('/\D/', '', trim($_POST["cnpj"]      ?? ''));
$razao     = trim($_POST["razao_social"] ?? '');

// ── 2. Validações básicas ─────────────────────────────────────
$erros  = [];
$campos = [];

if (!$nome)   { $erros[] = "Nome é obrigatório.";        $campos[] = ['field'=>'nome',      'msg'=>'Nome é obrigatório.']; }
if (!$sobrenome) { $erros[] = "Sobrenome é obrigatório."; $campos[] = ['field'=>'sobrenome', 'msg'=>'Sobrenome é obrigatório.']; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = "E-mail inválido."; $campos[] = ['field'=>'email', 'msg'=>'E-mail inválido.']; }
if (strlen($cnpj) !== 14) { $erros[] = "CNPJ inválido."; $campos[] = ['field'=>'cnpj', 'msg'=>'CNPJ deve ter 14 dígitos.']; }
if (!$razao)  { $erros[] = "Razão social é obrigatória."; $campos[] = ['field'=>'razao', 'msg'=>'Razão social é obrigatória.']; }
if (strlen($senha) < 8) { $erros[] = "Senha deve ter ao menos 8 caracteres."; $campos[] = ['field'=>'senha', 'msg'=>'Senha deve ter ao menos 8 caracteres.']; }
if ($senha !== $senhaConf) { $erros[] = "As senhas não coincidem."; $campos[] = ['field'=>'senhaConf', 'msg'=>'As senhas não coincidem.']; }
if (!$estado) { $erros[] = "Estado é obrigatório."; $campos[] = ['field'=>'estado', 'msg'=>'Selecione seu estado.']; }
if ($erros) voltarComErro(implode(" · ", $erros), $campos);

// ── 3. E-mail corporativo ─────────────────────────────────────
$dominiosBloqueados = $pdo->query("SELECT domain FROM blocked_email_domains")->fetchAll(PDO::FETCH_COLUMN);
$dominio = explode("@", $email)[1] ?? '';
if (in_array($dominio, $dominiosBloqueados)) {
    voltarComErro("Use um e-mail corporativo. Domínios gratuitos não são aceitos.", [['field'=>'email','msg'=>'Use um e-mail corporativo.']]);
}

// ── 4. E-mail já cadastrado? ──────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    voltarComErro("Este e-mail já está cadastrado.", [['field'=>'email','msg'=>'Este e-mail já está cadastrado.']]);
}

// ── 5. CNPJ já cadastrado? ────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ?");
$stmt->execute([$cnpj]);
if ($stmt->rowCount() > 0) {
    voltarComErro("Este CNPJ já está cadastrado.", [['field'=>'cnpj','msg'=>'CNPJ já cadastrado.']]);
}

// ── 6. Validação dígitos do CNPJ ─────────────────────────────
function cnpjValido(string $cnpj): bool {
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;
    $calc = function(string $cnpj, int $len): int {
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
    voltarComErro("CNPJ inválido. Verifique os números informados.", [['field'=>'cnpj','msg'=>'CNPJ inválido.']]);
}

// ── 7. Hash da senha ──────────────────────────────────────────
$passwordHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 8. Gera código 6 dígitos e salva na sessão ───────────────
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
];

// ── 9. Envia e-mail via Mailtrap SMTP ────────────────────────
$enviou = enviarEmailCodigo($email, $nome, $codigo);
if (!$enviou) {
    voltarComErro("Não foi possível enviar o e-mail de verificação. Tente novamente.");
}

// ── 10. Redireciona para /pendente.php sem nada na URL ───────
$script = $_SERVER['SCRIPT_NAME'];
$raiz   = rtrim(str_replace('BackEnd/auth/cadastro.php', '', $script), '/');
voltarComSucesso($raiz . "/pendente.php");