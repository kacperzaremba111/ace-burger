/* =========================================================
   FAST FOOD — main.js
   Vanilla JS (ES6+), zwykły skrypt ładowany z atrybutem "defer"
   ========================================================= */

/* ---------- Rok w stopce ---------- */
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = new Date().getFullYear();

/* ---------- Menu mobilne (hamburger) ---------- */
const navToggle = document.getElementById('navToggle');
const primaryNav = document.getElementById('primary-nav');
const mainEl = document.getElementById('main');
const footerEl = document.querySelector('.site-footer');

// Elementy poza panelem nawigacji, które trzeba wyłączyć z tabulacji
// i czytników ekranu, gdy menu mobilne jest otwarte (proste "focus trapping").
function setBackgroundInert(isInert) {
  [mainEl, footerEl].forEach((el) => {
    if (!el) return;
    if (isInert) el.setAttribute('inert', '');
    else el.removeAttribute('inert');
  });
}

function openNav() {
  primaryNav.classList.add('is-open');
  navToggle.setAttribute('aria-expanded', 'true');
  navToggle.setAttribute('aria-label', 'Zamknij menu nawigacji');
  document.body.style.overflow = 'hidden';
  setBackgroundInert(true);
  // Przenieś fokus do pierwszego linku w otwartym panelu
  primaryNav.querySelector('a')?.focus();
}

function closeNav() {
  primaryNav.classList.remove('is-open');
  navToggle.setAttribute('aria-expanded', 'false');
  navToggle.setAttribute('aria-label', 'Otwórz menu nawigacji');
  document.body.style.overflow = '';
  setBackgroundInert(false);
}

if (navToggle && primaryNav) {
  navToggle.addEventListener('click', () => {
    const isOpen = primaryNav.classList.contains('is-open');
    isOpen ? closeNav() : openNav();
  });

  // Zamknij menu po kliknięciu w link (mobile)
  primaryNav.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => closeNav());
  });

  // Dostępność: zamknięcie menu klawiszem Escape
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && primaryNav.classList.contains('is-open')) {
      closeNav();
      navToggle.focus();
    }
  });
}

/* ---------- Scroll-reveal (IntersectionObserver) ---------- */
const revealTargets = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window && revealTargets.length) {
  const revealObserver = new IntersectionObserver(
    (entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );

  revealTargets.forEach((target) => revealObserver.observe(target));
} else {
  // Brak wsparcia dla IntersectionObserver — pokaż od razu
  revealTargets.forEach((target) => target.classList.add('is-visible'));
}

/* ---------- Podświetlanie aktywnej sekcji w nawigacji ---------- */
const sections = document.querySelectorAll('main [id]');
const navLinks = document.querySelectorAll('.primary-nav__link');

if ('IntersectionObserver' in window && sections.length && navLinks.length) {
  const linkFor = (id) => document.querySelector(`.primary-nav__link[href="#${id}"]`);

  const navObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        const link = linkFor(entry.target.id);
        if (!link) return;
        if (entry.isIntersecting) {
          navLinks.forEach((l) => {
            l.classList.remove('is-active');
            l.removeAttribute('aria-current');
          });
          link.classList.add('is-active');
          link.setAttribute('aria-current', 'page');
        }
      });
    },
    { rootMargin: '-45% 0px -50% 0px', threshold: 0 }
  );

  sections.forEach((section) => navObserver.observe(section));
}
