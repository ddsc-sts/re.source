<?php
$titulo_pagina = 'Acompanhar frete — Re.Source';
$css_especifico = app_url('/public/css/freight.css');
require __DIR__ . '/../components/header.php';
$companyId = (int) ($_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? 0);
$isBuyer = $companyId === (int) $negotiation['buyer_company_id'];
$steps = [
  'contracted' => 1, 'preparing' => 1, 'in_transit' => 2,
  'out_for_delivery' => 3, 'delivered' => 4, 'concluded' => 4,
];
$currentStep = $steps[$freight['status']] ?? 0;
$statusLabels = [
  'contracted' => 'Frete contratado', 'preparing' => 'Preparando coleta',
  'in_transit' => 'Em transporte', 'out_for_delivery' => 'Saiu para entrega',
  'delivered' => 'Entregue', 'concluded' => 'Entrega confirmada', 'cancelled' => 'Cancelado',
];
?>
<main class="internal-page-shell">
  <div class="freight-page">
  <section class="freight-hero">
    <div>
      <span class="eyebrow">Rastreamento <?= htmlspecialchars($freight['tracking_code'], ENT_QUOTES, 'UTF-8') ?></span>
      <h1><?= htmlspecialchars($statusLabels[$freight['status']] ?? $freight['status'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($freight['carrier_company_name'] . ' · ' . $freight['service_name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a class="link-button secondary" href="<?= htmlspecialchars(app_url('/entregas'), ENT_QUOTES, 'UTF-8') ?>">Minhas entregas</a>
  </section>

  <?php if ($deliveryCode): ?>
    <section class="delivery-code" role="alert">
      <span>Código de confirmação — exibido apenas agora</span>
      <strong><?= htmlspecialchars($deliveryCode, ENT_QUOTES, 'UTF-8') ?></strong>
      <p>Informe este código ao entregador somente depois de receber e conferir o material. Ele expira em 24 horas.</p>
    </section>
  <?php endif; ?>

  <section class="tracking-card">
    <div class="timeline">
      <?php foreach ([1 => ['package-check', 'Contratado'], 2 => ['truck', 'Em transporte'], 3 => ['map-pin', 'Saiu para entrega'], 4 => ['badge-check', 'Confirmado']] as $number => [$icon, $label]): ?>
        <div class="timeline-step <?= $currentStep >= $number ? 'done' : '' ?>">
          <span><i data-lucide="<?= $icon ?>"></i></span><strong><?= $label ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="tracking-details">
      <div><small>Material</small><strong><?= htmlspecialchars($negotiation['listing_title'], ENT_QUOTES, 'UTF-8') ?></strong></div>
      <div><small>Trajeto</small><strong><?= htmlspecialchars($negotiation['seller_name'] . ' → ' . $negotiation['buyer_name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
      <div><small>Previsão</small><strong><?= $freight['estimated_delivery'] ? date('d/m/Y', strtotime($freight['estimated_delivery'])) : 'A confirmar' ?></strong></div>
      <div><small>Total do frete</small><strong>R$ <?= number_format((float) $freight['total_value'], 2, ',', '.') ?></strong></div>
    </div>
  </section>

  <?php if (!empty($statusHistory)): ?>
    <section class="freight-history" aria-label="Histórico detalhado do frete">
      <div class="freight-history-heading"><div><span class="eyebrow">Atualizações</span><h2>Histórico do frete</h2></div><i data-lucide="history"></i></div>
      <ol>
        <?php foreach (array_reverse($statusHistory) as $event): ?>
          <li>
            <span class="history-marker"><i data-lucide="<?= $event['status'] === 'concluded' ? 'badge-check' : ($event['status'] === 'out_for_delivery' ? 'key-round' : 'truck') ?>"></i></span>
            <div><strong><?= htmlspecialchars($statusLabels[$event['status']] ?? ucfirst($event['status']), ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></p></div>
            <time datetime="<?= htmlspecialchars($event['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('d/m/Y H:i', strtotime($event['created_at'])) ?></time>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>

  <section class="freight-actions">
    <?php if (in_array($freight['status'], ['contracted', 'preparing'], true)): ?>
      <form method="post" action="<?= htmlspecialchars(app_url('/frete/iniciar'), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
        <button type="submit"><i data-lucide="truck"></i> Simular início do transporte</button>
      </form>
    <?php elseif ($isBuyer && in_array($freight['status'], ['in_transit', 'out_for_delivery'], true)): ?>
      <form method="post" action="<?= htmlspecialchars(app_url('/frete/codigo'), ENT_QUOTES, 'UTF-8') ?>">
        <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
        <button type="submit"><i data-lucide="key-round"></i> Gerar código de entrega</button>
      </form>
      <p>O novo código substitui qualquer código anterior e poderá ser usado uma única vez.</p>
    <?php elseif ($freight['status'] === 'out_for_delivery'): ?>
      <p>Aguardando o comprador fornecer o código ao entregador.</p>
    <?php elseif ($freight['status'] === 'concluded'): ?>
      <div class="success-message delivery-complete-animation"><i data-lucide="circle-check-big"></i> Entrega concluída e saldo liberado ao vendedor.</div>
    <?php endif; ?>
  </section>
  </div>
</main>
<script>if (window.lucide) lucide.createIcons();</script>
<?php require __DIR__ . '/../components/footer.php'; ?>
