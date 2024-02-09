import { HIDDEN_CLASS } from './constants.js';
import { createMenuItem } from './table.js';
import { addClass, removeClass } from './utils.js';

const header = document.getElementById('header');
const headerMenus = {
  display: header.querySelector('#headerDisplayMenu'),
  order: header.querySelector('#headerOrderMenu'),
};
export function headerFunction() {
  const items = header.querySelectorAll('.header-breadcrumbs__link');
  for (const [index, item] of Object.entries(items)) {
    const label = item.innerText;

    if (headerMenus.display) {
      headerMenus.display.appendChild(
        createMenuItem(label, index).checkbox('header-display', true),
      );
    }
    if (headerMenus.order) {
      headerMenus.order.appendChild(createMenuItem(label, index).drag('header-order'));
    }
  }
}

export const headerOrderFunction = (activeIndex, prevIndex) => {
  const items = document.querySelectorAll('.header-breadcrumbs__link');
  const activeItem = items[activeIndex];
  const prevItem = items[prevIndex];

  if (activeIndex > prevIndex) {
    activeItem.parentNode.insertBefore(activeItem, prevItem);
  } else {
    prevItem.parentNode.insertBefore(activeItem, prevItem.nextElementSibling);
  }
};

const displayHeaderFunction = (target, id) => {
  const deleteItem = header.querySelector('.header-breadcrumbs').children[id];

  if (!target.checked) {
    addClass(deleteItem, HIDDEN_CLASS);
    headerMenus.order.children[id].style.display = 'none';
  } else {
    removeClass(deleteItem, HIDDEN_CLASS);
    headerMenus.order.children[id].style.display = '';
  }
};

document.addEventListener('change', (evt) => {
  if (evt.target.id.includes('header-display')) {
    const id = parseInt(evt.target.id.match(/\d+/));
    displayHeaderFunction(evt.target, id);
  }
});
