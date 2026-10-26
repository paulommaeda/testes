/* Nortume — navegação pública, sem serviços externos. */
(() => {
  'use strict';
  const boot = () => {
    const toggle = document.querySelector('.nt-menu-toggle');
    const menu = document.querySelector('.nt-navigation');
    if (!toggle || !menu) return;
    const close = () => { toggle.setAttribute('aria-expanded', 'false'); menu.classList.remove('is-open'); };
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      menu.classList.toggle('is-open', open);
    });
    menu.addEventListener('click', event => { if (event.target.closest('a')) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { close(); toggle.focus(); } });
    document.addEventListener('click', event => { if (!event.target.closest('.nt-header')) close(); });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
