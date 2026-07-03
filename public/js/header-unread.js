(function () {
  const badge = document.getElementById('headerUnreadBadge');
  if (!badge || !badge.dataset.unreadUrl) return;

  let updating = false;
  let latestMessageId = Number(badge.dataset.latestMessageId) || 0;

  window.showMessagePopup = function (message) {
    if (!message) return;

    document.querySelector('.message-popup')?.remove();
    const popup = document.createElement('aside');
    popup.className = 'message-popup';
    popup.setAttribute('role', 'status');

    const link = document.createElement('a');
    const basePath = window.APP_BASE_PATH || '';
    link.href = `${basePath}/conversas/abrir?id=${Number(message.negotiation_id)}`;

    const title = document.createElement('strong');
    title.textContent = `Nova mensagem de ${message.sender_company_name || message.sender_name || 'uma empresa'}`;
    const context = document.createElement('span');
    context.textContent = message.listing_title || 'Conversa em andamento';
    const preview = document.createElement('p');
    preview.textContent = String(message.content || 'Nova mensagem recebida.').slice(0, 140);
    link.append(title, context, preview);

    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Fechar aviso');
    close.textContent = '×';
    close.addEventListener('click', () => popup.remove());

    popup.append(link, close);
    document.body.appendChild(popup);
    requestAnimationFrame(() => popup.classList.add('is-visible'));
    setTimeout(() => popup.remove(), 8000);
  };

  function renderCount(count) {
    const normalized = Math.max(0, Number(count) || 0);
    badge.textContent = normalized > 99 ? '99+' : String(normalized);
    badge.setAttribute('aria-label', `${normalized} mensagens não lidas`);
    badge.style.display = normalized > 0 ? 'inline-grid' : 'none';
  }

  async function updateUnreadCount() {
    if (updating || document.hidden) return;
    updating = true;

    try {
      const response = await fetch(badge.dataset.unreadUrl, {
        cache: 'no-store',
        headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (response.ok && data.success) {
        renderCount(data.unread_count);
        const incomingId = Number(data.latest_message?.id) || 0;
        if (incomingId > latestMessageId) {
          latestMessageId = incomingId;
          window.showMessagePopup(data.latest_message);
        }
      }
    } catch (_) {
      // Mantém o último valor conhecido e tenta novamente no próximo ciclo.
    } finally {
      updating = false;
    }
  }

  setInterval(updateUnreadCount, 3000);
  document.addEventListener('visibilitychange', updateUnreadCount);
}());
