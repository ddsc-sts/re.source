<?php
$companyName = $company['nome_fantasia'] ?: $company['razao_social'];
$notice = trim((string) ($_GET['aviso'] ?? ''));
$changesRequested = ($company['status'] ?? '') === 'changes_requested';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aguardando aprovação — Re.Source</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>">
  <style>
    :root { --green:#157347; --dark:#263238; --muted:#667085; --bg:#f2f7f4; --white:#fff; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; font-family:Inter,sans-serif; color:var(--dark); background:var(--bg); }
    .top { height:68px; padding:0 6vw; display:flex; align-items:center; justify-content:space-between; background:#fff; border-bottom:1px solid #e4ebe7; }
    .logo { font:700 1.35rem Sora,sans-serif; color:var(--dark); text-decoration:none; }
    .logo strong { color:var(--green); }
    .logout { color:#b42318; text-decoration:none; font-weight:600; font-size:.9rem; }
    main { width:min(920px, 90vw); margin:64px auto; }
    .card { background:var(--white); border:1px solid #dfe9e3; border-radius:20px; padding:42px; box-shadow:0 16px 45px rgba(21,115,71,.08); }
    .badge { display:inline-flex; padding:7px 12px; border-radius:999px; background:#fff7e6; color:#9a6700; font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; }
    h1 { font:700 clamp(1.7rem,4vw,2.5rem) Sora,sans-serif; margin:20px 0 12px; }
    .lead { color:var(--muted); line-height:1.7; max-width:680px; }
    .notice { margin-top:20px; padding:13px 16px; background:#fff7e6; border:1px solid #f7d58b; border-radius:10px; color:#805500; }
    .review { margin-top:22px; padding:20px; background:#fff8ed; border:1px solid #f5c56b; border-radius:14px; }
    .review h2 { margin:0 0 8px; font:700 1rem Sora,sans-serif; color:#8a4b08; }
    .review p { margin:0; color:#6f430f; line-height:1.6; white-space:pre-wrap; }
    .actions { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:32px; }
    .action { padding:22px; border:1px solid #dfe9e3; border-radius:14px; text-decoration:none; color:var(--dark); transition:.2s; }
    .action:hover { border-color:var(--green); transform:translateY(-2px); }
    .action strong { display:block; font-family:Sora,sans-serif; margin-bottom:7px; }
    .action span { color:var(--muted); font-size:.86rem; line-height:1.5; }
    .refresh { display:inline-block; margin-top:28px; padding:12px 20px; border-radius:10px; background:var(--green); color:white; text-decoration:none; font-weight:700; }
    .submit-review { display:inline-block; margin:28px 0 0 10px; padding:12px 20px; border:0; border-radius:10px; background:#9a6700; color:white; cursor:pointer; font:700 .9rem Inter,sans-serif; }
    @media(max-width:700px){ .card{padding:28px 22px}.actions{grid-template-columns:1fr}.top{padding:0 5vw} }
  </style>
</head>
<body>
  <?php require __DIR__ . '/../components/flash.php'; ?>
  <header class="top">
    <a class="logo" href="<?= htmlspecialchars(app_url('/'), ENT_QUOTES, 'UTF-8') ?>">Re.<strong>Source</strong></a>
    <a class="logout" href="<?= htmlspecialchars(app_url('/logout'), ENT_QUOTES, 'UTF-8') ?>">Sair</a>
  </header>
  <main>
    <section class="card">
      <span class="badge"><?= $changesRequested ? 'Correção solicitada' : 'Aguardando aprovação' ?></span>
      <h1><?= $changesRequested ? 'Precisamos revisar alguns dados' : 'Cadastro recebido' ?>, <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>.</h1>
      <p class="lead">
        <?= $changesRequested
          ? 'O administrador deixou uma orientação abaixo. Atualize os dados da empresa e reenvie o cadastro para uma nova análise.'
          : 'Sua empresa já pode navegar pelo catálogo e completar os dados da conta. Publicação, chat, negociação, frete e saque serão liberados assim que um administrador aprovar o cadastro.' ?>
      </p>
      <?php if ($changesRequested): ?>
        <div class="review">
          <h2>Orientação do administrador</h2>
          <p><?= htmlspecialchars((string) ($company['review_notes'] ?? 'Revise os dados cadastrais.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      <?php endif; ?>
      <?php if ($notice !== ''): ?>
        <div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="actions">
        <a class="action" href="<?= htmlspecialchars(app_url('/busca'), ENT_QUOTES, 'UTF-8') ?>"><strong>Explorar anúncios</strong><span>Conheça os materiais disponíveis.</span></a>
        <a class="action" href="<?= htmlspecialchars(app_url('/conta'), ENT_QUOTES, 'UTF-8') ?>"><strong><?= $changesRequested ? 'Corrigir cadastro' : 'Completar perfil' ?></strong><span>Revise os dados da sua empresa.</span></a>
        <a class="action" href="<?= htmlspecialchars(app_url('/configuracoes'), ENT_QUOTES, 'UTF-8') ?>"><strong>Configurações</strong><span>Ajuste tema e notificações.</span></a>
      </div>
      <a class="refresh" href="<?= htmlspecialchars(app_url('/aguardando-aprovacao'), ENT_QUOTES, 'UTF-8') ?>">Verificar aprovação</a>
      <?php if ($changesRequested): ?>
        <form method="POST" action="<?= htmlspecialchars(app_url('/cadastro/reenviar-analise'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline" onsubmit="return confirm('Você já corrigiu os dados solicitados e deseja reenviar o cadastro?')">
          <?= csrf_field() ?>
          <button class="submit-review" type="submit">Reenviar para análise</button>
        </form>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
