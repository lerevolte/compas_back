import { ACTIVE_CLASS, TABLE_ROW_CLASS } from './constants.js';
import { closeDropdown } from './dropdown.js';
import { addClass, hasClass } from './utils.js';
import { initTableFunction } from './table.js';
import { resetEditControlPanel } from './tableEditor.js';

var rows = document.querySelectorAll(`.${TABLE_ROW_CLASS}`);

export const getUrlParam = (name) => {
  const results = new RegExp(`[\\?&]${name}=([^&#]*)`).exec(window.location.href);
  return results ? decodeURI(results[1]) : null;
};

const showItems = (pageNumber, rowsPerPage, totalPages) => {
  resetEditControlPanel();
  
  const container = document.querySelector('.table-body');
  container.innerHTML = '';
  const startIndex = (pageNumber - 1) * rowsPerPage;
  const endIndex = pageNumber * rowsPerPage;
  const pageData = [...rows].slice(startIndex, endIndex);
  if (pageData.length === 0) {
    window.location.search = `?page=${totalPages}`;
  }
  pageData.forEach((data) => container.appendChild(data));
  statusFieldInit();
  initTableFunction();
  $('tr.selected').each(function(){
    $(this).removeClass('selected')
  })
};

const renderLink = ({ page, content, state, isActive }) => {
  const link = document.createElement('LI');
  const inactiveLink = '<span class="pagination__link">...</span>';
  const activeLink = `<a class="pagination__link" href="${page}">${content}</a>`;
  addClass(link, 'pagination__item');

  if (state) {
    link.innerHTML = activeLink;
  } else {
    link.innerHTML = inactiveLink;
    addClass(link, 'inactive');
  }
  if (isActive) {
    addClass(link, ACTIVE_CLASS);
  }
  return link;
};

const appendLinks = (pageNumber, totalPages) => {
  const links = [];
  const minElemsCount = 7;
  const countOfLastElems = 3;
  const dotsElemIndex = 4;
  const linksContainer = document.querySelector('.pagination__list');

  for (let index = 1; index <= totalPages; index++) {
    const linkOptions = {
      page: `?page=${index}`,
      content: index,
      state: true,
      isActive: index === pageNumber,
    };
    const link = renderLink(linkOptions);
    links.push(link);
  }

  if (totalPages <= minElemsCount) {
    linksContainer.innerHTML = '';
    links.forEach(function(link) {
      linksContainer.appendChild(link);
      if(totalPages < 2) {
        console.log('TOTAL PAGES < 2');
        console.log(link)
        addClass(link, 'rounded-1');
      }
    });
    return;
  }
  const startSliceElemsCount = 2;
  let startIndex = totalPages - dotsElemIndex + 1;
  let endIndex = totalPages - countOfLastElems - dotsElemIndex + 1;
  if (pageNumber > startSliceElemsCount) {
    startIndex -= pageNumber - startSliceElemsCount;
    endIndex -= pageNumber - startSliceElemsCount;
  }
  if (totalPages - pageNumber >= 5) {
    links.splice(0, pageNumber - startSliceElemsCount);
  } else {
    links.splice(0, 3);
  }

  if (endIndex > 0) {
    links.splice(dotsElemIndex - 1, 0, renderLink({ state: false }));
    links.splice(-startIndex, endIndex);
  }

  linksContainer.innerHTML = '';
  console.log('totalPages')
  console.log(totalPages)
  links.forEach(function(link) {
    linksContainer.appendChild(link);
    if(totalPages < 2) {
      console.log('TOTAL PAGES < 2');
      console.log(link)
      addClass(link, 'rounded-1');
    }
  });
  
};

const init = (rowsPerPage) => {
  const pageNumber = parseInt(getUrlParam('page'), 10) || 1;
  const totalRows = rows.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage);
  showItems(pageNumber, rowsPerPage, totalPages);
  appendLinks(pageNumber, totalPages);
};

export const initPagination = () => {
  const storageName = 'itemsPerPage';
  const select = document.querySelector('#paginationSelect');
  const selectValue = select.querySelector('.select-current');
  const storageCount = parseInt(JSON.parse(localStorage.getItem(storageName)));
  if (storageCount) {
    selectValue.innerHTML = storageCount;
  }
  const itemsPerPage = storageCount || parseInt(selectValue.innerHTML, 10);
  init(itemsPerPage);
  select.closest('.dropdown').addEventListener('click', ({ target }) => {
    if (hasClass(target, 'select__input')) {
      const { value } = target;
      selectValue.innerHTML = value;
      init(parseInt(value, 10));
      closeDropdown(target);
      localStorage.setItem(storageName, value);
    }
  });
};
