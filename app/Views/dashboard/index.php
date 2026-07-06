<?php
$titulo_pagina = $titulo_pagina ?? 'Re.Source — Economia Circular em Joinville';
require_once __DIR__ . '/../components/header.php';
?>

<!-- ══ HERO ══ -->
<section class="hero" aria-label="Banner principal">
  <div class="slides-container" id="slider">

    <div class="slide active">
      <img class="bg" src="https://images.unsplash.com/photo-1766246719951-0d21674dd9b2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Fábrica sustentável" />
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="slide-text">
          <h1>Transforme o resíduo da sua indústria em receita.</h1>
          <p>Conectamos empresas para uma economia circular em Joinville.</p>
          <div class="esg-badge">
            <div class="esg-dot"></div>
            <span class="label">Economia Sustentável</span>
            <span class="tag">ESG</span>
          </div>
        </div>
      </div>
    </div>

    <div class="slide">
      <img class="bg" src="https://images.unsplash.com/photo-1748944079305-8d2a86e7ad32?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Trabalhadores em fábrica" />
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="slide-text">
          <h1>Economia Sustentável e Responsável</h1>
          <p>Juntos construímos um futuro mais verde para a indústria.</p>
          <div class="esg-badge">
            <div class="esg-dot"></div>
            <span class="label">Economia Sustentável</span>
            <span class="tag">ESG</span>
          </div>
        </div>
      </div>
    </div>

    <div class="slide">
      <img class="bg" src="https://images.unsplash.com/photo-1772544386001-637f7b34a24c?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Materiais industriais recicláveis" />
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="slide-text">
          <h1>Reduza Custos com Materiais Reutilizáveis</h1>
          <p>Encontre os resíduos industriais que você precisa.</p>
          <div class="esg-badge">
            <div class="esg-dot"></div>
            <span class="label">Economia Sustentável</span>
            <span class="tag">ESG</span>
          </div>
        </div>
      </div>
    </div>

    <div class="slide">
      <img class="bg" src="https://images.unsplash.com/photo-1646803101279-d1a2461a5eb6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080" alt="Economia circular" />
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="slide-text">
          <h1>Conectando Indústrias, Gerando Valor</h1>
          <p>Sua plataforma B2B de economia circular.</p>
          <div class="esg-badge">
            <div class="esg-dot"></div>
            <span class="label">Economia Sustentável</span>
            <span class="tag">ESG</span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="hero-dots" id="heroDots">
    <button class="hero-dot active" data-index="0" aria-label="Slide 1"></button>
    <button class="hero-dot" data-index="1" aria-label="Slide 2"></button>
    <button class="hero-dot" data-index="2" aria-label="Slide 3"></button>
    <button class="hero-dot" data-index="3" aria-label="Slide 4"></button>
  </div>
</section>

<!-- ══ CATEGORIAS + PAINEL DE CIRCULARIDADE ══ -->
<section class="category-section">
  <h2 class="section-title">Explore por Categoria</h2>

  <div class="category-layout">

    <div class="category-grid">

      <a href="/re.source/busca?category_id=4" class="category-card">
        <img src="https://images.unsplash.com/photo-1759300635757-19ab99f4cfed?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Madeira" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">MADEIRA</div>
      </a>

      <a href="/re.source/busca?category_id=3" class="category-card">
        <img src="https://images.unsplash.com/photo-1606037150583-fb842a55bae7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Plástico" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">PLÁSTICO</div>
      </a>

      <a href="/re.source/busca?category_id=1" class="category-card">
        <img src="https://images.unsplash.com/photo-1758264629814-44559c99e506?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Têxtil" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">TÊXTIL</div>
      </a>

      <a href="/re.source/busca?category_id=2" class="category-card">
        <img src="https://images.unsplash.com/photo-1722695510527-cc033e43be1b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Metal" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">METAL</div>
      </a>

      <a href="/re.source/busca?category_id=5" class="category-card">
        <img src="https://images.unsplash.com/photo-1719600804011-3bff3909b183?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Papelão" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">PAPELÃO</div>
      </a>

      <a href="/re.source/busca?category_id=7" class="category-card">
        <img src="https://images.unsplash.com/photo-1761765030682-26f51cfbc034?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Borracha" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">BORRACHA</div>
      </a>

      <a href="/re.source/busca?category_id=8" class="category-card">
        <img src="https://images.unsplash.com/photo-1759500657339-6e11b99a8882?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Eletrônicos" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">ELETRÔNICOS</div>
      </a>

      <a href="/re.source/busca?category_id=6" class="category-card">
        <img src="https://images.unsplash.com/photo-1646803101279-d1a2461a5eb6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Vidro" />
        <div class="category-overlay"></div>
        <div class="category-hover-tint"></div>
        <div class="category-name">VIDRO</div>
      </a>

    </div>

    <!-- Painel de Circularidade — inspirado na Imagem 2 (dashboard),
         com a paleta verde-escura da marca em vez de preto/lime -->
    <aside class="circularity-panel" aria-label="Perfil de circularidade da plataforma">
      <div class="circularity-header">
        <span class="circularity-title">Perfil de Circularidade</span>
        <i data-lucide="recycle" style="width:20px;height:20px;color:var(--accent-mint)"></i>
      </div>

      <div class="circularity-metric">
        <div class="circularity-metric-label">
          <span>Taxa de Reaproveitamento</span>
          <strong><?= (int) $circularityRate ?>%</strong>
        </div>
        <div class="circularity-bar">
          <div class="circularity-fill" style="width: <?= (int) $circularityRate ?>%"></div>
        </div>
      </div>

      <div class="circularity-badge">
        <i data-lucide="shield-check" style="width:14px;height:14px"></i>
        Conformidade ESG: <?= htmlspecialchars($circularityStatus, ENT_QUOTES, 'UTF-8') ?>
      </div>

      <br>

      <a href="/re.source/relatorios" class="circularity-link">
        Relatórios completos <i data-lucide="arrow-right"></i>
      </a>
    </aside>

  </div>
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