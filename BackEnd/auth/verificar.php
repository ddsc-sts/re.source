<?php

// BackEnd/auth/verificar.php — valida o token e ativa a conta

require_once __DIR__ . "/../config/conexao.php";

$token = trim($_GET['token'] ?? '');

// ── Sem token na URL ──────────────────────────────────────────
if (!$token) {
    header("Location: /cadastro.php?erro=" . urlencode("Link de verificação inválido."));
    exit;
}

// ── Busca o token no banco ────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT ev.id,
           ev.user_id,
           ev.expires_at,
           ev.used_at,
           u.email,
           u.name
    FROM   email_verifications ev
    JOIN   users u ON u.id = ev.user_id
    WHERE  ev.token = ?
    LIMIT  1
");
$stmt->execute([$token]);
$registro = $stmt->fetch();

// ── Token não encontrado ──────────────────────────────────────
if (!$registro) {
    exibirErro("Link inválido", "Este link de confirmação não existe ou já foi removido.");
}

// ── Token já utilizado ────────────────────────────────────────
if ($registro['used_at'] !== null) {
    exibirSucesso($registro['name'], $registro['email'], true);
}

// ── Token expirado ────────────────────────────────────────────
if (strtotime($registro['expires_at']) < time()) {
    // Apaga o token expirado e oferece reenvio
    $pdo->prepare("DELETE FROM email_verifications WHERE id = ?")->execute([$registro['id']]);
    exibirErro(
        "Link expirado",
        "Seu link de confirmação expirou. <a href='/reenviar.php?email=" . urlencode($registro['email']) . "'>Clique aqui para receber um novo link.</a>"
    );
}

// ── Tudo certo — ativa a conta ────────────────────────────────
$pdo->beginTransaction();
try {

    // Marca e-mail como verificado no usuário
    $pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")
        ->execute([$registro['user_id']]);

    // Marca token como usado
    $pdo->prepare("UPDATE email_verifications SET used_at = NOW() WHERE id = ?")
        ->execute([$registro['id']]);

    // Ativa a empresa vinculada ao usuário
    $pdo->prepare("
        UPDATE companies SET status = 'active'
        WHERE id = (SELECT company_id FROM users WHERE id = ?)
    ")->execute([$registro['user_id']]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    exibirErro("Erro interno", "Não foi possível ativar sua conta. Tente novamente ou entre em contato com o suporte.");
}

exibirSucesso($registro['name'], $registro['email'], false);

// ── Funções de resposta visual ────────────────────────────────

function exibirSucesso(string $nome, string $email, bool $jaAtivado): void {
    $titulo  = $jaAtivado ? "Conta já ativa" : "E-mail confirmado!";
    $msg     = $jaAtivado
        ? "Sua conta já estava ativa. Você pode fazer login normalmente."
        : "Sua conta foi ativada com sucesso. Bem-vindo(a) ao Re.Source!";
    $icone   = "✅";
    $cor     = "#157347";
    renderPagina($icone, $titulo, $msg, $nome, $email, $cor, "/login.php", "Fazer login agora");
}

function exibirErro(string $titulo, string $msg): void {
    renderPagina("❌", $titulo, $msg, '', '', "#dc3545", "/cadastro.php", "Voltar ao cadastro");
}

function renderPagina(string $icone, string $titulo, string $msg, string $nome, string $email, string $cor, string $btnHref, string $btnLabel): void {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= htmlspecialchars($titulo) ?> — Re.Source</title>
      <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="/FrontEnd/css/style.css">
      <style>
        body { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f4f7f4; font-family:Inter,sans-serif; padding:2rem; }
        .card { background:#fff; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); padding:3rem 2.5rem; max-width:440px; width:100%; text-align:center; }
        .icone { font-size:3.5rem; margin-bottom:1rem; }
        h1 { font-family:Sora,sans-serif; color:#1a1a1a; margin:.5rem 0 1rem; font-size:1.6rem; }
        p { color:#555; line-height:1.7; margin-bottom:1.5rem; }
        p a { color:#157347; font-weight:600; }
        .email-tag { display:inline-block; background:#f0f7f3; color:#157347; border-radius:6px; padding:.3rem .8rem; font-size:.9rem; font-weight:600; margin-bottom:1.5rem; }
        .btn { display:inline-block; padding:.85rem 2rem; border-radius:8px; color:#fff; font-weight:700; text-decoration:none; font-size:1rem; }
        .logo { font-family:Sora,sans-serif; font-weight:800; color:#157347; font-size:1.3rem; margin-bottom:2rem; display:block; }
      </style>
    </head>
    <body>
      <div class="card">
        <span class="logo">Re.Source</span>
        <div class="icone"><?= $icone ?></div>
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <?php if ($nome): ?>
          <div class="email-tag"><?= htmlspecialchars($email) ?></div>
        <?php endif; ?>
        <p><?= $msg ?></p>
        <a href="<?= $btnHref ?>" class="btn" style="background:<?= $cor ?>;"><?= $btnLabel ?></a>
      </div>
    </body>
    </html>
    <?php
    exit;
}