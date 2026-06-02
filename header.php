<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
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
  <link rel="stylesheet" href="/RE.SOURCE/FrontEnd/css/style.css" />
  
</head>
<body>

<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <img src="/RE.SOURCE/FrontEnd/img/logo.png" alt="Re.Source" />
      </div>

      <nav class="desktop-nav">
        <a href="/RE.SOURCE/base.php"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/RE.SOURCE/sobre.php"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="#"><i data-lucide="phone"></i> Contato</a>
      </nav>

      <div class="header-actions">
        <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
          <i data-lucide="menu"  class="icon-menu"></i>
          <i data-lucide="x"    class="icon-close"></i>
        </button>
      </div>

      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-label">Minha Conta</div>
        <a href="/RE.SOURCE/conta.php" class="menu-btn">Detalhes da conta</a>
        <a href="/RE.SOURCE/.php" class="menu-btn">Estatísticas</a>
        <a href="/RE.SOURCE/.php" class="menu-btn">Configurações</a>

        <div class="dropdown-divider"></div>
        <button class="btn-announce">Anunciar Resíduo</button>
      </div>

    </div>
  </div>

  <div class="search-bar-wrap">
    <div class="search-bar-inner">
      <div class="search-pill">
        <div class="search-field">
          <label>O que busca?</label>
          <input type="text" placeholder="Ex: Serragem" />
        </div>

        <div class="category-field">
          <label>Categoria</label>
          <button class="category-trigger" id="categoryTrigger">
            <span id="categoryLabel">Todas as categorias</span>
            <i data-lucide="chevron-down"></i>
          </button>
          <div class="category-dropdown" id="categoryDropdown">
            <button onclick="selectCategory('Todas as categorias')">Todas as categorias</button>
            <button onclick="selectCategory('Madeira')">Madeira</button>
            <button onclick="selectCategory('Plástico')">Plástico</button>
            <button onclick="selectCategory('Têxtil')">Têxtil</button>
            <button onclick="selectCategory('Metal')">Metal</button>
            <button onclick="selectCategory('Papelão')">Papelão</button>
            <button onclick="selectCategory('Borracha')">Borracha</button>
            <button onclick="selectCategory('Eletrônicos')">Eletrônicos</button>
          </div>
        </div>

        <button class="search-btn"><i data-lucide="search"></i></button>
      </div>
    </div>
  </div>
</header>