<?php require __DIR__ . '/../components/header.php'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/chat.css'), ENT_QUOTES, 'UTF-8') ?>">

<main class="chat-page">
  <div class="chat-page-heading">
    <div>
      <span class="chat-eyebrow">Negociações</span>
      <h1>Suas conversas</h1>
      <p>Acompanhe os contatos iniciados pelos anúncios.</p>
    </div>
    <a class="chat-secondary-button" href="<?= htmlspecialchars(app_url('/busca'), ENT_QUOTES, 'UTF-8') ?>">Explorar anúncios</a>
  </div>

  <section class="conversation-list" aria-label="Lista de conversas">
    <?php if (!$conversations): ?>
      <div class="chat-empty">
        <i data-lucide="messages-square"></i>
        <h2>Nenhuma conversa por enquanto</h2>
        <p>Abra um anúncio de outra empresa e clique em “Entrar em contato”.</p>
      </div>
    <?php else: ?>
      <?php foreach ($conversations as $conversation): ?>
        <a class="conversation-card" href="<?= htmlspecialchars(app_url('/conversas/abrir?id=' . (int) $conversation['id']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="conversation-avatar"><i data-lucide="building-2"></i></div>
          <div class="conversation-content">
            <div class="conversation-topline">
              <strong><?= htmlspecialchars($conversation['other_company_name'] ?: 'Empresa', ENT_QUOTES, 'UTF-8') ?></strong>
              <time><?= date('d/m H:i', strtotime($conversation['last_message_at'] ?: $conversation['created_at'])) ?></time>
            </div>
            <span class="conversation-listing"><?= htmlspecialchars($conversation['listing_title'], ENT_QUOTES, 'UTF-8') ?></span>
            <p><?= htmlspecialchars($conversation['last_message'] ?: 'Conversa iniciada. Envie a primeira mensagem.', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <i data-lucide="chevron-right" class="conversation-arrow"></i>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/../components/footer.php'; ?>
