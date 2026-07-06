<?php
$titulo_pagina = $titulo_pagina ?? 'Re.Source — Painel Industrial';

/* Dados do painel de circularidade — placeholders com fallback.
   Troque pelas variáveis reais assim que o backend calcular esses valores. */
$circularityRate   = $circularityRate   ?? 78;
$circularityStatus = $circularityStatus ?? 'A+ Verified';

/* Atividade recente — placeholder com fallback, no mesmo padrão acima.
   Substitua por uma consulta real (ex.: log de negociações/anúncios)
   assim que existir uma fonte de dados para isso. */
$recentActivity = $recentActivity ?? [
    ['type' => 'highlight', 'title' => 'Lance Aceito', 'text' => "Seu lance foi aceito por um comprador.", 'time' => 'Há 2 horas'],
    ['type' => 'normal',    'title' => 'Novo Anúncio', 'text' => 'Um novo anúncio foi publicado na sua categoria.', 'time' => 'Há 5 horas'],
    ['type' => 'highlight', 'title' => 'Documentação', 'text' => 'Certificado de desvio de aterro disponível.', 'time' => 'Ontem'],
];

/* Esconde a barra de busca do header nesta página (é um painel logado,
   não a home pública) sem remover o HTML/JS da busca — só oculta via CSS. */
$hideSearchBar = true;

require_once __DIR__ . '/../components/header.php';
?>

<main class="dash-home">
  <div class="dash-home-grid">

    <!-- ══ COLUNA PRINCIPAL ══ -->
    <div class="dash-home-main">

      <!-- Catálogo por Categoria -->
      <section class="dash-home-section">
        <div class="dash-home-section-head">
          <h2>Catálogo por Categoria</h2>
          <a href="/re.source/busca">Ver todos <i data-lucide="chevron-right"></i></a>
        </div>

        <div class="catalog-grid">
          <a href="/re.source/busca?category_id=4" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1759300635757-19ab99f4cfed?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Madeira" />
            </div>
            <div class="catalog-card-body">
              <h3>Madeira</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=3" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1606037150583-fb842a55bae7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Plástico" />
            </div>
            <div class="catalog-card-body">
              <h3>Plástico</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=1" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1758264629814-44559c99e506?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Têxtil" />
            </div>
            <div class="catalog-card-body">
              <h3>Têxtil</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=2" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1722695510527-cc033e43be1b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Metal" />
            </div>
            <div class="catalog-card-body">
              <h3>Metal</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=5" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1719600804011-3bff3909b183?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Papelão" />
            </div>
            <div class="catalog-card-body">
              <h3>Papelão</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=7" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1761765030682-26f51cfbc034?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Borracha" />
            </div>
            <div class="catalog-card-body">
              <h3>Borracha</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=8" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1759500657339-6e11b99a8882?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Eletrônicos" />
            </div>
            <div class="catalog-card-body">
              <h3>Eletrônicos</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>

          <a href="/re.source/busca?category_id=6" class="catalog-card">
            <div class="catalog-card-img">
              <img src="https://images.unsplash.com/photo-1646803101279-d1a2461a5eb6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600" alt="Vidro" />
            </div>
            <div class="catalog-card-body">
              <h3>Vidro</h3>
              <p>Ver materiais disponíveis</p>
              <i data-lucide="arrow-right"></i>
            </div>
          </a>
        </div>
      </section>

      <!-- Recomendado para Você / Anúncios Recentes -->
      <section class="dash-home-section">
        <div class="dash-home-section-head">
          <h2>Recomendado para Você</h2>
          <a href="/re.source/busca">Ver todos <i data-lucide="chevron-right"></i></a>
        </div>

        <?php if (empty($recentListings)): ?>
          <div class="empty-state">
            <i data-lucide="package-open" style="width:40px;height:40px;margin-bottom:1rem;opacity:.5"></i>
            <p>Nenhum anúncio disponível no momento.</p>
            <a href="/re.source/anuncios/novo" class="btn-view first-listing">Seja o primeiro a anunciar</a>
          </div>
        <?php else: ?>
          <div class="reco-grid">
            <?php foreach ($recentListings as $item): ?>
              <article class="reco-card">
                <div class="reco-card-img">
                  <img src="<?= htmlspecialchars($item['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                       alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                  <span class="reco-tag <?= $item['type'] === 'offer' ? 'reco-tag-offer' : 'reco-tag-demand' ?>">
                    <?= $item['type'] === 'offer' ? 'Venda' : 'Procura' ?>
                  </span>
                </div>
                <div class="reco-card-body">
                  <div class="reco-card-top">
                    <h4><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <?php if ($item['type'] === 'offer' && $item['price'] !== null): ?>
                      <span class="reco-price"><?= (float) $item['price'] > 0 ? 'R$ ' . number_format((float) $item['price'], 2, ',', '.') : 'Doação' ?></span>
                    <?php else: ?>
                      <span class="reco-price reco-price-demand">Busca</span>
                    <?php endif; ?>
                  </div>
                  <p class="reco-meta">
                    <?php
                    $quantity = (float) $item['quantity'];
                    $formattedQuantity = $quantity == floor($quantity)
                        ? number_format($quantity, 0, ',', '.')
                        : number_format($quantity, 3, ',', '.');
                    echo 'Qtd: ' . $formattedQuantity . ' ' . htmlspecialchars($unitLabel[$item['unit']] ?? $item['unit']);
                    ?>
                    | <?= htmlspecialchars($item['location_city'] . ' - ' . $item['location_state'], ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <a href="/re.source/anuncio?id=<?= (int) $item['id'] ?>" class="btn-view reco-btn">Ver Detalhes</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    </div>

    <!-- ══ COLUNA LATERAL ══ -->
    <div class="dash-home-side">

      <!-- Painel de Circularidade -->
      <aside class="circularity-panel" aria-label="Perfil de circularidade da plataforma">
        <div class="circularity-header">
          <span class="circularity-title">Perfil de Circularidade</span>
          <i data-lucide="recycle" style="width:20px;height:20px;color:var(--accent-lime)"></i>
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

      <!-- Atividade Recente -->
      <aside class="activity-panel" aria-label="Atividade recente">
        <h3>Atividade Recente</h3>
        <ul class="activity-list">
          <?php foreach ($recentActivity as $activity): ?>
            <li class="activity-item activity-<?= htmlspecialchars($activity['type'], ENT_QUOTES, 'UTF-8') ?>">
              <span class="activity-dot"></span>
              <div>
                <p class="activity-title"><?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="activity-text"><?= htmlspecialchars($activity['text'], ENT_QUOTES, 'UTF-8') ?></p>
                <span class="activity-time"><?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <a href="/re.source/estatisticas" class="btn-outline activity-history-btn">Ver histórico total</a>
      </aside>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>