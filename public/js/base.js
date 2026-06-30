// ── Init Lucide Icons ──
lucide.createIcons();

// ── HAMBURGER ──
const hamburgerBtn = document.getElementById('hamburgerBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
let menuOpen = false;

hamburgerBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  menuOpen = !menuOpen;
  dropdownMenu.classList.toggle('open', menuOpen);
  hamburgerBtn.classList.toggle('open', menuOpen);
});

// ── CATEGORY DROPDOWN ──
const categoryTrigger  = document.getElementById('categoryTrigger');
const categoryDropdown = document.getElementById('categoryDropdown');
const categoryLabel    = document.getElementById('categoryLabel');

categoryTrigger.addEventListener('click', (e) => {
  e.stopPropagation();
  categoryDropdown.classList.toggle('open');
});

function selectCategory(name, id) {
  categoryLabel.textContent = name;
  categoryTrigger.classList.add('selected');
  categoryDropdown.classList.remove('open');
  const catInput = document.getElementById('categoryIdInput');
  if (catInput) catInput.value = id || '';
}

// ── FECHAR MENUS AO CLICAR FORA ──
document.addEventListener('click', (e) => {
  if (!dropdownMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
    menuOpen = false;
    dropdownMenu.classList.remove('open');
    hamburgerBtn.classList.remove('open');
  }
  if (!categoryTrigger.contains(e.target) && !categoryDropdown.contains(e.target)) {
    categoryDropdown.classList.remove('open');
  }
});

// ── HERO SLIDER ──
const slides = document.querySelectorAll('.slide');
const dots   = document.querySelectorAll('.hero-dot');
let current  = 0;
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