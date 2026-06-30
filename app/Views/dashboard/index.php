<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Re.Source — Economia Circular em Joinville</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/base.css" />
</head>
<body>

<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
      </div>

      <nav class="desktop-nav">
        <a href="/re.source/base.php"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/re.source/sobre.php"><i data-lucide="info"></i> Sobre Nós</a>
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
        <a href="/re.source/conta.php" class="menu-btn">Detalhes da conta</a>
        <a href="#" class="menu-btn">Estatísticas</a>
        <a href="#" class="menu-btn">Configurações</a>
        <div class="dropdown-divider"></div>
        <button class="btn-announce">Anunciar Resíduo</button>
      </div>

    </div>
  </div>

  <div class="search-bar-wrap">
    <div class="search-bar-inner">
      <form class="search-pill" action="/re.source/busca" method="GET">
        <div class="search-field">
          <label>O que busca?</label>
          <input type="text" name="q" placeholder="Ex: Serragem" />
        </div>

        <div class="category-field">
          <label>Categoria</label>
          <button type="button" class="category-trigger" id="categoryTrigger">
            <span id="categoryLabel">Todas as categorias</span>
            <i data-lucide="chevron-down"></i>
          </button>
          <input type="hidden" name="category_id" id="categoryIdInput" value="">
          <div class="category-dropdown" id="categoryDropdown">
            <button type="button" onclick="selectCategory('Todas as categorias', '')">Todas as categorias</button>
            <button type="button" onclick="selectCategory('Madeira', 4)">Madeira</button>
            <button type="button" onclick="selectCategory('Plástico', 3)">Plástico</button>
            <button type="button" onclick="selectCategory('Têxtil', 1)">Têxtil</button>
            <button type="button" onclick="selectCategory('Metal', 2)">Metal</button>
            <button type="button" onclick="selectCategory('Papelão', 5)">Papelão</button>
            <button type="button" onclick="selectCategory('Borracha', 7)">Borracha</button>
            <button type="button" onclick="selectCategory('Eletrônicos', 8)">Eletrônicos</button>
            <button type="button" onclick="selectCategory('Vidro', 6)">Vidro</button>
          </div>
        </div>

        <button type="submit" class="search-btn"><i data-lucide="search"></i></button>
      </form>
    </div>
  </div>
</header>

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

<!-- ══ CATEGORIAS ══ -->
<section class="category-section">
  <h2 class="section-title">Explore por Categoria</h2>
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
</section>

<!-- ══ ANÚNCIOS RECENTES ══ -->
<section class="listings-section">
  <div class="listings-inner">
    <div class="listings-header">
      <h2 class="listings-title">Anúncios Recentes</h2>
      <a href="/re.source/busca" class="listings-ver-todos">Ver todos <i data-lucide="arrow-right"></i></a>
    </div>

    <?php if (empty($anunciosRecentes)): ?>
      <p style="color: var(--muted); text-align:center; padding: 3rem 0;">Nenhum anúncio disponível no momento.</p>
    <?php else: ?>
      <div class="listings-grid">
        <?php foreach ($anunciosRecentes as $ad): ?>
          <a href="/re.source/anuncio?id=<?php echo $ad['id']; ?>" class="listing-card">

            <div class="listing-img-wrap">
              <?php if (!empty($ad['main_image'])): ?>
                <img src="<?php echo htmlspecialchars($ad['main_image']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" />
              <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);">
                  <i data-lucide="image" style="width:32px;height:32px;color:var(--muted);"></i>
                </div>
              <?php endif; ?>
              <span class="listing-badge <?php echo $ad['type'] === 'demand' ? 'doacao' : ''; ?>">
                <?php echo $ad['type'] === 'offer' ? 'Oferta' : 'Demanda'; ?>
              </span>
            </div>

            <div class="listing-body">
              <div class="listing-title"><?php echo htmlspecialchars($ad['title']); ?></div>
              <?php if (!empty($ad['location_city'])): ?>
                <div class="listing-location">
                  <i data-lucide="map-pin"></i>
                  <span><?php echo htmlspecialchars($ad['location_city'] . ' — ' . $ad['location_state']); ?></span>
                </div>
              <?php endif; ?>
              <div class="listing-qty">
                <?php echo number_format((float)$ad['quantity'], 0, ',', '.') . ' ' . $ad['unit']; ?>
              </div>
            </div>

          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="stats-bar">
    <div class="stats-inner">
      <div>
        <div class="stat-value">+1,4 Bilhão</div>
        <div class="stat-label">KG de Resíduos</div>
      </div>
      <div>
        <div class="stat-value">+1,3 Bilhão</div>
        <div class="stat-label">Reais Valorizados</div>
      </div>
      <div>
        <div class="stat-value">+790 mil</div>
        <div class="stat-label">Usuários B2B</div>
      </div>
      <div>
        <div class="stat-value">+25 mil</div>
        <div class="stat-label">Empresas Homologadas</div>
      </div>
    </div>
  </div>

  <div class="footer-main">
    <div class="footer-logo">
      <div class="footer-logo-wrap">
        <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
      </div>
      <p class="footer-desc">Conectando empresas para uma economia circular sustentável em Joinville.</p>
    </div>

    <div class="footer-col">
      <h4>O que você procura?</h4>
      <ul>
        <li><a href="#">Plástico</a></li>
        <li><a href="#">Metal</a></li>
        <li><a href="#">Madeira</a></li>
        <li><a href="#">Têxtil</a></li>
        <li><a href="#">Eletrônicos</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Na Re.Source</h4>
      <ul>
        <li><a href="#">A Re.Source</a></li>
        <li><a href="#">Sobre Nós</a></li>
        <li><a href="#">Serviços</a></li>
        <li><a href="#">Anúncios</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Precisa de ajuda?</h4>
      <div class="footer-contact">
        <p>Entre em contato com a nossa equipe:</p>
        <a href="mailto:contato@resource.com.br">contato@resource.com.br</a>
      </div>
      <a href="https://wa.me/5547999999999" class="whatsapp-contact-btn" target="_blank" rel="noopener">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
        Contato via WhatsApp
      </a>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© 2026 Re.Source. Todos os direitos reservados.</p>
  </div>
</footer>

<script src="/re.source/public/js/base.js"></script>
</body>
</html>