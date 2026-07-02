<?php
// header.php
// Este componente é incluído dentro de view(), portanto precisa importar
// explicitamente a conexão criada pelo bootstrap no escopo global.
global $pdo;

$theme = 'light';
$headerCompanyName = 'Minha Conta';
$headerCompanyId = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
$headerCompanyStatus = $_SESSION['user']['company_status'] ?? null;

if ($headerCompanyId) {
    $stmt = $pdo->prepare("SELECT theme, nome_fantasia, status FROM companies WHERE id = ?");
    $stmt->execute([$headerCompanyId]);
    $headerCompany = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($headerCompany) {
        if (!empty($headerCompany['theme'])) {
            $theme = $headerCompany['theme'];
        }
        if (!empty($headerCompany['nome_fantasia'])) {
            $headerCompanyName = $headerCompany['nome_fantasia'];
        }
        $headerCompanyStatus = $headerCompany['status'];
        $_SESSION['user']['company_status'] = $headerCompanyStatus;
    }
}
$headerIsPending = $headerCompanyStatus === 'pending';
$headerHomeUrl = $headerIsPending ? app_url('/aguardando-aprovacao') : app_url('/base');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Re.Source — Economia Circular'; ?></title>
  
  <link rel="stylesheet" href="/re.source/public/css/style.css" />
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <?php if (isset($css_especifico)): ?>
    <link rel="stylesheet" href="<?php echo $css_especifico; ?>" />
  <?php endif; ?>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<script>window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH, JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require __DIR__ . '/flash.php'; ?>

<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <a href="<?= $headerHomeUrl ?>">
          <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
        </a>
      </div>

      <nav class="desktop-nav">
        <a href="<?= $headerHomeUrl ?>"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/re.source/sobre"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="/re.source/contato"><i data-lucide="phone"></i> Contato</a>
      </nav>

      <div class="header-actions">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
          <i data-lucide="menu"  class="icon-menu"></i>
          <i data-lucide="x"    class="icon-close"></i>
        </button>
      </div>

      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-label">
          <?= htmlspecialchars($headerCompanyName, ENT_QUOTES, 'UTF-8') ?>
          <?php if ($headerIsPending): ?>
            <span style="display:block;margin-top:5px;color:#9a6700;font-size:.7rem;font-weight:700;">AGUARDANDO APROVAÇÃO</span>
          <?php endif; ?>
        </div>
        <?php if ($headerIsPending): ?>
          <a href="/re.source/aguardando-aprovacao" class="menu-btn">Status da aprovação</a>
        <?php else: ?>
          <a href="/re.source/estatisticas" class="menu-btn">Estatísticas</a>
          <a href="/re.source/meus-anuncios" class="menu-btn">Meus Anúncios</a>
          <a href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>" class="menu-btn">Conversas</a>
        <?php endif; ?>
        <a href="/re.source/conta" class="menu-btn">Detalhes da conta</a>
        <a href="/re.source/configuracoes" class="menu-btn">Configurações</a>

        <?php if (!$headerIsPending): ?>
          <div class="dropdown-divider"></div>
          <a href="/re.source/anuncios/novo">
            <button class="btn-announce">Anunciar Resíduo</button>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <div class="search-bar-wrap">
    <div class="search-bar-inner">
      <form action="/re.source/busca" method="GET" class="search-pill">
        
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
          
          <div class="category-dropdown" id="categoryDropdown">
            <button type="button" onclick="selectCategory('Todas as categorias', '')">Todas as categorias</button>
            <button type="button" onclick="selectCategory('Madeira', 4)">Madeira</button>
            <button type="button" onclick="selectCategory('Plástico', 3)">Plástico</button>
            <button type="button" onclick="selectCategory('Têxtil', 1)">Têxtil</button>
            <button type="button" onclick="selectCategory('Metal', 2)">Metal</button>
            <button type="button" onclick="selectCategory('Papelão', 5)">Papelão</button>
            <button type="button" onclick="selectCategory('Borracha', 7)">Borracha</button>
            <button type="button" onclick="selectCategory('Eletrônicos', 8)">Eletrônicos</button>
          </div>
          
          <input type="hidden" name="category_id" id="hiddenCategory" value="">
        </div>

        <button type="submit" class="search-btn"><i data-lucide="search"></i></button>
        
      </form>
    </div>
  </div>
</header>

<script>
function selectCategory(categoryName, categoryId) {
    document.getElementById('categoryLabel').innerText = categoryName;
    document.getElementById('hiddenCategory').value = categoryId;
    document.getElementById('categoryDropdown').classList.remove('active'); 
}
</script>
