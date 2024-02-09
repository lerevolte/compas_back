import { ACTIVE_CLASS } from './constants.js';
import { addClass, hasClass, removeClass } from './utils.js';

const attributes = {
  dropdownAttr: '[data-dropdown=""]',
  btnAttr: '[data-dropdown="btn"]',
  subBtnAttr: '[data-dropdown="subBtn"]',
  menuAttr: '[data-dropdown="menu"]',
  subMenuAttr: '[data-dropdown="submenu"]',
};

const classNames = {
  dropdownClass: 'dropdown',
  menuClass: {
    def: 'dropdown__menu',
    align: 'dropdown__menu_align',
    top: 'dropdown__menu_top',
    right: 'dropdown__menu_right',
  },
  listClass: 'dropdown__list',
  itemClass: 'dropdown__item',
};

const hideMenu = (...elems) => {
  elems.forEach((elem) => removeClass(elem, ACTIVE_CLASS));
};

const showMenu = (...elems) => {
  elems.forEach((elem) => addClass(elem, ACTIVE_CLASS));
};

const removeZIndex = (cell) => {
  if (cell && cell.style.zIndex) {
    cell.style.zIndex = '';
  }
};

const addZIndex = (cell) => {
  const zIndexValue = '60';
  if (cell) {
    cell.style.zIndex = zIndexValue;
  }
};

export const closeDropdown = (item) => {
  const { dropdownAttr, btnAttr, menuAttr } = attributes;
  const { dropdownClass } = classNames;
  const dropdown = hasClass(item, dropdownClass) ? item : item.closest(dropdownAttr);
  if (dropdown.hasAttribute('data-noclose') || !dropdown) {
    return;
  }
  const btn = dropdown.querySelector(btnAttr);
  const menu = dropdown.querySelector(menuAttr);
  const tableCell = dropdown.closest('td,th');
  removeZIndex(tableCell);
  hideMenu(dropdown, btn, menu);
};

export const closeAllDropdowns = () => {
  const dropdowns = document.querySelectorAll(attributes.dropdownAttr);
  if (dropdowns.length === 0) {
    return;
  }
  dropdowns.forEach((dropdown) => closeDropdown(dropdown));
};

const alignMenu = (menu) => {
  const { menuClass } = classNames;
  const minDistanceToBottom = 50;
  const minDistanceToRight = 35;
  const wrapper = document.querySelector('.table__wrapper');
  const distanceToRight = window.innerWidth - menu.getBoundingClientRect().right;
  const parentOffset = menu.closest('td').offsetTop;
  const distanceToBottom = wrapper.offsetHeight - parentOffset;
  menu.classList.toggle(menuClass.right, distanceToRight < minDistanceToRight);
  menu.classList.toggle(menuClass.top, distanceToBottom <= minDistanceToBottom);
};

export const initDropdownFunction = () => {
  let dropdownOpenStatus = false;

  document.addEventListener('click', (event) => {

    const { dropdownAttr, btnAttr, menuAttr, subBtnAttr, subMenuAttr } = attributes;
    const { menuClass, listClass, itemClass } = classNames;
    const { target } = event;
    const btn = target.closest(btnAttr);
    const subBtn = target.closest(subBtnAttr);
    
    if (btn) {
      event.preventDefault();
      if (!hasClass(btn, ACTIVE_CLASS)) {
        closeAllDropdowns();
        dropdownOpenStatus = false;
      }
      const dropdown = btn.closest(dropdownAttr);
      const menu = dropdown.querySelector(menuAttr);
      const items = menu.querySelectorAll(`.${itemClass}`);
      const cell = dropdown.closest('td,th');

      if (dropdownOpenStatus) {
        if (hasClass(menu, menuClass.align)) {
          removeClass(menu, menuClass.top);
          removeClass(menu, menuClass.right);
        }
        if (hasClass(dropdown, 'status')) {
          dropdown.parentNode.style.overflow = 'hidden';
        }
        hideMenu(dropdown, btn, menu);
        removeZIndex(cell);
        dropdownOpenStatus = false;
      } else {
        if (hasClass(menu, menuClass.align)) {
          alignMenu(menu);
        }
        if (hasClass(dropdown, 'status')) {
          dropdown.parentNode.style.overflow = 'visible';
        }

        items.forEach((item) => {
          const list = menu.querySelector(`.${listClass}`);
          const submenu = item.querySelector(subMenuAttr);
          hideMenu(item, list, submenu);
        });

        addZIndex(cell);
        showMenu(dropdown, btn, menu);
        dropdownOpenStatus = true;
      }
    }
    if (subBtn) {
      event.preventDefault();
      const item = subBtn.parentNode;
      const subMenu = item.querySelector(subMenuAttr);
      const list = subBtn.closest(`.${listClass}`);
      item.classList.toggle(ACTIVE_CLASS);
      list.classList.toggle(ACTIVE_CLASS);
      subMenu.classList.toggle(ACTIVE_CLASS);
    }
    if (!target.closest(dropdownAttr)) {
      closeAllDropdowns();
      dropdownOpenStatus = false;
    }
  });
};
initDropdownFunction();
