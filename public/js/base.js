// ── Init Lucide Icons ──
lucide.createIcons();

// ── MENU DO USUÁRIO ──
const userMenuBtn = document.getElementById('userMenuBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
let menuOpen = false;

if (userMenuBtn && dropdownMenu) {
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        menuOpen = !menuOpen;
        dropdownMenu.classList.toggle('open', menuOpen);
        userMenuBtn.setAttribute('aria-expanded', menuOpen ? 'true' : 'false');
    });
}

// ── CATEGORY DROPDOWN ──
const categoryTrigger = document.getElementById('categoryTrigger');
const categoryDropdown = document.getElementById('categoryDropdown');
const categoryLabel = document.getElementById('categoryLabel');

if (categoryTrigger && categoryDropdown) {
    categoryTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        categoryDropdown.classList.toggle('open');
    });
}

function selectCategory(name, id) {
    if (categoryLabel) {
        categoryLabel.textContent = name;
    }

    if (categoryTrigger) {
        categoryTrigger.classList.add('selected');
    }

    if (categoryDropdown) {
        categoryDropdown.classList.remove('open');
    }

    const catInput = document.getElementById('categoryIdInput');
    if (catInput) {
        catInput.value = id || '';
    }
}

// Deixa a função acessível ao HTML (onclick)
window.selectCategory = selectCategory;

// ── FECHAR MENUS AO CLICAR FORA ──
document.addEventListener('click', (e) => {

    if (
        userMenuBtn &&
        dropdownMenu &&
        !userMenuBtn.contains(e.target) &&
        !dropdownMenu.contains(e.target)
    ) {
        menuOpen = false;
        dropdownMenu.classList.remove('open');
        userMenuBtn.setAttribute('aria-expanded', 'false');
    }

    if (
        categoryTrigger &&
        categoryDropdown &&
        !categoryTrigger.contains(e.target) &&
        !categoryDropdown.contains(e.target)
    ) {
        categoryDropdown.classList.remove('open');
    }

});

// ── HERO SLIDER ──
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.hero-dot');

let current = 0;
let sliderTimer;

if (slides.length > 0 && dots.length > 0) {

    function goTo(idx) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');

        current = (idx + slides.length) % slides.length;

        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function startAuto() {
        sliderTimer = setInterval(() => {
            goTo(current + 1);
        }, 5000);
    }

    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            clearInterval(sliderTimer);
            goTo(Number(dot.dataset.index));
            startAuto();
        });
    });

    startAuto();
} 