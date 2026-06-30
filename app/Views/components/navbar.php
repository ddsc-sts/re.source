
<?php
require_once __DIR__ . '/../../Middleware/AdminAuth.php';
// Usa REQUEST_URI para detectar rota ativa no roteador MVC (sem .php)
$rota = strtok($_SERVER['REQUEST_URI'], '?'); // ex: /re.source/admin
?>

<nav class="navbar" id="navbar">
  <a href="/re.source/admin"
    class="nav-item <?= $rota === '/re.source/admin' ? 'active' : '' ?>">
      <i data-lucide="layout-dashboard"></i>
      Dashboard
  </a>
  <a href="/re.source/admin/empresas"
    class="nav-item <?= $rota === '/re.source/admin/empresas' ? 'active' : '' ?>">
      <i data-lucide="building-2"></i>
      Empresas
  </a>
  <a href="/re.source/admin/anuncios"
    class="nav-item <?= $rota === '/re.source/admin/anuncios' ? 'active' : '' ?>">
      <i data-lucide="tag"></i>Anúncios

      <?php if (($metrics['anuncios_pendentes'] ?? 0) > 0): ?>
          <span class="badge"><?= $metrics['anuncios_pendentes'] ?></span>
      <?php endif; ?>
  </a>
  <a href="/re.source/admin/negociacoes"
    class="nav-item <?= $rota === '/re.source/admin/negociacoes' ? 'active' : '' ?>">
      <i data-lucide="handshake"></i>
      Negociações
  </a>
  <a href="/re.source/admin/logistica"
    class="nav-item <?= $rota === '/re.source/admin/logistica' ? 'active' : '' ?>">
      <i data-lucide="truck"></i>
      Logística
  </a>
  <a href="/re.source/admin/impacto"
    class="nav-item <?= $rota === '/re.source/admin/impacto' ? 'active' : '' ?>">
      <i data-lucide="leaf"></i>
      Impacto ESG
  </a>
  <a href="/re.source/admin/suporte"
    class="nav-item <?= $rota === '/re.source/admin/suporte' ? 'active' : '' ?>">
      <i data-lucide="life-buoy"></i>Suporte

    <?php if (($metrics['chamados_abertos'] ?? 0) > 0): ?>
          <span class="badge"><?= $metrics['chamados_abertos'] ?></span>
    <?php endif; ?>
  </a>
  <?php if (AdminAuth::can('view_settings')): ?>
    <a href="/re.source/admin/configuracoes"
      class="nav-item <?= $rota === '/re.source/admin/configuracoes' ? 'active' : '' ?>">
        <i data-lucide="settings"></i>
        Configurações
    </a>
  <?php endif; ?>
  <a href="/re.source/logout"
        class="nav-item"
        style="margin-left:auto"
        onclick="return confirm('Você será desconectado do sistema. Deseja continuar?');"
      >
    <i data-lucide="log-out"></i>Sair
  </a>
</nav>