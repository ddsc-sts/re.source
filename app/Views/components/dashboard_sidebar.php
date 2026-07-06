<?php
$sidebarActive = $sidebarActive ?? '';
$sidebarCompanyName = $headerCompanyName ?? ($_SESSION['user']['company_name'] ?? 'Minha empresa');
$sidebarLogoUrl = $headerCompany['logo_url'] ?? null;
$sidebarLinks = [
    ['statistics', '/estatisticas', 'bar-chart-2', 'Painel e Estatísticas'],
    ['listings', '/meus-anuncios', 'package', 'Meus anúncios'],
    ['conversations', '/conversas', 'messages-square', 'Conversas'],
    ['deliveries', '/entregas', 'truck', 'Minhas entregas'],
    ['account', '/conta', 'user', 'Detalhes da conta'],
    ['settings', '/configuracoes', 'settings', 'Configurações'],
];
?>
<aside class="dashboard-sidebar" aria-label="Navegação da empresa">
  <?php require __DIR__ . '/profile/sidebar_profile.php'; ?>
  <nav class="sidebar-nav">
    <?php foreach ($sidebarLinks as [$key, $path, $icon, $label]): ?>
      <a href="<?= htmlspecialchars(app_url($path), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link <?= $sidebarActive === $key ? 'active' : '' ?>">
        <i data-lucide="<?= $icon ?>"></i><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= htmlspecialchars(app_url('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link"><i data-lucide="log-out"></i>Sair</a>
  </nav>
</aside>
