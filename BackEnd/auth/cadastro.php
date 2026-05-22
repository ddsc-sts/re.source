<?php

// BackEnd/auth/cadastro.php — processa POST do formulário

require_once __DIR__ . "/../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /cadastro.php");
    exit;
}

// ── Detecta se é uma requisição AJAX (fetch com X-Requested-With) ──
$isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

/**
 * Encerra a execução devolvendo erro.
 * XHR  → JSON  { ok: false, erro: "...", campos: [...] }
 * Form → redirect para cadastro.php?erro=...
 */
function voltarComErro(string $msg, array $campos = []): void {
    global $isXhr;
    if ($isXhr) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => $msg, 'campos' => $campos]);
    } else {
        header("Location: /cadastro.php?erro=" . urlencode($msg));
    }
    exit;
}

/**
 * Encerra com sucesso.
 * XHR  → JSON  { ok: true, redirect: "..." }
 * Form → redirect para pendente.php
 */
function voltarComSucesso(string $url): void {
    global $isXhr;
    if ($isXhr) {
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
$campos = [];   // [ ['field' => 'nome', 'msg' => '...'], ... ]

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

if ($erros) {
    voltarComErro(implode(" · ", $erros), $campos);
}

// ── 3. E-mail corporativo (RN-04) ─────────────────────────────
$dominiosBloqueados = $pdo->query("SELECT domain FROM blocked_email_domains")
                          ->fetchAll(PDO::FETCH_COLUMN);
$dominio = explode("@", $email)[1] ?? '';
if (in_array($dominio, $dominiosBloqueados)) {
    voltarComErro(
        "Use um e-mail corporativo. Domínios gratuitos não são aceitos.",
        [['field' => 'email', 'msg' => 'Use um e-mail corporativo.']]
    );
}

// ── 4. CNPJ único (RN-02) ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ?");
$stmt->execute([$cnpj]);
if ($stmt->rowCount() > 0) {
    voltarComErro(
        "CNPJ já cadastrado na plataforma.",
        [['field' => 'cnpj', 'msg' => 'CNPJ já cadastrado na plataforma.']]
    );
}

// ── 5. Validação ReceitaWS (RF-02, RN-03) ─────────────────────
$apiResp = @file_get_contents(
    "https://www.receitaws.com.br/v1/cnpj/{$cnpj}",
    false,
    stream_context_create(['http' => ['timeout' => 5]])
);
if (!$apiResp) {
    voltarComErro("Não foi possível validar o CNPJ agora. Tente novamente.");
}

$dadosCnpj = json_decode($apiResp, true);
if (($dadosCnpj['status'] ?? '') === 'ERROR' ||
    strtoupper($dadosCnpj['situacao'] ?? '') !== 'ATIVA') {
    $situacao = $dadosCnpj['situacao'] ?? 'não encontrado';
    voltarComErro(
        "CNPJ com situação '$situacao'. Apenas CNPJs ATIVOS são aceitos.",
        [['field' => 'cnpj', 'msg' => "CNPJ $situacao. Apenas CNPJs ativos são aceitos."]]
    );
}
if (!$razao && !empty($dadosCnpj['nome'])) $razao = $dadosCnpj['nome'];

$pdo->prepare(
    "INSERT INTO cnpj_validations (cnpj, status, razao_social, api_response_json)
     VALUES (?,?,?,?)"
)->execute([$cnpj, $dadosCnpj['situacao'], $dadosCnpj['nome'] ?? null, $apiResp]);

// ── 6. Hash senha (RNF-03) ────────────────────────────────────
$passwordHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 7. Transação: addresses → companies → users ───────────────
$pdo->beginTransaction();

try {
    $pdo->prepare(
        "INSERT INTO addresses (zip_code, street, number, district, city, state)
         VALUES ('','','','','',?)"
    )->execute([$estado]);
    $addressId = $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO companies (cnpj, razao_social, email, phone, address_id, plan_id, responsible_name)
         VALUES (?,?,?,?,?,1,?)"
    )->execute([$cnpj, $razao, $email, $telefone, $addressId, "$nome $sobrenome"]);
    $companyId = $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO users (company_id, name, email, password_hash, role)
         VALUES (?,?,?,?,'admin_company')"
    )->execute([$companyId, "$nome $sobrenome", $email, $passwordHash]);
    $userId = $pdo->lastInsertId();

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    voltarComErro("Erro interno ao salvar. Tente novamente.");
}

// ── 8. Gerar token de confirmação (RF-03) ─────────────────────
$token     = bin2hex(random_bytes(32));   // 64 hex chars
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

$pdo->prepare(
    "INSERT INTO email_verifications (user_id, token, expires_at)
     VALUES (?, ?, ?)"
)->execute([$userId, $token, $expiresAt]);

// ── 9. Enviar e-mail de confirmação ───────────────────────────
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
        Seu cadastro foi recebido com sucesso. Para ativar sua conta e começar a usar a plataforma, clique no botão abaixo:
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

mail($email, $assunto, $corpo, $headers);
// ⚠️  Em produção use PHPMailer + SMTP.

// ── 10. Sucesso ───────────────────────────────────────────────
$redirectUrl = "/pendente.php?email=" . urlencode($email);
voltarComSucesso($redirectUrl);