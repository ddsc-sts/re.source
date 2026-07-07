<?php
  global $pdo;

  $userName     = htmlspecialchars($user['name'] ?? 'Administrador');
  $userInitials = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $userName), 0, 2)));
  $topbarPlatformName = app_setting('platform_name', 'Re.Source') ?: 'Re.Source';
  $adminSupportSeenAt = $_SESSION['admin_support_seen_at'] ?? null;
  $topbarAlertCount = 0;

  try {
      if ($adminSupportSeenAt) {
          $stmt = $pdo->prepare(
              "SELECT COUNT(*) FROM audit_logs
               WHERE severity IN ('warning','critical') AND created_at > ?"
          );
          $stmt->execute([$adminSupportSeenAt]);
          $topbarAlertCount = (int) $stmt->fetchColumn();
      } else {
          $topbarAlertCount = (int) $pdo->query(
              "SELECT COUNT(*) FROM audit_logs WHERE severity IN ('warning','critical')"
          )->fetchColumn();
      }
  } catch (Throwable $e) {
      $topbarAlertCount = 0;
  }
?>
<div class="topbar">
    <a href="#" class="topbar-logo">
      <img src="/re.source/public/img/logos/logo.png" alt="<?= htmlspecialchars($topbarPlatformName, ENT_QUOTES, 'UTF-8') ?>" />
      <div class="topbar-logo-text">
        <span class="topbar-logo-name"><?= htmlspecialchars($topbarPlatformName, ENT_QUOTES, 'UTF-8') ?></span>
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
        <?php if ($topbarAlertCount > 0): ?><span class="topbar-notif-dot"></span><?php endif; ?>
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
