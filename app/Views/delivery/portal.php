<?php
$statusLabels = ['contracted' => 'Contratado', 'preparing' => 'Preparando', 'in_transit' => 'Em transporte', 'out_for_delivery' => 'Aguardando código', 'delivered' => 'Entregue'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logística — Re.Source Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/admin-dashboard.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/freight.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <header class="site-header"><?php require __DIR__ . '/../components/topbar.php'; require __DIR__ . '/../components/navbar.php'; ?></header>
  <?php require __DIR__ . '/../components/flash.php'; ?>
  <main class="admin-freight-page">
    <section class="admin-freight-heading"><div><span>OPERAÇÃO SIMULADA</span><h1>Portal de entregas</h1><p>Valide a entrega com o código de seis dígitos informado pelo comprador.</p></div><div class="admin-freight-count"><strong><?= count($freights) ?></strong><span>fretes ativos</span></div></section>
    <?php if (!$freights): ?>
      <section class="empty-state"><i data-lucide="truck"></i><h2>Nenhum frete em andamento</h2><p>Os fretes contratados aparecerão automaticamente aqui.</p></section>
    <?php else: ?>
      <section class="admin-freight-grid">
        <?php foreach ($freights as $freight): ?>
          <article class="admin-freight-card">
            <div class="admin-freight-card-head"><span class="status-pill"><?= htmlspecialchars($statusLabels[$freight['status']] ?? $freight['status'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($freight['tracking_code'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <h2><?= htmlspecialchars($freight['listing_title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($freight['seller_name'] . ' → ' . $freight['buyer_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <dl><div><dt>Transportadora</dt><dd><?= htmlspecialchars($freight['carrier_company_name'], ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Serviço</dt><dd><?= htmlspecialchars($freight['service_name'], ENT_QUOTES, 'UTF-8') ?></dd></div></dl>
            <?php if ($freight['status'] === 'out_for_delivery'): ?>
              <form class="delivery-validation" method="post" action="<?= htmlspecialchars(app_url('/entregador/validar'), ENT_QUOTES, 'UTF-8') ?>">
                <?= csrf_field() ?><input type="hidden" name="freight_id" value="<?= (int) $freight['id'] ?>">
                <label for="code-<?= (int) $freight['id'] ?>">Código de entrega</label>
                <div><input id="code-<?= (int) $freight['id'] ?>" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required><button type="submit">Confirmar entrega</button></div>
                <small><?= max(0, 5 - (int) $freight['delivery_code_attempts']) ?> tentativa(s) disponível(is)</small>
              </form>
            <?php else: ?>
              <div class="awaiting-code"><i data-lucide="clock-3"></i> Aguardando transporte e geração do código.</div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
  <script>if (window.lucide) lucide.createIcons();</script>
</body></html>
