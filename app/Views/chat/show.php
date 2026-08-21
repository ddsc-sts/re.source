<?php require __DIR__ . '/../components/header.php'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/chat.css'), ENT_QUOTES, 'UTF-8') ?>">
<main class="internal-page-shell">
  <div class="chat-page chat-room-page">
  <div class="chat-room-header">
    <a class="chat-back" href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Voltar às conversas"><i data-lucide="arrow-left"></i></a>
    <div>
      <span>Conversa com <?= htmlspecialchars($otherCompanyName ?: 'Empresa', ENT_QUOTES, 'UTF-8') ?></span>
      <h1><?= htmlspecialchars($negotiation['listing_title'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <a class="chat-secondary-button" href="<?= htmlspecialchars(app_url('/anuncio?id=' . (int) $negotiation['listing_id']), ENT_QUOTES, 'UTF-8') ?>">Ver anúncio</a>
    <span class="chat-sync-status is-online" id="chatSyncStatus" aria-live="polite">
      <span class="chat-sync-dot"></span>
      <span id="chatSyncText">Atualização automática ativa</span>
    </span>
  </div>

  <?php
  $negotiationStatus = (string) $negotiation['status'];
  $buyerAccepted = !empty($proposal['buyer_accepted_at']);
  $sellerAccepted = !empty($proposal['seller_accepted_at']);
  $currentAccepted = $isBuyer ? $buyerAccepted : $sellerAccepted;
  $proposalPending = $proposal && $proposal['status'] === 'pending';
  $canManageProposal = in_array($negotiationStatus, ['open', 'proposal_sent', 'buyer_accepted', 'seller_accepted'], true);
  $canEditProposal = $canManageProposal && (!$proposalPending || (int) $proposal['sender_company_id'] === (int) $companyId);
  $freightLabels = ['buyer' => 'Comprador', 'seller' => 'Vendedor', 'shared' => 'Dividido entre as empresas'];
  $negotiationStatusLabel = match ($negotiationStatus) {
    'open' => 'Conversa aberta', 'proposal_sent' => 'Proposta enviada',
    'buyer_accepted' => 'Comprador aceitou', 'seller_accepted' => 'Vendedor aceitou',
    'accepted' => 'Acordo confirmado', 'cancelled' => 'Cancelada',
    default => ucfirst(str_replace('_', ' ', $negotiationStatus)),
  };
  $flowOrder = ['open'=>0,'proposal_sent'=>0,'buyer_accepted'=>1,'seller_accepted'=>1,'accepted'=>1,'awaiting_freight'=>2,'shipping'=>3,'delivered'=>4,'concluded'=>4];
  $currentFlowStep = $flowOrder[$negotiationStatus] ?? 0;
  ?>

  <section class="negotiation-progress" aria-label="Progresso da negociação">
    <div><strong>Em qual etapa estou?</strong><span><?= htmlspecialchars($negotiationStatusLabel, ENT_QUOTES, 'UTF-8') ?></span></div>
    <ol><?php foreach (['Proposta','Aceite','Frete','Transporte','Entrega'] as $stepIndex=>$stepLabel): ?><li class="<?= $stepIndex < $currentFlowStep ? 'is-done' : ($stepIndex === $currentFlowStep ? 'is-current' : '') ?>"><i><?= $stepIndex < $currentFlowStep ? '✓' : $stepIndex + 1 ?></i><span><?= $stepLabel ?></span></li><?php endforeach; ?></ol>
    <p><?php echo match($currentFlowStep){0=>'Próxima ação: envie ou revise a proposta.',1=>'Próxima ação: confirme o acordo das duas empresas.',2=>'Próxima ação: escolha e contrate uma opção de frete.',3=>'O material está em transporte. Acompanhe a atualização logística.',default=>'Confirme a entrega e consulte o passaporte do material.'}; ?></p>
  </section>
  <?php if ($negotiationStatus === 'concluded'): ?>
    <form method="post" action="<?= htmlspecialchars(app_url('/passaporte/criar'), ENT_QUOTES, 'UTF-8') ?>" class="passport-action">
      <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int)$negotiation['id'] ?>">
      <div><strong>Material reaproveitado</strong><span>Gere o registro rastreável desta negociação.</span></div>
      <button type="submit"><i data-lucide="qr-code"></i> Ver passaporte digital</button>
    </form>
  <?php endif; ?>

  <details class="proposal-drawer">
    <summary class="proposal-drawer-trigger">
      <span><i data-lucide="handshake"></i><strong>Proposta e acordo comercial</strong></span>
      <span class="negotiation-status status-<?= htmlspecialchars($negotiationStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($negotiationStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
      <i class="proposal-drawer-chevron" data-lucide="chevron-down"></i>
    </summary>
    <section class="proposal-panel" aria-label="Proposta da negociação">
    <div class="proposal-panel-heading">
      <div>
        <span class="chat-eyebrow">Acordo comercial</span>
        <h2>Proposta da negociação</h2>
      </div>
    </div>

    <?php if ($proposal): ?>
      <div class="proposal-card proposal-<?= htmlspecialchars($proposal['status'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="proposal-values">
          <div><span>Quantidade</span><strong><?= number_format((float) $proposal['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($negotiation['unit'], ENT_QUOTES, 'UTF-8') ?></strong></div>
          <div><span>Valor unitário</span><strong>R$ <?= number_format((float) $proposal['unit_price'], 2, ',', '.') ?></strong></div>
          <div><span>Total</span><strong>R$ <?= number_format((float) $proposal['total_price'], 2, ',', '.') ?></strong></div>
          <div><span>Prazo</span><strong><?= $proposal['delivery_deadline'] ? date('d/m/Y', strtotime($proposal['delivery_deadline'])) : 'A combinar' ?></strong></div>
          <div><span>Frete</span><strong><?= htmlspecialchars($freightLabels[$proposal['responsible_for_freight']] ?? 'A combinar', ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
        <?php if (!empty($proposal['notes'])): ?><p class="proposal-notes"><?= nl2br(htmlspecialchars($proposal['notes'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>

        <?php if ($proposalPending): ?>
          <div class="acceptance-grid">
            <span class="<?= $buyerAccepted ? 'accepted' : '' ?>"><i data-lucide="<?= $buyerAccepted ? 'check-circle-2' : 'clock-3' ?>"></i> Comprador <?= $buyerAccepted ? 'confirmou' : 'a confirmar' ?></span>
            <span class="<?= $sellerAccepted ? 'accepted' : '' ?>"><i data-lucide="<?= $sellerAccepted ? 'check-circle-2' : 'clock-3' ?>"></i> Vendedor <?= $sellerAccepted ? 'confirmou' : 'a confirmar' ?></span>
          </div>
          <div class="proposal-actions">
            <?php if (!$currentAccepted): ?>
              <form method="POST" action="<?= htmlspecialchars(app_url('/negociacoes/proposta/aceitar'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Confirmar os valores e condições desta proposta?')">
                <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
                <button class="proposal-accept" type="submit"><i data-lucide="handshake"></i> Aceitar proposta</button>
              </form>
            <?php else: ?>
              <span class="waiting-other">Seu aceite foi registrado. Aguardando a outra empresa.</span>
            <?php endif; ?>
            <details class="reason-action">
              <summary>Recusar proposta</summary>
              <form method="POST" action="<?= htmlspecialchars(app_url('/negociacoes/proposta/recusar'), ENT_QUOTES, 'UTF-8') ?>">
                <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
                <textarea name="reason" minlength="10" maxlength="1000" required placeholder="Explique o motivo da recusa"></textarea>
                <button type="submit">Confirmar recusa</button>
              </form>
            </details>
          </div>
        <?php elseif ($proposal['status'] === 'refused'): ?>
          <p class="proposal-feedback refused"><strong>Proposta recusada:</strong> <?= htmlspecialchars($proposal['refusal_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($proposal['status'] === 'cancelled'): ?>
          <p class="proposal-feedback refused"><strong>Negociação cancelada:</strong> <?= htmlspecialchars($proposal['cancel_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($negotiationStatus === 'accepted'): ?>
      <div class="agreement-confirmed">
        <i data-lucide="badge-check"></i>
        <div><strong>Acordo mútuo confirmado</strong><span>Protocolo <?= htmlspecialchars($negotiation['protocol_number'], ENT_QUOTES, 'UTF-8') ?></span></div>
        <a href="<?= htmlspecialchars(app_url('/frete?negociacao=' . (int) $negotiation['id']), ENT_QUOTES, 'UTF-8') ?>">Continuar para o frete</a>
      </div>
    <?php elseif ($canEditProposal): ?>
      <details class="proposal-editor" <?= !$proposalPending ? 'open' : '' ?>>
        <summary><?= $proposalPending ? 'Editar proposta' : 'Criar nova proposta' ?></summary>
        <form method="POST" action="<?= htmlspecialchars(app_url('/negociacoes/proposta'), ENT_QUOTES, 'UTF-8') ?>">
          <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
          <label>Quantidade<input type="number" name="quantity" min="0.001" max="999999999" step="0.001" required value="<?= $proposalPending ? htmlspecialchars($proposal['quantity'], ENT_QUOTES, 'UTF-8') : '' ?>"></label>
          <label>Valor unitário (R$)<input type="number" name="unit_price" min="0" max="999999999" step="0.01" required value="<?= $proposalPending ? htmlspecialchars($proposal['unit_price'], ENT_QUOTES, 'UTF-8') : '' ?>"></label>
          <label>Prazo de entrega<input type="date" name="delivery_deadline" min="<?= date('Y-m-d') ?>" value="<?= $proposalPending ? htmlspecialchars($proposal['delivery_deadline'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?>"></label>
          <label>Responsável pelo frete<select name="responsible_for_freight" required><?php foreach ($freightLabels as $value => $label): ?><option value="<?= $value ?>" <?= $proposalPending && $proposal['responsible_for_freight'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
          <label class="proposal-notes-field">Observações<textarea name="notes" maxlength="1000" placeholder="Condições adicionais"><?= $proposalPending ? htmlspecialchars($proposal['notes'] ?? '', ENT_QUOTES, 'UTF-8') : '' ?></textarea></label>
          <button type="submit"><i data-lucide="send"></i> <?= $proposalPending ? 'Atualizar proposta' : 'Enviar proposta' ?></button>
        </form>
      </details>
    <?php endif; ?>

    <?php if (in_array($negotiationStatus, ['open', 'proposal_sent', 'buyer_accepted', 'seller_accepted', 'accepted'], true)): ?>
      <details class="cancel-negotiation reason-action">
        <summary>Cancelar negociação</summary>
        <form method="POST" action="<?= htmlspecialchars(app_url('/negociacoes/cancelar'), ENT_QUOTES, 'UTF-8') ?>">
          <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
          <textarea name="reason" minlength="10" maxlength="1000" required placeholder="Informe o motivo do cancelamento"></textarea>
          <button type="submit">Confirmar cancelamento</button>
        </form>
      </details>
    <?php elseif ($negotiationStatus === 'cancelled'): ?>
      <form class="reopen-negotiation" method="POST" action="<?= htmlspecialchars(app_url('/negociacoes/reabrir'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Reabrir esta negociacao para uma nova proposta?')">
        <?= csrf_field() ?><input type="hidden" name="negotiation_id" value="<?= (int) $negotiation['id'] ?>">
        <button type="submit"><i data-lucide="rotate-ccw"></i> Reabrir negociacao</button>
      </form>
    <?php endif; ?>
    </section>
  </details>

  <section
    class="chat-room"
    id="chatRoom"
    data-negotiation-id="<?= (int) $negotiation['id'] ?>"
    data-company-id="<?= (int) $companyId ?>"
    data-other-company-name="<?= htmlspecialchars($otherCompanyName ?: 'Empresa', ENT_QUOTES, 'UTF-8') ?>"
    data-listing-title="<?= htmlspecialchars($negotiation['listing_title'], ENT_QUOTES, 'UTF-8') ?>"
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
  </div>
</main>

<script src="<?= htmlspecialchars(app_url('/public/js/chat.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php require __DIR__ . '/../components/footer.php'; ?>
