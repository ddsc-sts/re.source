<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?php echo $categoriaSelecionada ? htmlspecialchars($categoriaSelecionada) . ' — ' : ''; ?>
    Buscar Resíduos — Re.Source
  </title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/base.css" />
  <link rel="stylesheet" href="/re.source/public/css/search.css" />
</head>
<body>

<!-- ══ HEADER (igual ao da base) ══ -->
<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
      </div>

      <nav class="desktop-nav">
        <a href="/re.source/base"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/re.source/sobre"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="#"><i data-lucide="phone"></i> Contato</a>
      </nav>

      <div class="header-actions">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
          <i data-lucide="menu" class="icon-menu"></i>
          <i data-lucide="x"   class="icon-close"></i>
        </button>
      </div>

      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-label">Minha Conta</div>
        <a href="/re.source/conta" class="menu-btn">Detalhes da conta</a>
        <a href="#" class="menu-btn">Estatísticas</a>
        <a href="#" class="menu-btn">Configurações</a>
        <div class="dropdown-divider"></div>
        <button class="btn-announce">Anunciar Resíduo</button>
      </div>

    </div>
  </div>

  <div class="search-bar-wrap">
    <div class="search-bar-inner">
      <form class="search-pill" action="/re.source/busca" method="GET" id="headerSearchForm">
        <div class="search-field">
          <label>O que busca?</label>
          <input type="text" name="q" id="headerQ" placeholder="Ex: Serragem" value="<?php echo htmlspecialchars($q); ?>" />
        </div>

        <div class="category-field">
          <label>Categoria</label>
          <button type="button" class="category-trigger" id="categoryTrigger">
            <span id="categoryLabel"><?php echo $categoriaSelecionada ?? 'Todas as categorias'; ?></span>
            <i data-lucide="chevron-down"></i>
          </button>
          <div class="category-dropdown" id="categoryDropdown">
            <button type="button" onclick="selectCategory('', 'Todas as categorias')">Todas as categorias</button>
            <?php foreach ($categorias as $cat): ?>
              <button type="button" onclick="selectCategory('<?php echo $cat['id']; ?>', '<?php echo htmlspecialchars($cat['name']); ?>')">
                <?php echo htmlspecialchars($cat['name']); ?>
              </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="category_id" id="headerCategoryId" value="<?php echo htmlspecialchars($category_id ?? ''); ?>" />
        </div>

        <button type="submit" class="search-btn" id="headerSearchBtn"><i data-lucide="search"></i></button>
      </form>
    </div>
  </div>
</header>

