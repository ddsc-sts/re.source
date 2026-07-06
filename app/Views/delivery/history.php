<?php
$titulo_pagina = 'Minhas entregas — Re.Source';
$css_especifico = app_url('/public/css/freight.css');
require __DIR__ . '/../components/header.php';
$statusLabels = ['contracted' => 'Contratado', 'preparing' => 'Preparando', 'in_transit' => 'Em transporte', 'out_for_delivery' => 'Saiu para entrega', 'delivered' => 'Entregue', 'concluded' => 'Concluído', 'cancelled' => 'Cancelado'];
?>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">
<main class="dashboard-shell">
  <?php $sidebarActive = 'deliveries'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>
  <div class="freight-page">
  <section class="freight-hero"><div><span class="eyebrow">Logística</span><h1>Minhas entregas</h1><p>Acompanhe os fretes das suas negociações.</p></div></section>
  <?php if (!$history): ?>
    <section class="empty-state"><i data-lucide="package-open"></i><h2>Nenhum frete por aqui</h2><p>Depois de um acordo no chat, o frete aparecerá nesta tela.</p><a class="link-button" href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>">Ver negociações</a></section>
  <?php else: ?>
    <section class="delivery-list">
      <?php foreach ($history as $item): ?>
        <article>
          <div class="quote-icon"><i data-lucide="truck"></i></div>
          <div class="delivery-main"><span><?= htmlspecialchars($item['protocol_number'] ?? ('Frete #' . $item['id']), ENT_QUOTES, 'UTF-8') ?></span><h2><?= htmlspecialchars($item['listing_title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($item['seller_name'] . ' → ' . $item['buyer_name'], ENT_QUOTES, 'UTF-8') ?></p></div>
          <div class="delivery-status"><span class="status-pill"><?= htmlspecialchars($statusLabels[$item['status']] ?? $item['status'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($item['tracking_code'], ENT_QUOTES, 'UTF-8') ?></strong></div>
          <a class="link-button" href="<?= htmlspecialchars(app_url('/frete/acompanhar?negociacao=' . (int) $item['negotiation_id']), ENT_QUOTES, 'UTF-8') ?>">Acompanhar</a>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
  </div>
</main>
<script>if (window.lucide) lucide.createIcons();</script>
<?php require __DIR__ . '/../components/footer.php'; ?>
