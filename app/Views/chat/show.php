<?php require __DIR__ . '/../components/header.php'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/chat.css'), ENT_QUOTES, 'UTF-8') ?>">

<main class="chat-page chat-room-page">
  <div class="chat-room-header">
    <a class="chat-back" href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Voltar às conversas"><i data-lucide="arrow-left"></i></a>
    <div>
      <span>Conversa com <?= htmlspecialchars($otherCompanyName ?: 'Empresa', ENT_QUOTES, 'UTF-8') ?></span>
      <h1><?= htmlspecialchars($negotiation['listing_title'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <a class="chat-secondary-button" href="<?= htmlspecialchars(app_url('/anuncio?id=' . (int) $negotiation['listing_id']), ENT_QUOTES, 'UTF-8') ?>">Ver anúncio</a>
  </div>

  <section
    class="chat-room"
    id="chatRoom"
    data-negotiation-id="<?= (int) $negotiation['id'] ?>"
    data-company-id="<?= (int) $companyId ?>"
    data-messages-url="<?= htmlspecialchars(app_url('/conversas/mensagens'), ENT_QUOTES, 'UTF-8') ?>"
    data-send-url="<?= htmlspecialchars(app_url('/conversas/enviar'), ENT_QUOTES, 'UTF-8') ?>"
  >
    <div class="message-list" id="messageList" aria-live="polite">
      <?php if (!$messages): ?>
        <div class="chat-empty-inline" id="emptyMessages">Esta conversa começou agora. Envie a primeira mensagem.</div>
      <?php endif; ?>
      <?php foreach ($messages as $message): ?>
        <?php $ownMessage = (int) $message['sender_company_id'] === (int) $companyId; ?>
        <article class="message <?= $ownMessage ? 'message-own' : 'message-received' ?>" data-message-id="<?= (int) $message['id'] ?>">
          <span class="message-sender"><?= htmlspecialchars($ownMessage ? 'Você' : $message['sender_name'], ENT_QUOTES, 'UTF-8') ?></span>
          <p><?= nl2br(htmlspecialchars($message['content'], ENT_QUOTES, 'UTF-8')) ?></p>
          <time datetime="<?= htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></time>
        </article>
      <?php endforeach; ?>
    </div>

    <form class="message-form" id="messageForm" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
      <label class="sr-only" for="messageContent">Mensagem</label>
      <textarea id="messageContent" name="content" maxlength="2000" rows="2" placeholder="Escreva uma mensagem..." required></textarea>
      <button type="submit" id="sendMessageButton"><i data-lucide="send"></i><span>Enviar</span></button>
      <div class="message-form-error" id="messageFormError" role="alert"></div>
    </form>
  </section>
</main>

<script src="<?= htmlspecialchars(app_url('/public/js/chat.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php require __DIR__ . '/../components/footer.php'; ?>
