<?php
$titulo_pagina = 'Meus Anúncios — Re.Source';
$hideSearchBar = true;
$css_especifico = asset_url('/css/meus-anuncios.css');
$unitLabels = ['kg'=>'kg','ton'=>'t','m2'=>'m²','m3'=>'m³','unidade'=>'un.','litro'=>'L','outro'=>''];
$statusLabels = [
  'draft'=>'Rascunho','active'=>'Anúncio ativo','paused'=>'Pausado','sold'=>'Negociado','expired'=>'Expirado','removed'=>'Removido'
];
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">

<main class="dashboard-shell listings-dashboard">
  <?php $sidebarActive = 'listings'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

  <section class="dashboard-content my-listings-content">
    <header class="my-listings-header">
      <div><span>Área da empresa</span><h1>Meus Anúncios</h1><p>Gerencie ofertas e demandas publicadas pela sua empresa.</p></div>
      <a class="new-listing-button" href="<?= htmlspecialchars(app_url('/anuncios/novo'), ENT_QUOTES, 'UTF-8') ?>">Novo anúncio <i data-lucide="plus"></i></a>
    </header>

    <?= $mensagem ?? '' ?>

    <?php if (empty($anuncios)): ?>
      <div class="my-listings-empty">
        <i data-lucide="clipboard-plus"></i>
        <h2>Você ainda não possui anúncios</h2>
        <p>Publique o primeiro material para iniciar uma negociação.</p>
        <a href="<?= htmlspecialchars(app_url('/anuncios/novo'), ENT_QUOTES, 'UTF-8') ?>">Criar anúncio</a>
      </div>
    <?php else: ?>
      <div class="my-listings-list">
        <?php foreach ($anuncios as $anuncio):
          $status = (string) ($anuncio['status'] ?? 'draft');
          $quantity = (float) ($anuncio['quantity'] ?? 0);
          $image = $anuncio['main_image'] ?: asset_url('/img/logos/logo.png');
        ?>
          <article class="my-listing-card">
            <div class="my-listing-image">
              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($anuncio['title'], ENT_QUOTES, 'UTF-8') ?>">
              <span><?= ($anuncio['type'] ?? '') === 'demand' ? 'Demanda' : 'Oferta' ?></span>
            </div>
            <div class="my-listing-body">
              <div class="my-listing-title-row">
                <div><h2><?= htmlspecialchars($anuncio['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                  <div class="my-listing-meta">
                    <span><i data-lucide="shapes"></i><?= htmlspecialchars($anuncio['category_name'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?></span>
                    <span><i data-lucide="package"></i><?= number_format($quantity, $quantity == floor($quantity) ? 0 : 3, ',', '.') ?> <?= htmlspecialchars($unitLabels[$anuncio['unit']] ?? $anuncio['unit'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span><i data-lucide="map-pin"></i><?= htmlspecialchars(($anuncio['location_city'] ?? '') . '/' . ($anuncio['location_state'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                </div>
                <div class="my-listing-price">
                  <small><?= ($anuncio['type'] ?? '') === 'demand' ? 'Demanda' : 'Preço' ?></small>
                  <strong><?= ($anuncio['price'] ?? null) !== null ? 'R$ ' . number_format((float) $anuncio['price'], 2, ',', '.') : 'Negociável' ?></strong>
                </div>
              </div>
              <div class="my-listing-footer">
                <span class="listing-status status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><i></i><?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                <div class="my-listing-actions">
                  <a class="icon-action" href="<?= htmlspecialchars(app_url('/anuncios/editar?id=' . (int) $anuncio['id']), ENT_QUOTES, 'UTF-8') ?>" aria-label="Editar anúncio"><i data-lucide="pencil"></i></a>
                  <form action="<?= htmlspecialchars(app_url('/anuncios/excluir'), ENT_QUOTES, 'UTF-8') ?>" method="post" onsubmit="return confirm('Excluir este anúncio? Esta ação não pode ser desfeita.');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $anuncio['id'] ?>">
                    <button class="icon-action danger" type="submit" aria-label="Excluir anúncio"><i data-lucide="trash-2"></i></button>
                  </form>
                  <a class="details-action" href="<?= htmlspecialchars(app_url('/anuncio?id=' . (int) $anuncio['id']), ENT_QUOTES, 'UTF-8') ?>">Ver detalhes</a>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
