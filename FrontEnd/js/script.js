// ── Init Lucide Icons ──
lucide.createIcons();

// ── THEME TOGGLE ──
const themeToggleBtn  = document.getElementById('themeToggleBtn');
const themeIcon       = document.getElementById('themeIcon');
const htmlEl          = document.documentElement;

// Load saved preference
const savedTheme = localStorage.getItem('resource-theme') || 'light';
htmlEl.setAttribute('data-theme', savedTheme);
themeIcon.setAttribute('data-lucide', savedTheme === 'dark' ? 'sun' : 'moon');
lucide.createIcons();

themeToggleBtn.addEventListener('click', () => {
  const current = htmlEl.getAttribute('data-theme');
  const next    = current === 'dark' ? 'light' : 'dark';
  htmlEl.setAttribute('data-theme', next);
  localStorage.setItem('resource-theme', next);
  themeIcon.setAttribute('data-lucide', next === 'dark' ? 'sun' : 'moon');
  lucide.createIcons();
});

// ── HAMBURGER ──
const hamburgerBtn  = document.getElementById('hamburgerBtn');
const hamburgerIcon = document.getElementById('hamburgerIcon');
const dropdownMenu  = document.getElementById('dropdownMenu');
let menuOpen = false;

hamburgerBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  menuOpen = !menuOpen;
  dropdownMenu.classList.toggle('open', menuOpen);
  hamburgerBtn.classList.toggle('open', menuOpen);
  hamburgerIcon.setAttribute('data-lucide', menuOpen ? 'x' : 'menu');
  lucide.createIcons();
});

// ── CATEGORY DROPDOWN ──
const categoryTrigger  = document.getElementById('categoryTrigger');
const categoryDropdown = document.getElementById('categoryDropdown');
const categoryLabel    = document.getElementById('categoryLabel');

categoryTrigger.addEventListener('click', (e) => {
  e.stopPropagation();
  categoryDropdown.classList.toggle('open');
});

function selectCategory(name) {
  categoryLabel.textContent = name;
  categoryTrigger.classList.add('selected');
  categoryDropdown.classList.remove('open');
}

// ── FECHAR MENUS AO CLICAR FORA ──
document.addEventListener('click', (e) => {
  if (!dropdownMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
    menuOpen = false;
    dropdownMenu.classList.remove('open');
    hamburgerBtn.classList.remove('open');
    hamburgerIcon.setAttribute('data-lucide', 'menu');
    lucide.createIcons();
  }
  categoryDropdown.classList.remove('open');
});

// ── HERO SLIDER ──
const slides   = document.querySelectorAll('.slide');
const dots     = document.querySelectorAll('.hero-dot');
let current    = 0;
let sliderTimer;

function goTo(idx) {
  slides[current].classList.remove('active');
  dots[current].classList.remove('active');
  current = (idx + slides.length) % slides.length;
  slides[current].classList.add('active');
  dots[current].classList.add('active');
}

function startAuto() {
  sliderTimer = setInterval(() => goTo(current + 1), 5000);
}

dots.forEach(dot => {
  dot.addEventListener('click', () => {
    clearInterval(sliderTimer);
    goTo(parseInt(dot.dataset.index));
    startAuto();
  });
});

startAuto();
