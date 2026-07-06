<?php
  $userName     = htmlspecialchars($user['name'] ?? 'Administrador');
  $userInitials = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $userName), 0, 2)));
  
?>
<div class="topbar">
    <a href="#" class="topbar-logo">
      <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
      <div class="topbar-logo-text">
        <span class="topbar-logo-name">Re.Source</span>
        <span class="topbar-logo-sub">Indústria Circular</span>
      </div>
    </a>

    <form class="topbar-search" action="/re.source/admin/empresas" method="get">
      <i data-lucide="search"></i>
      <input type="search" name="q" placeholder="Buscar empresas por nome, CNPJ ou e-mail" />
      <kbd>Enter</kbd>
    </form>

    <div class="topbar-actions">
      <a class="topbar-btn" title="Suporte" href="/re.source/admin/suporte"><i data-lucide="life-buoy"></i></a>
      <a class="topbar-btn" title="Alertas operacionais" href="/re.source/admin/suporte">
        <i data-lucide="bell"></i>
        <span class="topbar-notif-dot"></span>
      </a>
      <?php if (AdminAuth::can('view_settings')): ?><a class="topbar-btn" title="Configurações" href="/re.source/admin/configuracoes"><i data-lucide="settings"></i></a><?php endif; ?>
      <div class="topbar-user">
        <div class="topbar-avatar"><?= $userInitials ?></div>
        <div class="topbar-user-info">
          <div class="topbar-user-name"><?= $userName ?></div>
          <div class="topbar-user-role"><?= AdminAuth::isAdmin() ? 'Administrador' : 'Staff' ?></div>
        </div>
      </div>
      <button class="mobile-menu-toggle" id="navToggle" aria-label="Menu">
        <i data-lucide="menu"></i>
      </button>
    </div>
  </div>
