(function () {
  const list = document.getElementById('conversationList');
  if (!list || !list.dataset.listUrl) return;

  const status = document.getElementById('conversationLiveStatus');
  const statusText = document.getElementById('conversationLiveText');
  let updating = false;
  let lastSignature = '';

  function setStatus(state, text) {
    if (!status || !statusText) return;
    status.classList.remove('is-online', 'is-syncing', 'is-error');
    status.classList.add(`is-${state}`);
    statusText.textContent = text;
  }

  function formatDate(value) {
    const date = new Date(String(value || '').replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('pt-BR', {
      day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
    }).replace(',', '');
  }

  function createConversationCard(conversation) {
    const unread = Math.max(0, Number(conversation.unread_count) || 0);
    const card = document.createElement('a');
    card.className = `conversation-card${unread > 0 ? ' has-unread' : ''}`;
    card.href = `${window.APP_BASE_PATH || ''}/conversas/abrir?id=${Number(conversation.id)}`;

    const avatar = document.createElement('div');
    avatar.className = 'conversation-avatar';
    avatar.innerHTML = '<i data-lucide="building-2"></i>';

    const content = document.createElement('div');
    content.className = 'conversation-content';
    const top = document.createElement('div');
    top.className = 'conversation-topline';
    const company = document.createElement('strong');
    company.textContent = conversation.other_company_name || 'Empresa';
    const time = document.createElement('time');
    time.textContent = formatDate(conversation.last_message_at || conversation.created_at);
    top.append(company, time);

    const listing = document.createElement('span');
    listing.className = 'conversation-listing';
    listing.textContent = conversation.listing_title || 'Anúncio';
    const preview = document.createElement('p');
    preview.textContent = conversation.last_message || 'Conversa iniciada. Envie a primeira mensagem.';
    content.append(top, listing, preview);
    card.append(avatar, content);

    if (unread > 0) {
      const badge = document.createElement('span');
      badge.className = 'unread-badge';
      badge.setAttribute('aria-label', `${unread} mensagens não lidas`);
      badge.textContent = unread > 99 ? '99+' : String(unread);
      card.appendChild(badge);
    }

    const arrow = document.createElement('i');
    arrow.className = 'conversation-arrow';
    arrow.setAttribute('data-lucide', 'chevron-right');
    card.appendChild(arrow);
    return card;
  }

  function render(conversations) {
    list.replaceChildren();
    if (!conversations.length) {
      const empty = document.createElement('div');
      empty.className = 'chat-empty';
      empty.innerHTML = '<i data-lucide="messages-square"></i><h2>Nenhuma conversa por enquanto</h2><p>Abra um anúncio de outra empresa e clique em “Entrar em contato”.</p>';
      list.appendChild(empty);
    } else {
      conversations.forEach((conversation) => list.appendChild(createConversationCard(conversation)));
    }
    if (window.lucide) window.lucide.createIcons();
  }

  async function updateConversations() {
    if (updating || document.hidden) return;
    updating = true;
    setStatus('syncing', 'Atualizando conversas...');

    try {
      const response = await fetch(list.dataset.listUrl, {
        cache: 'no-store',
        headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao atualizar.');

      const signature = JSON.stringify(data.conversations);
      if (signature !== lastSignature) {
        lastSignature = signature;
        render(data.conversations);
      }
      setStatus('online', `Atualizado às ${new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);
    } catch (_) {
      setStatus('error', 'Falha ao atualizar — nova tentativa em 3s');
    } finally {
      updating = false;
    }
  }

  setInterval(updateConversations, 3000);
  document.addEventListener('visibilitychange', updateConversations);
}());
