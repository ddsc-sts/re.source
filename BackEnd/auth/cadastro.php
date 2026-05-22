<?php

// BackEnd/auth/cadastro.php — processa POST do formulário
// FLUXO: valida → salva em sessão → envia e-mail → cadastra só após confirmação

ob_start();
session_start();
require_once __DIR__ . "/../config/conexao.php";

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

if (!$nome) {
    $erros[]  = "Nome é obrigatório.";
    $campos[] = ['field' => 'nome', 'msg' => 'Nome é obrigatório.'];
}
if (!$sobrenome) {
    $erros[]  = "Sobrenome é obrigatório.";
    $campos[] = ['field' => 'sobrenome', 'msg' => 'Sobrenome é obrigatório.'];
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[]  = "E-mail inválido.";
    $campos[] = ['field' => 'email', 'msg' => 'E-mail inválido.'];
}
if (strlen($cnpj) !== 14) {
    $erros[]  = "CNPJ inválido.";
    $campos[] = ['field' => 'cnpj', 'msg' => 'CNPJ deve ter 14 dígitos.'];
}
if (!$razao) {
    $erros[]  = "Razão social é obrigatória.";
    $campos[] = ['field' => 'razao', 'msg' => 'Razão social é obrigatória.'];
}
if (strlen($senha) < 8) {
    $erros[]  = "Senha deve ter ao menos 8 caracteres.";
    $campos[] = ['field' => 'senha', 'msg' => 'Senha deve ter ao menos 8 caracteres.'];
}
if ($senha !== $senhaConf) {
    $erros[]  = "As senhas não coincidem.";
    $campos[] = ['field' => 'senhaConf', 'msg' => 'As senhas não coincidem.'];
}
if (!$estado) {
    $erros[]  = "Estado é obrigatório.";
    $campos[] = ['field' => 'estado', 'msg' => 'Selecione seu estado.'];
}
if ($erros) voltarComErro(implode(" · ", $erros), $campos);

// ── 3. E-mail corporativo ─────────────────────────────────────
$dominiosBloqueados = $pdo->query("SELECT domain FROM blocked_email_domains")
                          ->fetchAll(PDO::FETCH_COLUMN);
$dominio = explode("@", $email)[1] ?? '';
if (in_array($dominio, $dominiosBloqueados)) {
    voltarComErro(
        "Use um e-mail corporativo. Domínios gratuitos não são aceitos.",
        [['field' => 'email', 'msg' => 'Use um e-mail corporativo.']]
    );
}

// ── 4. E-mail já cadastrado? ──────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    voltarComErro(
        "Este e-mail já está cadastrado na plataforma.",
        [['field' => 'email', 'msg' => 'Este e-mail já está cadastrado.']]
    );
}

// ── 5. CNPJ já cadastrado? ────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ?");
$stmt->execute([$cnpj]);
if ($stmt->rowCount() > 0) {
    voltarComErro(
        "Este CNPJ já está cadastrado na plataforma.",
        [['field' => 'cnpj', 'msg' => 'CNPJ já cadastrado na plataforma.']]
    );
}

// ── 6. Validação local dos dígitos do CNPJ ───────────────────
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
    return $calc($cnpj, 12) === (int)$cnpj[12]
        && $calc($cnpj, 13) === (int)$cnpj[13];
}
if (!cnpjValido($cnpj)) {
    voltarComErro(
        "CNPJ inválido. Verifique os números informados.",
        [['field' => 'cnpj', 'msg' => 'CNPJ inválido.']]
    );
}

// ── 7. Hash da senha ─────────────────────────────────────────
$passwordHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 8. Salva dados em sessão (ainda NÃO grava no banco) ───────
$token     = bin2hex(random_bytes(32));   // 64 hex chars — enviado no link
$tokenHash = hash('sha256', $token);      // armazenado na sessão para comparar
$expiresAt = time() + 86400;              // 24 horas

$_SESSION['cadastro_pendente'] = [
    'token_hash'    => $tokenHash,
    'expires_at'    => $expiresAt,
    'nome'          => $nome,
    'sobrenome'     => $sobrenome,
    'email'         => $email,
    'password_hash' => $passwordHash,
    'telefone'      => $telefone,
    'estado'        => $estado,
    'cnpj'          => $cnpj,
    'razao'         => $razao,
];

// ── 9. Envia e-mail de confirmação ────────────────────────────
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'];

$linkConfirmacao = $baseUrl . "/BackEnd/auth/verificar.php?token=" . $token;
$nomeCompleto    = "$nome $sobrenome";
$assunto         = "Confirme seu cadastro — Re.Source";

$corpo = "
<!DOCTYPE html>
<html lang='pt-BR'>
<body style='font-family:Inter,sans-serif;background:#f4f4f4;padding:2rem;'>
  <div style='max-width:520px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);'>
    <div style='background:#157347;padding:1.5rem 2rem;'>
      <h1 style='color:#fff;margin:0;font-size:1.4rem;'>Re.Source</h1>
    </div>
    <div style='padding:2rem;'>
      <h2 style='color:#1a1a1a;margin-top:0;'>Olá, $nomeCompleto!</h2>
      <p style='color:#444;line-height:1.6;'>
        Para ativar sua conta e começar a usar a plataforma, clique no botão abaixo:
      </p>
      <div style='text-align:center;margin:2rem 0;'>
        <a href='$linkConfirmacao'
           style='background:#157347;color:#fff;padding:.9rem 2rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:1rem;display:inline-block;'>
          ✅ Confirmar meu e-mail
        </a>
      </div>
      <p style='color:#888;font-size:.85rem;'>
        O link expira em <strong>24 horas</strong>. Se você não criou uma conta no Re.Source, ignore este e-mail.
      </p>
      <hr style='border:none;border-top:1px solid #eee;margin:1.5rem 0;'>
      <p style='color:#aaa;font-size:.8rem;text-align:center;'>
        Caso o botão não funcione, copie e cole este link no navegador:<br>
        <a href='$linkConfirmacao' style='color:#157347;word-break:break-all;'>$linkConfirmacao</a>
      </p>
    </div>
  </div>
</body>
</html>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Re.Source <noreply@re.source.com.br>\r\n";
$headers .= "Reply-To: suporte@re.source.com.br\r\n";

@mail($email, $assunto, $corpo, $headers);

// ── 10. Redireciona para tela de pendente ────────────────────
voltarComSucesso("/pendente.php?email=" . urlencode($email));