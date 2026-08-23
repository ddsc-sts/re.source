<?php
$statusLabels = [
    'open' => 'Conversa aberta', 'proposal_sent' => 'Proposta enviada',
    'buyer_accepted' => 'Comprador aceitou', 'seller_accepted' => 'Vendedor aceitou',
    'accepted' => 'Acordo confirmado', 'awaiting_freight' => 'Aguardando frete',
    'shipping' => 'Em transporte', 'delivered' => 'Entregue',
    'concluded' => 'Concluída', 'cancelled' => 'Cancelada',
];
$statusClasses = [
    'open' => 'pending', 'proposal_sent' => 'review', 'buyer_accepted' => 'partial',
    'seller_accepted' => 'partial', 'accepted' => 'active', 'awaiting_freight' => 'active',
    'shipping' => 'active', 'delivered' => 'active', 'concluded' => 'active', 'cancelled' => 'rejected',
];
$freightLabels = ['buyer' => 'Comprador', 'seller' => 'Vendedor', 'shared' => 'Dividido'];
$m = $negotiationMetrics ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RE.SOURCE — Negociações</title>
<link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/admin-dashboard.css'), ENT_QUOTES, 'UTF-8') ?>" />
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/empresas.css'), ENT_QUOTES, 'UTF-8') ?>" />
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/admin-negotiations.css'), ENT_QUOTES, 'UTF-8') ?>" />
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/admin-v2.css'), ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body>
<header class="site-header">
  <?php require_once __DIR__ . '/../../components/topbar.php'; ?>
  <?php require_once __DIR__ . '/../../components/navbar.php'; ?>
</header>

