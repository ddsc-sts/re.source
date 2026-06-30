<?php
// app/Views/components/header.php
// Busca o tema do banco antes de renderizar o header
$theme = 'light'; // padrão
$company_id = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;

if ($company_id) {
    try {
        $stmt = $pdo->prepare("SELECT theme FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $result = $stmt->fetch();
        if ($result && $result['theme']) {
            $theme = $result['theme'];
        }
    } catch (\Throwable $e) {
        // ignora
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) : 'Re.Source — Economia Circular'; ?></title>
  
  <link rel="stylesheet" href="/re.source/public/css/base.css" />
  <?php if (isset($css_especifico)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css_especifico); ?>" />
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
        <a href="/re.source/base">
          <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
        </a>
      </div>

      <nav class="desktop-nav">
        <a href="/re.source/base"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/re.source/sobre"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="#"><i data-lucide="phone"></i> Contato</a>
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
            try {
                // Buscamos o nome da empresa para exibir aqui
                $stmt = $pdo->prepare("SELECT nome_fantasia FROM companies WHERE id = ?");
                $stmt->execute([$company_id ?? 1]);
                $empresa = $stmt->fetch();
                echo htmlspecialchars($empresa['nome_fantasia'] ?? 'Minha Conta');
            } catch (\Throwable $e) {
                echo 'Minha Conta';
            }
            ?>
        </div>
        <a href="/re.source/conta" class="menu-btn">Detalhes da conta</a>
        <a href="/re.source/meus-anuncios" class="menu-btn">Meus Anúncios</a>
        <a href="/re.source/estatisticas" class="menu-btn">Estatísticas</a>
        <a href="/re.source/configuracoes" class="menu-btn">Configurações</a>

        <div class="dropdown-divider"></div>
        <a href="/re.source/anuncios/novo">
          <button class="btn-announce">Anunciar Resíduo</button>
        </a>
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
            <button type="button" onclick="selectCategory('Vidro', 6)">Vidro</button>
          </div>
          
          <input type="hidden" name="category_id" id="categoryIdInput" value="">
        </div>

        <button type="submit" class="search-btn"><i data-lucide="search"></i></button>
        
      </form>

    </div>
  </div>
</header>

<script>
function selectCategory(categoryName, categoryId) {
    document.getElementById('categoryLabel').innerText = categoryName;
    document.getElementById('categoryIdInput').value = categoryId;
    document.getElementById('categoryDropdown').classList.remove('active'); 
}
</script>
