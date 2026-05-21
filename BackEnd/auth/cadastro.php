<?php

// BackEnd/auth/cadastro.php — processa POST do formulário

require_once __DIR__ . "/../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /cadastro.php");
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

function voltarComErro(string $msg): void {
    header("Location: /cadastro.php?erro=" . urlencode($msg));
    exit;
}

// ── 2. Validações básicas ─────────────────────────────────────
$erros = [];
if (!$nome || !$sobrenome)                      $erros[] = "Nome e sobrenome são obrigatórios.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "E-mail inválido.";
if (strlen($cnpj) !== 14)                       $erros[] = "CNPJ inválido.";
if (!$razao)                                    $erros[] = "Razão social é obrigatória.";
if (strlen($senha) < 8)                         $erros[] = "Senha deve ter ao menos 8 caracteres.";
if ($senha !== $senhaConf)                      $erros[] = "As senhas não coincidem.";
if (!$estado)                                   $erros[] = "Estado é obrigatório.";
if ($erros) voltarComErro(implode(" · ", $erros));

// ── 3. E-mail corporativo (RN-04) ─────────────────────────────
$dominiosBloqueados = $pdo->query("SELECT domain FROM blocked_email_domains")->fetchAll(PDO::FETCH_COLUMN);
$dominio = explode("@", $email)[1] ?? '';
if (in_array($dominio, $dominiosBloqueados)) {
    voltarComErro("Use um e-mail corporativo. Domínios gratuitos não são aceitos.");
}

// ── 4. CNPJ único (RN-02) ─────────────────────────────────────
$stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = ?");
$stmt->execute([$cnpj]);
if ($stmt->rowCount() > 0) voltarComErro("CNPJ já cadastrado na plataforma.");

// ── 5. Validação ReceitaWS (RF-02, RN-03) ─────────────────────
$apiResp = @file_get_contents(
    "https://www.receitaws.com.br/v1/cnpj/{$cnpj}",
    false,
    stream_context_create(['http' => ['timeout' => 5]])
);
if (!$apiResp) voltarComErro("Não foi possível validar o CNPJ agora. Tente novamente.");

$dadosCnpj = json_decode($apiResp, true);
if (($dadosCnpj['status'] ?? '') === 'ERROR' || strtoupper($dadosCnpj['situacao'] ?? '') !== 'ATIVA') {
    voltarComErro("CNPJ com situação '" . ($dadosCnpj['situacao'] ?? 'não encontrado') . "'. Apenas CNPJs ATIVOS são aceitos.");
}
if (!$razao && !empty($dadosCnpj['nome'])) $razao = $dadosCnpj['nome'];

$pdo->prepare("INSERT INTO cnpj_validations (cnpj, status, razao_social, api_response_json) VALUES (?,?,?,?)")
    ->execute([$cnpj, $dadosCnpj['situacao'], $dadosCnpj['nome'] ?? null, $apiResp]);

// ── 6. Hash senha (RNF-03) ────────────────────────────────────
$passwordHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

// ── 7. Transação: addresses → companies → users ───────────────
$pdo->beginTransaction();

try {

    $pdo->prepare("INSERT INTO addresses (zip_code, street, number, district, city, state) VALUES ('','','','','',?)")
        ->execute([$estado]);
    $addressId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO companies (cnpj, razao_social, email, phone, address_id, plan_id, responsible_name) VALUES (?,?,?,?,?,1,?)")
        ->execute([$cnpj, $razao, $email, $telefone, $addressId, "$nome $sobrenome"]);
    $companyId = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO users (company_id, name, email, password_hash, role) VALUES (?,?,?,?,'admin_company')")
        ->execute([$companyId, "$nome $sobrenome", $email, $passwordHash]);
    $userId = $pdo->lastInsertId();

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    voltarComErro("Erro interno ao salvar. Tente novamente.");
}

// ── 8. Gerar token de confirmação (RF-03) ─────────────────────
$token     = bin2hex(random_bytes(32));          // 64 chars hexadecimais
$expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

$pdo->prepare("
    INSERT INTO email_verifications (user_id, token, expires_at)
    VALUES (?, ?, ?)
")->execute([$userId, $token, $expiresAt]);

// ── 9. Enviar e-mail de confirmação ───────────────────────────
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST'];

$linkConfirmacao = $baseUrl . "/BackEnd/auth/verificar.php?token=" . $token;
$nomeCompleto    = "$nome $sobrenome";

$assunto = "Confirme seu cadastro — Re.Source";

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

// Headers para e-mail HTML
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Re.Source <noreply@re.source.com.br>\r\n";
$headers .= "Reply-To: suporte@re.source.com.br\r\n";

mail($email, $assunto, $corpo, $headers);
// ⚠️  Em produção use PHPMailer + SMTP. Em XAMPP local, configure o sendmail
// no php.ini ou use MailHog/Mailtrap para testar sem enviar e-mails reais.

// ── 10. Redireciona para tela de "verifique seu e-mail" ───────
header("Location: /pendente.php?email=" . urlencode($email));
exit;