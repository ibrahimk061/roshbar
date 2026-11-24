// Roshbar — small enhancements. Every page works without this file.
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* Mobile menu */
  const toggle = $('[data-nav-toggle]');
  const nav = $('#site-nav');
  if (toggle && nav) {
    const setOpen = (open) => {
      nav.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', () => setOpen(!nav.classList.contains('open')));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    document.addEventListener('click', (e) => {
      if (!nav.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
    });
  }

  /* Header shadow once the page scrolls */
  const header = $('[data-header]');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Gentle reveal for cards as they enter the screen */
  const items = $$('.card, .feature, .cat, .season-item, .value, .contact-card');
  if ('IntersectionObserver' in window && matchMedia('(prefers-reduced-motion: no-preference)').matches) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        en.target.classList.add('in');
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -40px 0px' });
    items.forEach((el, i) => {
      if (el.getBoundingClientRect().top < innerHeight) return; // already visible: leave as is
      el.classList.add('reveal');
      el.style.transitionDelay = (i % 4) * 70 + 'ms';
      io.observe(el);
    });
  }
})();
