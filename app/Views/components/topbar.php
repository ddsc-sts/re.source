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

    <div class="topbar-search">
      <i data-lucide="search"></i>
      <input type="text" placeholder="Buscar empresas, anúncios, resíduos…" />
      <kbd>⌘K</kbd>
    </div>

    <div class="topbar-actions">
      <button class="topbar-btn" title="Mensagens"><i data-lucide="message-square"></i></button>
      <button class="topbar-btn" title="Notificações">
        <i data-lucide="bell"></i>
        <span class="topbar-notif-dot"></span>
      </button>
      <button class="topbar-btn" title="Configurações"><i data-lucide="settings"></i></button>
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