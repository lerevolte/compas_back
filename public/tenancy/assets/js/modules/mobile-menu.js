import { ACTIVE_CLASS } from './constants.js';
import { addClass, hasClass, removeClass } from './utils.js';

export const mobileMenuFunction = () => {
  const headerBtn = document.getElementById('burgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');

  const openMenu = () => {
    addClass(document.body, 'no-scroll');
    addClass(headerBtn, ACTIVE_CLASS);
    addClass(mobileMenu, ACTIVE_CLASS);
    headerBtn.setAttribute('aria-expanded', true);
  };

  const closeMenu = () => {
    removeClass(document.body, 'no-scroll');
    removeClass(headerBtn, ACTIVE_CLASS);
    removeClass(mobileMenu, ACTIVE_CLASS);
    headerBtn.setAttribute('aria-expanded', false);
  };

  const openSubmenu = (target) => {
    const currentMenu = target.nextElementSibling;
    addClass(currentMenu, ACTIVE_CLASS);
  };

  const closeSubmenu = (target) => {
    const currentMenu = target.closest('[data-mobile="menu"]');
    removeClass(currentMenu, ACTIVE_CLASS);
  };

  document.addEventListener('click', ({ target }) => {
    if (window.matchMedia('(max-width: 768px)').matches) {
      const attribute = target.dataset.mobile;
      if (target === headerBtn) {
        if (hasClass(headerBtn, ACTIVE_CLASS)) {
          closeMenu();
        } else {
          openMenu();
        }
      }
      if (attribute === 'btn') {
        openSubmenu(target);
      }
      if (attribute === 'closeSub') {
        closeSubmenu(target);
      }
      if (attribute === 'close') {
        closeMenu();
      }
    }
  });
  document.addEventListener('keydown', ({ key }) => {
    if (window.matchMedia('(max-width: 768px)').matches && key === 'Escape') {
      closeMenu();
    }
  });
};