<main class="admin-main"><div class="admin-content">
  <section class="dash-hero">
    <div class="hero-left">
      <span class="hero-breadcrumb"><i data-lucide="handshake"></i> Gestão • Negociações</span>
      <h1 class="hero-title">Negociações da plataforma</h1>
      <p class="hero-subtitle">Acompanhe propostas e acordos entre empresas sem interferir nas decisões comerciais.</p>
      <div class="hero-meta">
        <span class="hero-meta-item"><i data-lucide="messages-square"></i> <?= (int) ($m['em_andamento'] ?? 0) ?> em andamento</span>
        <span class="hero-meta-item"><i data-lucide="badge-check"></i> <?= (int) ($m['acordos'] ?? 0) ?> acordos</span>
        <span class="hero-meta-item"><i data-lucide="circle-x"></i> <?= (int) ($m['canceladas'] ?? 0) ?> canceladas</span>
      </div>
    </div>
    <div class="hero-right"><div class="hero-badge-num"><?= (int) ($m['total'] ?? 0) ?></div><div class="hero-badge-label">Total</div><div class="hero-badge-sub">Negociações registradas</div></div>
  </section>

  <section class="negotiation-summary">
    <article><i data-lucide="circle-dollar-sign"></i><div><span>Volume acordado</span><strong>R$ <?= number_format((float) ($m['volume'] ?? 0), 2, ',', '.') ?></strong></div></article>
    <article><i data-lucide="handshake"></i><div><span>Acordos confirmados</span><strong><?= (int) ($m['acordos'] ?? 0) ?></strong></div></article>
    <article><i data-lucide="activity"></i><div><span>Em andamento</span><strong><?= (int) ($m['em_andamento'] ?? 0) ?></strong></div></article>
  </section>

  <section class="company-filters"><form method="GET" action="<?= htmlspecialchars(app_url('/admin/negociacoes'), ENT_QUOTES, 'UTF-8') ?>">
    <label><span>Buscar</span><input type="search" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Empresa, anúncio ou protocolo" /></label>
    <label><span>Status</span><select name="status"><option value="">Todos os status</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $value ?>" <?= ($statusFilter ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
    <button class="btn-primary" type="submit"><i data-lucide="search"></i> Filtrar</button>
    <?php if (($search ?? '') !== '' || ($statusFilter ?? '') !== ''): ?><a class="filter-clear" href="<?= htmlspecialchars(app_url('/admin/negociacoes'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a><?php endif; ?>
  </form></section>

  <div class="card"><div class="card-header"><div><div class="card-title">Negociações encontradas</div><div class="card-sub"><?= count($negociacoes ?? []) ?> resultado(s) • consulta somente leitura</div></div></div>
    <div class="table-wrapper"><table class="admin-table"><thead><tr><th>Negociação</th><th>Empresas</th><th>Proposta</th><th>Status</th><th>Atualização</th><th style="text-align:right">Detalhes</th></tr></thead><tbody>
    <?php if (empty($negociacoes)): ?><tr><td colspan="6" class="empty-table">Nenhuma negociação encontrada.</td></tr>
    <?php else: foreach ($negociacoes as $n): ?>
      <?php
      $buyerName = $n['comprador_fantasia'] ?: $n['comprador'];
      $sellerName = $n['vendedor_fantasia'] ?: $n['vendedor'];
      $status = (string) $n['status'];
      $detail = [
        'id' => (int) $n['id'], 'protocol' => $n['protocol_number'] ?: 'Ainda não gerado',
        'listing' => $n['anuncio'], 'buyer' => $buyerName, 'seller' => $sellerName,
        'status' => $statusLabels[$status] ?? $status, 'quantity' => $n['quantity'] ?? $n['proposed_quantity'],
        'unit' => $n['unit'], 'unit_price' => $n['unit_price'] ?? $n['proposed_price'],
        'total' => $n['total_price'] ?? $n['proposed_total'], 'deadline' => $n['delivery_deadline'],
        'freight' => $freightLabels[$n['responsible_for_freight']] ?? 'Não definido',
        'notes' => $n['notes'], 'buyer_accepted' => $n['buyer_accepted_at'],
        'seller_accepted' => $n['seller_accepted_at'], 'reason' => $n['refusal_reason'] ?: ($n['proposal_cancel_reason'] ?: $n['cancel_reason']),
        'created_at' => $n['created_at'], 'agreement_at' => $n['agreement_at'],
      ];
      ?>
      <tr>
        <td><strong>#<?= (int) $n['id'] ?> · <?= htmlspecialchars($n['anuncio'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong><small class="table-secondary"><?= htmlspecialchars($n['protocol_number'] ?: 'Sem protocolo', ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><span class="party buyer">Compra: <?= htmlspecialchars($buyerName ?? '-', ENT_QUOTES, 'UTF-8') ?></span><span class="party seller">Venda: <?= htmlspecialchars($sellerName ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><strong>R$ <?= number_format((float) ($n['total_price'] ?? $n['proposed_total'] ?? 0), 2, ',', '.') ?></strong><small class="table-secondary"><?= number_format((float) ($n['quantity'] ?? $n['proposed_quantity'] ?? 0), 3, ',', '.') ?> <?= htmlspecialchars($n['unit'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
        <td><span class="status-badge <?= $statusClasses[$status] ?? 'pending' ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= date('d/m/Y H:i', strtotime($n['updated_at'] ?? $n['created_at'])) ?></td>
        <td><div class="action-buttons" style="justify-content:flex-end"><button class="btn-icon js-negotiation-detail" type="button" title="Visualizar negociação" data-detail="<?= htmlspecialchars(json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="eye"></i></button></div></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
  </div>
</div></main>

<dialog class="negotiation-dialog" id="negotiationDialog"><div class="negotiation-dialog-content">
  <button class="dialog-close" id="negotiationDialogClose" type="button" aria-label="Fechar">×</button>
  <span class="hero-breadcrumb"><i data-lucide="file-search"></i> Detalhes do acordo</span>
  <h2 id="detailTitle"></h2><p class="detail-status" id="detailStatus"></p>
  <div class="detail-grid" id="detailGrid"></div>
  <div class="detail-section"><h3>Aceites</h3><div class="acceptance-admin" id="detailAcceptances"></div></div>
  <div class="detail-section" id="detailNotesSection"><h3>Observações / motivo</h3><p id="detailNotes"></p></div>
</div></dialog>

<script src="<?= htmlspecialchars(app_url('/public/js/admin-negotiations.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>document.getElementById('menuToggle')?.addEventListener('click',()=>document.getElementById('navbar')?.classList.toggle('open'));lucide.createIcons();</script>
</body></html>
