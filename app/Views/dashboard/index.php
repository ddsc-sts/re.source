  <?php
  $titulo_pagina = $titulo_pagina ?? 'Re.Source — Economia Circular em Joinville';
  $hideSearchBar = true;
  $css_especifico = asset_url('/css/base-home.css');
  $categoryMedia = [
    'madeira' => 'https://images.unsplash.com/photo-1759300635757-19ab99f4cfed?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'plastico' => 'https://images.unsplash.com/photo-1606037150583-fb842a55bae7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'textil' => 'https://images.unsplash.com/photo-1758264629814-44559c99e506?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'metal' => 'https://images.unsplash.com/photo-1722695510527-cc033e43be1b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'papel-papelao' => 'https://images.unsplash.com/photo-1719600804011-3bff3909b183?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'borracha' => 'https://images.unsplash.com/photo-1761765030682-26f51cfbc034?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'eletronico' => 'https://images.unsplash.com/photo-1759500657339-6e11b99a8882?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
    'vidro' => 'https://images.unsplash.com/photo-1646803101279-d1a2461a5eb6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600',
  ];
  $categoryDescriptions = [
    'madeira' => 'Paletes, cavacos, biomassa e resíduos de processamento.',
    'plastico' => 'Polímeros, aparas e materiais para reciclagem técnica.',
    'textil' => 'Retalhos, fibras e tecidos industriais reaproveitáveis.',
    'metal' => 'Sucata ferrosa, não ferrosa, limalhas e componentes.',
    'papel-papelao' => 'Papel, embalagens e papelão para nova destinação.',
    'borracha' => 'Aparas, mantas, pneus e compostos elastoméricos.',
    'eletronico' => 'Componentes e equipamentos destinados à recuperação.',
    'vidro' => 'Cacos, embalagens e vidro industrial reaproveitável.',
  ];
  require_once __DIR__ . '/../components/header.php';
  ?>

  <!-- ══ HERO ══ -->
  <section class="hero" aria-label="Banner principal">
    <div class="slides-container" id="slider">

      <div class="slide active">
        <img class="bg" src="<?= htmlspecialchars(asset_url('/img/base-carousel/industria-circular.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Indústria circular com materiais preparados para reaproveitamento" />
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <div class="slide-text">
            <h1>Transforme o resíduo da sua indústria em receita.</h1>
            <p>Conectamos empresas para uma economia circular em Joinville.</p>
            <div class="esg-badge">
              <i data-lucide="leaf" aria-hidden="true"></i>
              <span class="label">Economia Sustentável</span>
              <span class="tag">ESG</span>
            </div>
          </div>
        </div>
      </div>

      <div class="slide">
        <img class="bg" src="<?= htmlspecialchars(asset_url('/img/base-carousel/triagem-industrial.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Equipe realizando triagem de materiais industriais" />
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <div class="slide-text">
            <h1>Economia Sustentável e Responsável</h1>
            <p>Juntos construímos um futuro mais verde para a indústria.</p>
            <div class="esg-badge">
              <i data-lucide="leaf" aria-hidden="true"></i>
              <span class="label">Economia Sustentável</span>
              <span class="tag">ESG</span>
            </div>
          </div>
        </div>
      </div>

      <div class="slide">
        <img class="bg" src="<?= htmlspecialchars(asset_url('/img/base-carousel/materiais-reutilizaveis.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Materiais industriais organizados para reutilização" />
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <div class="slide-text">
            <h1>Reduza Custos com Materiais Reutilizáveis</h1>
            <p>Encontre os resíduos industriais que você precisa.</p>
            <div class="esg-badge">
              <i data-lucide="leaf" aria-hidden="true"></i>
              <span class="label">Economia Sustentável</span>
              <span class="tag">ESG</span>
            </div>
          </div>
        </div>
      </div>

      <div class="slide">
        <img class="bg" src="<?= htmlspecialchars(asset_url('/img/base-carousel/logistica-circular.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logística conectando empresas na economia circular" />
        <div class="slide-overlay"></div>
        <div class="slide-content">
          <div class="slide-text">
            <h1>Conectando Indústrias, Gerando Valor</h1>
            <p>Sua plataforma B2B de economia circular.</p>
            <div class="esg-badge">
              <i data-lucide="leaf" aria-hidden="true"></i>
              <span class="label">Economia Sustentável</span>
              <span class="tag">ESG</span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <form class="base-hero-search" action="<?= htmlspecialchars(app_url('/busca'), ENT_QUOTES, 'UTF-8') ?>" method="get">
      <label><span>O que busca?</span><input type="search" name="q" placeholder="Ex: Cavacos de aço, polímeros..."></label>
      <label><span>Categoria</span><select name="category_id"><option value="">Todas as categorias</option><?php foreach ($baseCategories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <button type="submit">Buscar <i data-lucide="search"></i></button>
    </form>

    <div class="hero-dots" id="heroDots">
      <button class="hero-dot active" data-index="0" aria-label="Slide 1"></button>
      <button class="hero-dot" data-index="1" aria-label="Slide 2"></button>
      <button class="hero-dot" data-index="2" aria-label="Slide 3"></button>
      <button class="hero-dot" data-index="3" aria-label="Slide 4"></button>
    </div>
  </section>

  <!-- ══ CATEGORIAS + PAINEL DE CIRCULARIDADE ══ -->
  <section class="category-section">
    <div class="base-section-heading">
      <h2 class="section-title">Explore por Categoria</h2>
      <a href="<?= htmlspecialchars(app_url('/busca'), ENT_QUOTES, 'UTF-8') ?>">Ver catálogo completo <i data-lucide="external-link"></i></a>
    </div>

    <div class="category-layout">

      <div class="category-grid">
        <?php foreach ($baseCategories as $category):
          $slug = (string) $category['slug'];
          $image = $categoryMedia[$slug] ?? 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600';
        ?>
          <a href="<?= htmlspecialchars(app_url('/busca?category_id=' . (int) $category['id']), ENT_QUOTES, 'UTF-8') ?>" class="category-card">
            <div class="category-card-media">
              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>">
              <span><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="category-card-copy">
              <h3><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p><?= htmlspecialchars($categoryDescriptions[$slug] ?? 'Materiais industriais disponíveis para negociação.', ENT_QUOTES, 'UTF-8') ?></p>
              <div><strong><?= number_format((int) $category['listing_count'], 0, ',', '.') ?> anúncios</strong><i data-lucide="arrow-right"></i></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

    </div>

  </section>

  <section class="base-trust-strip" aria-label="Indicadores da plataforma">
    <span>Ecossistema industrial verificado</span>
    <strong><?= number_format((int) ($marketplaceStats['companies'] ?? 0), 0, ',', '.') ?> empresas</strong>
    <strong><?= number_format((int) ($marketplaceStats['listings'] ?? 0), 0, ',', '.') ?> anúncios ativos</strong>
    <strong><?= number_format((int) ($marketplaceStats['negotiations'] ?? 0), 0, ',', '.') ?> negociações</strong>
    <strong><?= number_format((int) ($marketplaceStats['deliveries'] ?? 0), 0, ',', '.') ?> entregas concluídas</strong>
  </section>

  <!-- ANÚNCIOS RECENTES -->
  <section class="listings-section">
    <div class="listings-inner">
      <div class="listings-header">
        <h2 class="listings-title">Anúncios Recentes</h2>
        <a href="/re.source/busca" class="btn-ver-todos">Ver todos <i data-lucide="arrow-right"></i></a>
      </div>

      <?php if (empty($recentListings)): ?>
        <div class="empty-state">
          <i data-lucide="package-open" style="width:48px;height:48px;margin-bottom:1rem;opacity:.5"></i>
          <p>Nenhum anúncio disponível no momento.</p>
          <a href="/re.source/anuncios/novo" class="btn-view first-listing">Seja o primeiro a anunciar</a>
        </div>
      <?php else: ?>
        <div class="listings-grid">
          <?php foreach ($recentListings as $item): ?>
            <article class="ad-card">
              <img src="<?= htmlspecialchars($item['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                  class="ad-image" loading="lazy">
              <div class="ad-content">
                <span class="badge <?= $item['type'] === 'offer' ? 'badge-offer' : 'badge-demand' ?>">
                  <?= $item['type'] === 'offer' ? 'Venda' : 'Procura' ?>
                </span>
                <h3 class="ad-title"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="ad-meta">
                  <span><i data-lucide="tag"></i> <?= htmlspecialchars($item['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span><i data-lucide="box"></i>
                    <?php
                    $quantity = (float) $item['quantity'];
                    $formattedQuantity = $quantity == floor($quantity)
                        ? number_format($quantity, 0, ',', '.')
                        : number_format($quantity, 3, ',', '.');
                    echo 'Qtd: ' . $formattedQuantity . ' ' . htmlspecialchars($unitLabel[$item['unit']] ?? $item['unit']);
                    ?>
                  </span>
                  <span><i data-lucide="map-pin"></i>
                    <?= htmlspecialchars($item['location_city'] . ' - ' . $item['location_state'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </div>
                <div class="ad-footer">
                  <?php if ($item['type'] === 'offer' && $item['price'] !== null): ?>
                    <span class="ad-price"><?= (float) $item['price'] > 0 ? 'R$ ' . number_format((float) $item['price'], 2, ',', '.') : 'Doação' ?></span>
                  <?php else: ?>
                    <span class="ad-price demand-price">Busca</span>
                  <?php endif; ?>
                  <a href="/re.source/anuncio?id=<?= (int) $item['id'] ?>" class="btn-view">Ver Detalhes</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php require_once __DIR__ . '/../components/footer.php'; ?>
