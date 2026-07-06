<?php
$titulo_pagina = 'Escolher frete — Re.Source';
$css_especifico = app_url('/public/css/freight.css');
require __DIR__ . '/../components/header.php';
$responsibleLabels = ['buyer' => 'comprador', 'seller' => 'vendedor', 'shared' => 'ambas as empresas'];
?>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">
<main class="dashboard-shell">
  <?php $sidebarActive = 'deliveries'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>
  <div class="freight-page">
  <section class="freight-hero">
    <div>
      <span class="eyebrow">Negociação <?= htmlspecialchars($negotiation['protocol_number'] ?? ('#' . $negotiation['id']), ENT_QUOTES, 'UTF-8') ?></span>
      <h1>Escolha o frete</h1>
      <p>Valores simulados para a apresentação. A opção escolhida ficará registrada na negociação.</p>
    </div>
    <a class="link-button secondary" href="<?= htmlspecialchars(app_url('/conversas/abrir?id=' . (int) $negotiation['id']), ENT_QUOTES, 'UTF-8') ?>">Voltar ao chat</a>
  </section>

  <section class="summary-card">
    <div><small>Material</small><strong><?= htmlspecialchars($negotiation['listing_title'], ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div><small>Origem</small><strong><?= htmlspecialchars($negotiation['seller_name'] . ($negotiation['origin_location'] ? ' · ' . $negotiation['origin_location'] : ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div><small>Destino</small><strong><?= htmlspecialchars($negotiation['buyer_name'] . ($negotiation['destination_location'] ? ' · ' . $negotiation['destination_location'] : ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div><small>Responsável</small><strong><?= htmlspecialchars($responsibleLabels[$negotiation['responsible_for_freight']] ?? 'comprador', ENT_QUOTES, 'UTF-8') ?></strong></div>
  </section>

  <?php if (!$canChoose): ?>
    <div class="notice">A outra empresa ficou responsável pelo frete no acordo. Você poderá acompanhar a entrega assim que ela escolher uma opção.</div>
  <?php endif; ?>

  <section class="quote-grid" aria-label="Opções de frete simulado">
    <?php foreach ($quotes as $index => $quote): ?>
      <article class="quote-card <?= $index === 1 ? 'featured' : '' ?>">
        <?php if ($index === 1): ?><span class="recommendation">Recomendado</span><?php endif; ?>
        <div class="quote-icon"><i data-lucide="truck"></i></div>
        <h2><?= htmlspecialchars($quote['service_name'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($quote['provider_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <strong class="quote-price">R$ <?= number_format((float) $quote['price'], 2, ',', '.') ?></strong>
        <ul>
          <li><i data-lucide="calendar-clock"></i> Até <?= (int) $quote['delivery_days'] ?> dias úteis</li>
          <li><i data-lucide="map-pin"></i> Rastreamento incluído</li>
          <li><i data-lucide="shield-check"></i> Confirmação por código</li>
        </ul>
        <form method="post" action="<?= htmlspecialchars(app_url('/frete/contratar'), ENT_QUOTES, 'UTF-8') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
          <input type="hidden" name="quote_id" value="<?= (int) $quote['id'] ?>">
          <button type="submit" <?= !$canChoose ? 'disabled' : '' ?>>Escolher esta opção</button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>
  </div>
</main>
<script>if (window.lucide) lucide.createIcons();</script>
<?php require __DIR__ . '/../components/footer.php'; ?>
