<?php
// header.php
// Este componente é incluído dentro de view(), portanto precisa importar
// explicitamente a conexão criada pelo bootstrap no escopo global.
global $pdo;

$theme = 'light';
$platformName = app_setting('platform_name', 'Re.Source') ?: 'Re.Source';
$maintenanceMessage = trim((string) app_setting('maintenance_message', ''));
$headerCompanyName = 'Minha Conta';
$headerCompanyId = $_SESSION['user']['company_id'] ?? $_SESSION['company_id'] ?? null;
$headerCompanyStatus = $_SESSION['user']['company_status'] ?? null;
$headerUnreadMessages = 0;
$headerLatestUnreadId = 0;
$headerUnseenNotifications = 0;
$headerCategories = [];

try {
    $headerCategories = $pdo->query(
        'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Falha ao carregar categorias do cabeçalho: ' . $e->getMessage());
}

if ($headerCompanyId) {
    $stmt = $pdo->prepare("SELECT theme, nome_fantasia, status, logo_url FROM companies WHERE id = ?");
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

    if ($headerCompanyStatus === 'active') {
        $stmtUnread = $pdo->prepare(
            'SELECT COUNT(*) AS unread_count, COALESCE(MAX(m.id), 0) AS latest_message_id
             FROM messages m
             INNER JOIN negotiations n ON n.id = m.negotiation_id
             INNER JOIN users sender ON sender.id = m.sender_user_id
             WHERE m.read_at IS NULL
               AND sender.company_id <> ?
               AND (n.buyer_company_id = ? OR n.seller_company_id = ?)'
        );
        $stmtUnread->execute([$headerCompanyId, $headerCompanyId, $headerCompanyId]);
        $headerUnreadOverview = $stmtUnread->fetch(PDO::FETCH_ASSOC) ?: [];
        $headerUnreadMessages = (int) ($headerUnreadOverview['unread_count'] ?? 0);
        $headerLatestUnreadId = (int) ($headerUnreadOverview['latest_message_id'] ?? 0);
    }

    $stmtNotifications = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE company_id = ? AND is_seen = 0'
    );
    $stmtNotifications->execute([$headerCompanyId]);
    $headerUnseenNotifications = (int) $stmtNotifications->fetchColumn();
}
$headerIsPending = in_array($headerCompanyStatus, ['pending', 'changes_requested'], true);
$headerHomeUrl = $headerIsPending ? app_url('/aguardando-aprovacao') : app_url('/base');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Re.Source — Economia Circular'; ?></title>
  
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/style.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/design-system.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/profile.css'), ENT_QUOTES, 'UTF-8') ?>" />
  <?php if (isset($css_especifico)): ?>
    <link rel="stylesheet" href="<?php echo $css_especifico; ?>" />
  <?php endif; ?>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<script>window.APP_BASE_PATH = <?= json_encode(APP_BASE_PATH, JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require __DIR__ . '/flash.php'; ?>

<?php if ($maintenanceMessage !== ''): ?>
  <div class="system-alert" role="status">
    <i data-lucide="wrench"></i>
    <span><?= htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
<?php endif; ?>

<header>
  <div class="header-top">
    <div class="header-top-inner">

      <div class="logo">
        <a href="<?= $headerHomeUrl ?>">
          <img src="/re.source/public/img/logos/logo.png" alt="<?= htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8') ?>" />
        </a>
      </div>

      <nav class="desktop-nav">
        <a href="<?= $headerHomeUrl ?>"><i data-lucide="home"></i> Página Inicial</a>
        <a href="/re.source/sobre"><i data-lucide="info"></i> Sobre Nós</a>
        <a href="/re.source/contato"><i data-lucide="phone"></i> Contato</a>
      </nav>

      <?php if ($headerCompanyId && !$headerIsPending): ?>
      <div class="header-user-actions">
        <button
          class="header-bell<?= $headerUnseenNotifications > 0 ? ' has-unread' : '' ?>"
          id="notificationButton"
          aria-label="Notificações"
          aria-expanded="false"
          aria-controls="notificationPanel"
          type="button"
          data-feed-url="<?= htmlspecialchars(app_url('/notificacoes'), ENT_QUOTES, 'UTF-8') ?>"
          data-read-url="<?= htmlspecialchars(app_url('/notificacoes/marcar-lidas'), ENT_QUOTES, 'UTF-8') ?>"
          data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
        >
          <i data-lucide="bell"></i>
          <span class="header-bell-dot"></span>
          <span class="sr-only" id="notificationCount"><?= $headerUnseenNotifications ?></span>
        </button>
        <button
          type="button"
          class="header-user-chip"
          id="userMenuBtn"
          aria-haspopup="menu"
          aria-expanded="false"
          aria-controls="dropdownMenu"
        >
          <div class="header-user-text">
            <strong><?= htmlspecialchars($headerCompanyName, ENT_QUOTES, 'UTF-8') ?></strong>
            <span>Conta B2B Verificada</span>
          </div>
          <div class="header-user-avatar">
            <?php if (!empty($headerCompany['logo_url'])): ?>
              <img src="<?= htmlspecialchars($headerCompany['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($headerCompanyName, ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
              <i data-lucide="factory"></i>
            <?php endif; ?>
          </div>
          <i data-lucide="chevron-down" class="header-user-caret"></i>
        </button>
      </div>
      <?php endif; ?>

      <aside class="notification-panel" id="notificationPanel" aria-label="Notificações" hidden>
        <div class="notification-panel-head">
          <strong>Notificações</strong>
          <button type="button" id="markNotificationsRead">Marcar como lidas</button>
        </div>
        <div class="notification-panel-list" id="notificationList">
          <p class="notification-empty">Carregando...</p>
        </div>
      </aside>


      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-label">
          <?= htmlspecialchars($headerCompanyName, ENT_QUOTES, 'UTF-8') ?>
          <?php if ($headerIsPending): ?>
            <span class="header-status-note">
              <?= $headerCompanyStatus === 'changes_requested' ? 'CORREÇÃO SOLICITADA' : 'AGUARDANDO APROVAÇÃO' ?>
            </span>
          <?php endif; ?>
        </div>
        <?php if ($headerIsPending): ?>
          <a href="/re.source/aguardando-aprovacao" class="menu-btn">Status da aprovação</a>
        <?php else: ?>
          <a href="/re.source/estatisticas" class="menu-btn">Estatísticas</a>
          <a href="/re.source/meus-anuncios" class="menu-btn">Meus Anúncios</a>
          <a href="<?= htmlspecialchars(app_url('/entregas'), ENT_QUOTES, 'UTF-8') ?>" class="menu-btn">Minhas entregas</a>
          <a href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>" class="menu-btn">
            Conversas
            <span
              id="headerUnreadBadge"
              class="header-unread-badge"
              data-unread-url="<?= htmlspecialchars(app_url('/conversas/nao-lidas'), ENT_QUOTES, 'UTF-8') ?>"
              data-latest-message-id="<?= $headerLatestUnreadId ?>"
              aria-label="<?= $headerUnreadMessages ?> mensagens não lidas"
              <?= $headerUnreadMessages > 0 ? '' : 'hidden' ?>
            ><?= $headerUnreadMessages > 99 ? '99+' : $headerUnreadMessages ?></span>
          </a>
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

  <?php if (empty($hideSearchBar)): ?>
  <div>
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
            <button type="button" data-category-id="" data-category-name="Todas as categorias">Todas as categorias</button>
            <?php foreach ($headerCategories as $headerCategory): ?>
              <button
                type="button"
                data-category-id="<?= (int) $headerCategory['id'] ?>"
                data-category-name="<?= htmlspecialchars($headerCategory['name'], ENT_QUOTES, 'UTF-8') ?>"
              ><?= htmlspecialchars($headerCategory['name'], ENT_QUOTES, 'UTF-8') ?></button>
            <?php endforeach; ?>
          </div>
          
          <input type="hidden" name="category_id" id="categoryIdInput" value="">
        </div>

        <button type="submit" class="search-btn"><i data-lucide="search"></i></button>
        
      </form>
    </div>
  </div>
  <?php endif; ?>
</header>

<?php if (!$headerIsPending && $headerCompanyStatus === 'active'): ?>
  <script src="<?= htmlspecialchars(app_url('/public/js/header-unread.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
