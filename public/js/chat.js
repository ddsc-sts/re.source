(function () {
  const room = document.getElementById('chatRoom');
  if (!room) return;

  const list = document.getElementById('messageList');
  const form = document.getElementById('messageForm');
  const input = document.getElementById('messageContent');
  const button = document.getElementById('sendMessageButton');
  const errorBox = document.getElementById('messageFormError');
  const syncStatus = document.getElementById('chatSyncStatus');
  const syncText = document.getElementById('chatSyncText');
  const negotiationId = Number(room.dataset.negotiationId);
  const companyId = Number(room.dataset.companyId);
  let polling = false;

  function setSyncStatus(state, message) {
    if (!syncStatus || !syncText) return;
    syncStatus.classList.remove('is-online', 'is-syncing', 'is-error');
    syncStatus.classList.add(`is-${state}`);
    syncText.textContent = message;
  }

  const lastMessageId = () => {
    const messages = list.querySelectorAll('[data-message-id]');
    return messages.length ? Number(messages[messages.length - 1].dataset.messageId) : 0;
  };

  function appendMessage(message) {
    if (list.querySelector(`[data-message-id="${Number(message.id)}"]`)) return;
    document.getElementById('emptyMessages')?.remove();

    const own = Number(message.sender_company_id) === companyId;
    const article = document.createElement('article');
    article.className = `message ${own ? 'message-own' : 'message-received'}`;
    article.dataset.messageId = Number(message.id);

    const sender = document.createElement('span');
    sender.className = 'message-sender';
    sender.textContent = own ? 'Você' : message.sender_name;

    const content = document.createElement('p');
    content.textContent = message.content;

    const time = document.createElement('time');
    const parsedDate = new Date(String(message.created_at).replace(' ', 'T'));
    time.textContent = Number.isNaN(parsedDate.getTime())
      ? message.created_at
      : parsedDate.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

    article.append(sender, content, time);
    list.appendChild(article);
    list.scrollTop = list.scrollHeight;
  }

  async function pollMessages() {
    if (polling || document.hidden) return;
    polling = true;
    setSyncStatus('syncing', 'Atualizando mensagens...');
    try {
      const url = new URL(room.dataset.messagesUrl, window.location.origin);
      url.searchParams.set('negotiation_id', negotiationId);
      url.searchParams.set('after_id', lastMessageId());
      const response = await fetch(url, {
        cache: 'no-store',
        headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao atualizar mensagens.');
      let negotiationStateChanged = false;
      data.messages.forEach((message) => {
        appendMessage(message);
        if (Number(message.sender_company_id) !== companyId && /^(📄|👍|✅|❌|🚫)/u.test(String(message.content))) {
          negotiationStateChanged = true;
        }
        if (Number(message.sender_company_id) !== companyId && typeof window.showMessagePopup === 'function') {
          window.showMessagePopup({
            ...message,
            negotiation_id: negotiationId,
            sender_company_name: room.dataset.otherCompanyName,
            listing_title: room.dataset.listingTitle
          });
        }
      });
      setSyncStatus('online', `Atualizado às ${new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);
      if (negotiationStateChanged) {
        setSyncStatus('syncing', 'Atualizando o estado da proposta...');
        setTimeout(() => window.location.reload(), 700);
      }
    } catch (error) {
      errorBox.textContent = error.message;
      setSyncStatus('error', 'Falha ao atualizar — nova tentativa em 3s');
    } finally {
      polling = false;
    }
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    errorBox.textContent = '';
    button.disabled = true;
    try {
      const response = await fetch(room.dataset.sendUrl, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Não foi possível enviar.');
      appendMessage(data.message);
      input.value = '';
      input.focus();
    } catch (error) {
      errorBox.textContent = error.message;
    } finally {
      button.disabled = false;
    }
  });

  input.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  list.scrollTop = list.scrollHeight;
  setSyncStatus('online', 'Atualização automática ativa');
  setInterval(pollMessages, 3000);
  document.addEventListener('visibilitychange', pollMessages);
}());
