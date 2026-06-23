<?php
// header.php
// CORREÇÃO: Trocado $_SESSION['company_id'] por $_SESSION['user']['company_id'] em todos os lugares.

$theme = 'light';
if (!empty($_SESSION['user']['company_id'])) {
    $stmt = $pdo->prepare("SELECT theme FROM companies WHERE id = ?");
    $stmt->execute([$_SESSION['user']['company_id']]);
    $result = $stmt->fetch();
    if ($result && $result['theme']) {
        $theme = $result['theme'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Re.Source — Economia Circular'; ?></title>
  
  <link rel="stylesheet" href="/RE.SOURCE/FrontEnd/css/style.css" />
  <?php if (isset($css_especifico)): ?>
    <link rel="stylesheet" href="<?php echo $css_especifico; ?>" />
  <?php endif; ?>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <a href="/RE.SOURCE/base.php">
          <img src="/RE.SOURCE/FrontEnd/img/logo.png" alt="Re.Source" />
        </a>
      </div>

      <nav class="desktop-nav">
        <a href="/RE.SOURCE/base.php"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/RE.SOURCE/sobre.php"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="/RE.SOURCE/contato.php"><i data-lucide="phone"></i> Contato</a>
      </nav>

      <div class="header-actions">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
          <i data-lucide="menu"  class="icon-menu"></i>
          <i data-lucide="x"    class="icon-close"></i>
        </button>
      </div>

      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-label">
          <?php
          // CORREÇÃO: usa $_SESSION['user']['company_id'] ao invés de $_SESSION['company_id']
          if (!empty($_SESSION['user']['company_id'])) {
              $stmt = $pdo->prepare("SELECT nome_fantasia FROM companies WHERE id = ?");
              $stmt->execute([$_SESSION['user']['company_id']]);
              $empresa = $stmt->fetch();
              echo htmlspecialchars($empresa['nome_fantasia'] ?? 'Minha Conta');
          } else {
              echo 'Minha Conta';
          }
          ?>
        </div>
        <a href="/RE.SOURCE/estatisticas.php" class="menu-btn">Estatísticas</a>
        <a href="/RE.SOURCE/meusAnuncios.php" class="menu-btn">Meus Anúncios</a>
        <a href="/RE.SOURCE/conta.php" class="menu-btn">Detalhes da conta</a>
        <a href="/RE.SOURCE/configuracoes.php" class="menu-btn">Configurações</a>

        <div class="dropdown-divider"></div>
        <a href="/RE.SOURCE/anunciarResiduos.php">
          <button class="btn-announce">Anunciar Resíduo</button>
        </a>
      </div>

    </div>
  </div>

  <div class="search-bar-wrap">
    <div class="search-bar-inner">
      <form action="/RE.SOURCE/busca.php" method="GET" class="search-pill">
        
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
            <button type="button" onclick="selectCategory('Todas as categorias')">Todas as categorias</button>
            <button type="button" onclick="selectCategory('Madeira')">Madeira</button>
            <button type="button" onclick="selectCategory('Plástico')">Plástico</button>
            <button type="button" onclick="selectCategory('Têxtil')">Têxtil</button>
            <button type="button" onclick="selectCategory('Metal')">Metal</button>
            <button type="button" onclick="selectCategory('Papelão')">Papelão</button>
            <button type="button" onclick="selectCategory('Borracha')">Borracha</button>
            <button type="button" onclick="selectCategory('Eletrônicos')">Eletrônicos</button>
          </div>
          
          <input type="hidden" name="cat_nome" id="hiddenCategory" value="">
        </div>

        <button type="submit" class="search-btn"><i data-lucide="search"></i></button>
        
      </form>
    </div>
  </div>
</header>

<script>
function selectCategory(categoryName) {
    document.getElementById('categoryLabel').innerText = categoryName;
    document.getElementById('hiddenCategory').value = categoryName === 'Todas as categorias' ? '' : categoryName;
    document.getElementById('categoryDropdown').classList.remove('active'); 
}
</script>