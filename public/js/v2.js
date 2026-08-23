document.addEventListener('DOMContentLoaded', () => {
  const navToggle = document.getElementById('headerMobileToggle');
  const primaryNav = document.getElementById('headerPrimaryNav');

  const closeNav = () => {
    if (!navToggle || !primaryNav) return;
    primaryNav.classList.remove('is-open');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'Abrir navegação');
  };

  navToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = !primaryNav?.classList.contains('is-open');
    primaryNav?.classList.toggle('is-open', open);
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    navToggle.setAttribute('aria-label', open ? 'Fechar navegação' : 'Abrir navegação');
  });

  primaryNav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeNav));
  document.addEventListener('click', (event) => {
    if (!primaryNav?.contains(event.target) && !navToggle?.contains(event.target)) closeNav();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeNav();
  });

  const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
  document.querySelectorAll('[data-v2-tab-target]').forEach((tab) => {
    const target = tab.getAttribute('data-v2-tab-target');
    tab.toggleAttribute('aria-current', Boolean(target && currentPath.endsWith(target)));
  });

  document.querySelectorAll('[data-v2-disclosure]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const target = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!target) return;
      const open = trigger.getAttribute('aria-expanded') === 'true';
      trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
      target.hidden = open;
    });
  });

  const helpSearch = document.getElementById('helpSearch');
  const helpCards = Array.from(document.querySelectorAll('[data-help-card]'));
  const helpEmpty = document.getElementById('helpEmpty');
  helpSearch?.addEventListener('input', () => {
    const term = helpSearch.value.trim().toLocaleLowerCase('pt-BR');
    let visible = 0;
    helpCards.forEach((card) => {
      const matches = !term || (card.dataset.search || '').includes(term);
      card.hidden = !matches;
      if (matches) visible += 1;
    });
    if (helpEmpty) helpEmpty.hidden = visible !== 0;
  });
});
