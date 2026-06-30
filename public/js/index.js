    // Lucide icons
    lucide.createIcons();

    // Navbar scroll
    const header = document.getElementById('siteHeader');
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 30);
    });

    // Mobile nav
    const toggle = document.getElementById('navToggle');
    const mNav   = document.getElementById('mobileNav');
    toggle.addEventListener('click', () => {
      const open = mNav.classList.toggle('open');
      toggle.innerHTML = open
        ? '<i data-lucide="x"></i>'
        : '<i data-lucide="menu"></i>';
      lucide.createIcons();
    });
    mNav.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        mNav.classList.remove('open');
        toggle.innerHTML = '<i data-lucide="menu"></i>';
        lucide.createIcons();
      });
    });

    // Reveal on scroll
    const revealEls = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    revealEls.forEach(el => io.observe(el));

    // ESG bars animation
    const fills = document.querySelectorAll('.esg-fill');
    const barIO = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          const w = e.target.getAttribute('data-w');
          e.target.style.width = w + '%';
          barIO.unobserve(e.target);
        }
      });
    }, { threshold: 0.5 });
    fills.forEach(f => { f.style.width = '0'; barIO.observe(f); });