<!-- ══ CONTEÚDO PRINCIPAL ══ -->
<main class="search-layout">

  <!-- ── SIDEBAR DE FILTROS ── -->
  <aside class="search-sidebar">
    <div class="sidebar-header">
      <i data-lucide="sliders-horizontal"></i>
      <span>Filtros</span>
    </div>

    <form action="/re.source/busca" method="GET" class="filters-form">

      <div class="filter-group">
        <label for="q">O que você procura?</label>
        <input
          type="text"
          id="q"
          name="q"
          value="<?php echo htmlspecialchars($q); ?>"
          placeholder="Ex: Paletes, Plástico..."
        />
      </div>

      <div class="filter-group">
        <label>Tipo de Anúncio</label>
        <div class="radio-group">
          <label class="radio-label <?php echo empty($type) ? 'active' : ''; ?>">
            <input type="radio" name="type" value="" <?php echo empty($type) ? 'checked' : ''; ?>>
            <span>Todos</span>
          </label>
          <label class="radio-label <?php echo $type === 'offer' ? 'active' : ''; ?>">
            <input type="radio" name="type" value="offer" <?php echo $type === 'offer' ? 'checked' : ''; ?>>
            <span>Ofertas</span>
          </label>
          <label class="radio-label <?php echo $type === 'demand' ? 'active' : ''; ?>">
            <input type="radio" name="type" value="demand" <?php echo $type === 'demand' ? 'checked' : ''; ?>>
            <span>Demandas</span>
          </label>
        </div>
      </div>

      <div class="filter-group">
        <label for="category_id">Categoria</label>
        <select id="category_id" name="category_id">
          <option value="">Todas as categorias</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo ((int)$category_id === (int)$cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-group">
        <label for="state">Estado</label>
        <select id="state" name="state" data-selected="<?php echo htmlspecialchars($state); ?>">
          <option value="">Todos os estados</option>
        </select>
      </div>

      <div class="filter-actions">
        <button type="submit" class="btn-filter">
          <i data-lucide="search"></i> Buscar
        </button>
        <a href="/re.source/busca" class="btn-clear">
          <i data-lucide="x"></i> Limpar
        </a>
      </div>

    </form>
  </aside>

  <!-- ── ÁREA DE RESULTADOS ── -->
  <section class="search-results">

    <div class="results-header">
      <div class="results-title">
        <?php if ($categoriaSelecionada): ?>
          <h1><?php echo htmlspecialchars($categoriaSelecionada); ?></h1>
        <?php elseif (!empty($q)): ?>
          <h1>Resultados para "<?php echo htmlspecialchars($q); ?>"</h1>
        <?php else: ?>
          <h1>Todos os Anúncios</h1>
        <?php endif; ?>
        <span class="results-count"><?php echo count($anuncios); ?> anúncio(s) encontrado(s)</span>
      </div>
    </div>

    <?php if (empty($anuncios)): ?>
      <div class="empty-state">
        <i data-lucide="search-x"></i>
        <h3>Nenhum resultado encontrado</h3>
        <p>Tente ajustar os filtros ou buscar por palavras diferentes.</p>
        <a href="/re.source/busca" class="btn-filter">Ver todos os anúncios</a>
      </div>

    <?php else: ?>
      <div class="listings-grid-search">
        <?php foreach ($anuncios as $ad): ?>
          <a href="/re.source/anuncio?id=<?php echo $ad['id']; ?>" class="listing-card-search">

            <div class="card-img-wrap">
              <?php if (!empty($ad['main_image'])): ?>
                <img src="<?php echo htmlspecialchars($ad['main_image']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" />
              <?php else: ?>
                <div class="card-img-placeholder">
                  <i data-lucide="image"></i>
                </div>
              <?php endif; ?>

              <span class="card-badge <?php echo $ad['type'] === 'demand' ? 'badge-demand' : ''; ?>">
                <?php echo $ad['type'] === 'offer' ? 'Oferta' : 'Demanda'; ?>
              </span>
            </div>

            <div class="card-body">
              <span class="card-category"><?php echo htmlspecialchars($ad['category_name']); ?></span>
              <h3 class="card-title"><?php echo htmlspecialchars($ad['title']); ?></h3>

              <div class="card-meta">
                <span>
                  <i data-lucide="box"></i>
                  <?php echo number_format((float)$ad['quantity'], 0, ',', '.') . ' ' . $ad['unit']; ?>
                </span>
                <?php if (!empty($ad['location_city'])): ?>
                  <span>
                    <i data-lucide="map-pin"></i>
                    <?php echo htmlspecialchars($ad['location_city'] . ' — ' . $ad['location_state']); ?>
                  </span>
                <?php endif; ?>
              </div>

              <div class="card-footer">
                <?php if ($ad['type'] === 'offer'): ?>
                  <span class="card-price">
                    <?php if ((float)$ad['price'] > 0): ?>
                      R$ <?php echo number_format((float)$ad['price'], 2, ',', '.'); ?>
                    <?php else: ?>
                      Doação
                    <?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="card-price demand">Procurando</span>
                <?php endif; ?>

                <span class="card-company">
                  <i data-lucide="building-2"></i>
                  <?php echo htmlspecialchars($ad['company_name'] ?? ''); ?>
                </span>
              </div>
            </div>

          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </section>

</main>

<script src="/re.source/public/js/base.js"></script>
<script src="/re.source/public/js/search.js"></script>
<script>
// Busca pelo header pill — redireciona com os parâmetros preenchidos
document.getElementById('headerSearchBtn').addEventListener('click', function () {
  const q    = document.getElementById('headerQ').value.trim();
  const catId = document.getElementById('headerCategoryId').value;
  let url = '/re.source/busca?';
  if (q)     url += 'q='           + encodeURIComponent(q) + '&';
  if (catId) url += 'category_id=' + encodeURIComponent(catId) + '&';
  window.location.href = url.replace(/&$/, '');
});

// Seleção de categoria no dropdown do header
function selectCategory(id, label) {
  document.getElementById('categoryLabel').textContent = label;
  document.getElementById('headerCategoryId').value = id;
  document.getElementById('categoryDropdown').classList.remove('open');
  document.getElementById('categoryTrigger').classList.toggle('selected', id !== '');
}
</script>
</body>
</html>