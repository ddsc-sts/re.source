<?php

// BackEnd/auth/verificar.php — valida o token e cadastra o usuário no banco

session_start();
require_once __DIR__ . "/../config/conexao.php";

$token = trim($_GET['token'] ?? '');

// ── Sem token na URL ──────────────────────────────────────────
if (!$token) {
    exibirErro("Link inválido", "Este link de confirmação não existe ou é inválido.");
}

// ── Busca dados pendentes na sessão ──────────────────────────
$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!$pendente) {
    exibirErro("Sessão expirada", "Seus dados de cadastro expiraram. Por favor, <a href='/cadastro.php'>cadastre-se novamente</a>.");
}

// ── Valida o token ────────────────────────────────────────────
$tokenHash = hash('sha256', $token);

if (!hash_equals($pendente['token_hash'], $tokenHash)) {
    exibirErro("Link inválido", "Este link de confirmação não existe ou já foi removido.");
}

// ── Token expirado ────────────────────────────────────────────
if (time() > $pendente['expires_at']) {
    unset($_SESSION['cadastro_pendente']);
    exibirErro(
        "Link expirado",
        "Seu link de confirmação expirou. <a href='/cadastro.php'>Clique aqui para se cadastrar novamente.</a>"
    );
}

// ── Tudo certo — agora cadastra no banco ──────────────────────
$pdo->beginTransaction();
try {

    $pdo->prepare(
        "INSERT INTO addresses (zip_code, street, number, district, city, state)
         VALUES ('','','','','',?)"
    )->execute([$pendente['estado']]);
    $addressId = $pdo->lastInsertId();

    $nomeCompleto = $pendente['nome'] . ' ' . $pendente['sobrenome'];

    $pdo->prepare(
        "INSERT INTO companies (cnpj, razao_social, email, phone, address_id, plan_id, responsible_name, email_verified_at)
         VALUES (?,?,?,?,?,1,?,NOW())"
    )->execute([
        $pendente['cnpj'],
        $pendente['razao'],
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

    // E-mail ou CNPJ já cadastrado (usuário clicou no link duas vezes)
    if ($e->getCode() === '23000') {
        unset($_SESSION['cadastro_pendente']);
        exibirSucesso($pendente['nome'], $pendente['email'], true);
    }

    exibirErro("Erro interno", "Não foi possível ativar sua conta. Tente novamente ou entre em contato com o suporte.");
}

// ── Limpa sessão e mostra sucesso ─────────────────────────────
unset($_SESSION['cadastro_pendente']);
exibirSucesso($pendente['nome'], $pendente['email'], false);

// ── Funções de resposta visual ────────────────────────────────

function exibirSucesso(string $nome, string $email, bool $jaAtivado): void {
    $titulo = $jaAtivado ? "Conta já ativa" : "E-mail confirmado!";
    $msg    = $jaAtivado
        ? "Sua conta já estava ativa. Você pode fazer login normalmente."
        : "Sua conta foi ativada com sucesso. Bem-vindo(a) ao Re.Source!";
    renderPagina("✅", $titulo, $msg, $nome, $email, "#157347", "/login.php", "Fazer login agora");
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
        <?php if ($email): ?>
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