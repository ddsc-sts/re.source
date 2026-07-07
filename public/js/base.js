document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }

  const userMenuBtn = document.getElementById('userMenuBtn');
  const dropdownMenu = document.getElementById('dropdownMenu');
  const categoryTrigger = document.getElementById('categoryTrigger');
  const categoryDropdown = document.getElementById('categoryDropdown');
  const categoryLabel = document.getElementById('categoryLabel');
  const categoryInput = document.getElementById('categoryIdInput');
  const notificationButton = document.getElementById('notificationButton');
  const notificationPanel = document.getElementById('notificationPanel');
  const notificationList = document.getElementById('notificationList');
  const markNotificationsRead = document.getElementById('markNotificationsRead');

  function setOpen(button, panel, open) {
    if (!button || !panel) return;
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.hidden = !open;
    panel.classList.toggle('open', open);
  }

  if (userMenuBtn && dropdownMenu) {
    userMenuBtn.addEventListener('click', (event) => {
      event.stopPropagation();
      const open = !dropdownMenu.classList.contains('open');
      dropdownMenu.classList.toggle('open', open);
      userMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      setOpen(notificationButton, notificationPanel, false);
    });
  }

  if (categoryTrigger && categoryDropdown) {
    categoryTrigger.addEventListener('click', (event) => {
      event.stopPropagation();
      categoryDropdown.classList.toggle('open');
    });

    categoryDropdown.addEventListener('click', (event) => {
      const option = event.target.closest('[data-category-id]');
      if (!option) return;
      categoryLabel.textContent = option.dataset.categoryName || 'Todas as categorias';
      categoryInput.value = option.dataset.categoryId || '';
      categoryTrigger.classList.toggle('selected', Boolean(categoryInput.value));
      categoryDropdown.classList.remove('open');
    });
  }

  function notificationLink(item) {
    let data = {};
    try { data = JSON.parse(item.data_json || '{}'); } catch (_) {}
    const base = window.APP_BASE_PATH || '';
    if (data.negotiation_id) return `${base}/conversas/abrir?id=${Number(data.negotiation_id)}`;
    if (String(item.type).startsWith('withdrawal_')) return `${base}/estatisticas`;
    if (String(item.type).startsWith('freight_')) return `${base}/entregas`;
    if (String(item.type).startsWith('account_')) return `${base}/conta`;
    return `${base}/base`;
  }

  function renderNotifications(items) {
    if (!notificationList) return;
    notificationList.replaceChildren();
    if (!items.length) {
      const empty = document.createElement('p');
      empty.className = 'notification-empty';
      empty.textContent = 'Nenhuma notificação por enquanto.';
      notificationList.append(empty);
      return;
    }
    items.forEach((item) => {
      const link = document.createElement('a');
      link.className = `notification-item${Number(item.is_seen) ? '' : ' is-unseen'}`;
      link.href = notificationLink(item);
      const title = document.createElement('strong');
      title.textContent = item.title || 'Atualização';
      const body = document.createElement('span');
      body.textContent = item.body || '';
      const time = document.createElement('time');
      time.textContent = item.created_at || '';
      link.append(title, body, time);
      notificationList.append(link);
    });
  }

  async function loadNotifications() {
    if (!notificationButton?.dataset.feedUrl) return;
    try {
      const response = await fetch(notificationButton.dataset.feedUrl, {
        cache: 'no-store', headers: { Accept: 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao carregar.');
      renderNotifications(data.notifications || []);
      notificationButton.classList.toggle('has-unread', Number(data.unseen_count) > 0);
      if (Number(data.unseen_count) > 0) {
        await markAllNotificationsRead();
      }
    } catch (_) {
      if (notificationList) notificationList.innerHTML = '<p class="notification-empty">Não foi possível carregar as notificações.</p>';
    }
  }

  async function markAllNotificationsRead() {
    if (!notificationButton?.dataset.readUrl) return;
    const body = new URLSearchParams({ csrf_token: notificationButton.dataset.csrf || '' });
    const response = await fetch(notificationButton.dataset.readUrl, {
      method: 'POST', body, headers: { Accept: 'application/json' }
    });
    if (response.ok) {
      notificationButton.classList.remove('has-unread');
      document.getElementById('notificationCount')?.replaceChildren(document.createTextNode('0'));
      notificationList?.querySelectorAll('.is-unseen').forEach((item) => item.classList.remove('is-unseen'));
    }
  }

  if (notificationButton && notificationPanel) {
    notificationButton.addEventListener('click', async (event) => {
      event.stopPropagation();
      const open = notificationPanel.hidden;
      setOpen(notificationButton, notificationPanel, open);
      if (dropdownMenu) dropdownMenu.classList.remove('open');
      if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'false');
      if (open) await loadNotifications();
    });
  }

  markNotificationsRead?.addEventListener('click', async () => {
    await markAllNotificationsRead();
  });

  document.addEventListener('click', (event) => {
    if (userMenuBtn && dropdownMenu && !userMenuBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
      dropdownMenu.classList.remove('open');
      userMenuBtn.setAttribute('aria-expanded', 'false');
    }
    if (categoryTrigger && categoryDropdown && !categoryTrigger.contains(event.target) && !categoryDropdown.contains(event.target)) {
      categoryDropdown.classList.remove('open');
    }
    if (notificationButton && notificationPanel && !notificationButton.contains(event.target) && !notificationPanel.contains(event.target)) {
      setOpen(notificationButton, notificationPanel, false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    dropdownMenu?.classList.remove('open');
    categoryDropdown?.classList.remove('open');
    if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'false');
    setOpen(notificationButton, notificationPanel, false);
  });

  const slides = Array.from(document.querySelectorAll('.slide'));
  const dots = Array.from(document.querySelectorAll('.hero-dot'));
  if (slides.length && dots.length) {
    let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('active')));
    let timer;
    const goTo = (index) => {
      slides.forEach((slide, i) => slide.classList.toggle('active', i === index));
      dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
      current = index;
    };
    const start = () => {
      clearInterval(timer);
      timer = window.setInterval(() => goTo((current + 1) % slides.length), 5000);
    };
    dots.forEach((dot, index) => dot.addEventListener('click', () => { goTo(index); start(); }));
    document.addEventListener('visibilitychange', () => document.hidden ? clearInterval(timer) : start());
    goTo(current);
    start();
  }
});
