import { closeAllDropdowns } from './dropdown.js';

export const statusFunction = () => {
  document.addEventListener('click', ({ target }) => {
    if (target.closest('.status__item')) {
      const wrapper = target.closest('.status');
      const text = target.querySelector('.status__text');
      const icon = target.querySelector('.status__icon');
      const currentImg = wrapper.querySelector('.status__current-icon');
      const input = wrapper.querySelector('.status__input');
      input.value = text.innerHTML;
      currentImg.style.backgroundColor = icon.style.backgroundColor;
      closeAllDropdowns();
    }
    if (target.closest('.status__select')) {
      const wrapper = target.closest('.status');
      const menu = wrapper.querySelector('.status__list');
      const minDistanceToBottom = 200;
      const tableWrapper = document.querySelector('.table__wrapper');
      const parentOffset = menu.closest('td').offsetTop;
      const distanceToBottom = tableWrapper.offsetHeight - parentOffset;
      menu.classList.toggle('status__list_top', distanceToBottom <= minDistanceToBottom);
    }
  });
};
