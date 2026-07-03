/* ══════════════════════════════════════════════
   RE.SOURCE — LANDING PAGE (INDEX) — interações
   ══════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  /* ícones lucide */
  if (window.lucide) lucide.createIcons();

  /* ── menu mobile ── */
  const navToggle = document.getElementById('navToggle');
  const mobileNav = document.getElementById('mobileNav');
  const siteHeader = document.getElementById('siteHeader');

  if (navToggle && mobileNav) {
    navToggle.addEventListener('click', () => {
      mobileNav.classList.toggle('open');
      const icon = mobileNav.classList.contains('open') ? 'x' : 'menu';
      navToggle.innerHTML = `<i data-lucide="${icon}"></i>`;
      if (window.lucide) lucide.createIcons();
    });

    mobileNav.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        mobileNav.classList.remove('open');
        navToggle.innerHTML = `<i data-lucide="menu"></i>`;
        if (window.lucide) lucide.createIcons();
      });
    });
  }

  /* ── header: sombra ao rolar ── */
  const onScroll = () => {
    if (siteHeader) {
      siteHeader.classList.toggle('scrolled', window.scrollY > 12);
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── reveal on scroll ── */
  const revealEls = document.querySelectorAll('.reveal');
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');

        /* aciona as barras do widget ESG quando revelado */
        entry.target.querySelectorAll('.esg-fill').forEach(bar => {
          const w = bar.getAttribute('data-w');
          if (w) requestAnimationFrame(() => { bar.style.width = w + '%'; });
        });

        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  revealEls.forEach(el => revealObserver.observe(el));

  /* ── destaque do item ativo na nav ── */
  const sections = ['como-funciona', 'categorias', 'impacto', 'seguranca']
    .map(id => document.getElementById(id))
    .filter(Boolean);
  const navLinks = document.querySelectorAll('.main-nav a');

  if (sections.length && navLinks.length) {
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          navLinks.forEach(link => {
            link.classList.toggle('active', link.dataset.section === entry.target.id);
          });
        }
      });
    }, { threshold: 0.4, rootMargin: '-15% 0px -55% 0px' });

    sections.forEach(sec => sectionObserver.observe(sec));
  }

});