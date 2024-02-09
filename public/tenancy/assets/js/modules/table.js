/* eslint-disable max-len */
/* eslint-disable no-loop-func */
import { columnResize } from './columnResize.js';
import {
  DRAGGABLE_CLASS,
  HIDDEN_CLASS,
  SORTED_CLASS,
  STICKY_CLASS,
  TABLE_ROW_CLASS,
} from './constants.js';
import { initDragFunction } from './drag.js';
import { addClass, getTableColumn, hasClass, removeClass } from './utils.js';

const table = document.querySelector('.table__inner');
const tableMenus = {
  display: document.getElementById('displayMenu'),
  fix: document.getElementById('fixMenu'),
  order: document.getElementById('orderMenu'),
  filter: document.getElementById('filterMenu'),
};
var settings_sorting = [];
export const activateSaveBtn = () => {
  const tableSaveBtn = document.querySelector('.table__change-btn');
  tableSaveBtn.style.display = 'inline-block';
};

export function createMenuItem(label, count) {
  const item = document.createElement('li');
  const itemClass = 'dropdown__item';

  addClass(item, itemClass);
  let pattern = '';
  return {
    checkbox(inputName, isChecked) {
      const id = `${inputName}-column-${count}`;
      const state = isChecked ? 'checked' : '';
      pattern = `
      <label class="form-checkbox__label dropdown__link" for="${id}">
        <input class="form-checkbox__input" type="checkbox" id="${id}" ${state} tabIndex="0">
        <span class="form-checkbox__switcher form-checkbox__switcher_sm"></span>
        <span class="form-checkbox__text">${label}</span>
      </label>`;
      item.innerHTML = pattern;
      // item.tabIndex = '0';
      return item;
    },
    drag(dataName) {
      addClass(item, 'dropdown__link');
      addClass(item, 'drag-item');
      pattern = `
      <button class="drag-btn dropdown-drag__btn btn-clear" type="button" draggable="true">
        <img src="/tenancy/assets/img/icons/drag.svg" alt="Drag icon">
      </button>
      ${label}`;
      item.setAttribute(`data-${dataName}`, count);
      item.innerHTML = pattern;
      return item;
    },
  };
}

function updateAllMenus(table, menu, menuName) {
  if (!table) {
    return;
  }
  const headerItems = table.querySelectorAll('.table-header__item');
  menu.innerHTML = '';
  headerItems.forEach((item, index) => {
    var label = item.innerText;
    if(!label) {
      label = $(item).find('.table-header__label').text();
    }
    if (!hasClass(item, HIDDEN_CLASS)) {
      if (menuName === 'table-fix') {
        const state = hasClass(item, STICKY_CLASS);
        var el = createMenuItem(label, index).checkbox(menuName, state);
        menu.appendChild(el);
        if(hasClass(item, 'sticky-start')) {
          removeClass(item, 'sticky-start');
          $(el).find('input').trigger('click');
          fixedItem(index).onChecked(document.getElementById('table-fix-column-'+index));
        }
      }
      if (menuName === 'table-display') {
        menu.appendChild(createMenuItem(label, index).checkbox(menuName, true));
      }
      if (menuName === 'table-order') {
        menu.appendChild(createMenuItem(label, index).drag(menuName));
        initDragFunction();
      }
    } else if (menuName === 'table-display') {
      menu.appendChild(createMenuItem(label, index).checkbox(menuName, false));
    }
  });
}

export const fixedItem = (id) => {
  const table = document.querySelector('.table__inner');
  const currentColumn = getTableColumn(table, id);
  const headerItems = [...table.querySelectorAll('.table-header__item')];
  const currentItem = headerItems[id];
  const nextItems = headerItems.slice(id + 1, headerItems.length);
  const prevItems = headerItems.slice(0, id);
  const prevActiveItems = [];
  const nextActiveItems = [];
  let prevWidth = 0;
  let nextWidth = 0;
  return {
    update() {
      prevItems.forEach((item) => {
        if (hasClass(item, STICKY_CLASS)) {
          prevActiveItems.push(item);
        }
      });
      nextItems.forEach((item) => {
        if (hasClass(item, STICKY_CLASS)) {
          nextActiveItems.push(item);
        }
      });

      for (const item of prevActiveItems) {
        const lastPrevItem = prevActiveItems[prevActiveItems.length - 1];
        const prevFullWidth = lastPrevItem.offsetWidth + prevWidth - 1;
        console.log('prevWidth')
        console.log(prevFullWidth);
        const column = getTableColumn(table, item.cellIndex);
        column.forEach((cell) => (cell.style.left = prevWidth + 'px'));
        currentColumn.forEach((cell) => {
          //cell.style.left = prevFullWidth + 'px';
        });
        prevWidth += item.offsetWidth;
      }
      for (const item of nextActiveItems) {
        nextWidth += currentItem.offsetWidth;
        currentColumn.forEach((cell) => (cell.style.left = `${prevWidth}px`));
        const column = getTableColumn(table, item.cellIndex);
        const totalWidth = nextWidth + prevWidth + 'px';
        column.forEach((cell) => (cell.style.left = totalWidth));
        nextWidth += item.offsetWidth - currentItem.offsetWidth;
      }
    },
    onChecked(target) {
      const activeItems = table.querySelectorAll('.table-header__item.sticky');
      if (target.checked) {
        if (activeItems.length === 0) {
          for (const cell of currentColumn) {
            addClass(cell, STICKY_CLASS);
            cell.style.left = '0';
          }
        } else {
          currentColumn.forEach((cell) => {
            addClass(cell, STICKY_CLASS);
          });

          prevItems.forEach((item) => {
            if (hasClass(item, STICKY_CLASS)) {
              prevActiveItems.push(item);
            }
          });
          nextItems.forEach((item) => {
            if (hasClass(item, STICKY_CLASS)) {
              nextActiveItems.push(item);
            }
          });

          for (const item of prevActiveItems) {
            const lastPrevItem = prevActiveItems[prevActiveItems.length - 1];
            const prevFullWidth = lastPrevItem.offsetWidth + prevWidth - 1;
            const column = getTableColumn(table, item.cellIndex);
            column.forEach((cell) => (cell.style.left = prevWidth + 'px'));
            currentColumn.forEach((cell) => {
              cell.style.left = prevFullWidth + 'px';
            });
            prevWidth += item.offsetWidth;
          }

          for (const item of nextActiveItems) {
            currentColumn.forEach((cell) => {
              cell.style.left = prevWidth + 'px';
            });
            nextWidth += currentItem.offsetWidth;
            const column = getTableColumn(table, item.cellIndex);
            const totalWidth = nextWidth + prevWidth + 'px';
            column.forEach((cell) => (cell.style.left = totalWidth));
            nextWidth += item.offsetWidth - currentItem.offsetWidth;
          }
        }
      } else {
        for (const cell of currentColumn) {
          removeClass(cell, STICKY_CLASS);
          cell.style.left = '';
        }
        prevItems.forEach((item) => {
          if (hasClass(item, STICKY_CLASS)) {
            prevActiveItems.push(item);
          }
        });
        nextItems.forEach((item) => {
          if (hasClass(item, STICKY_CLASS)) {
            nextActiveItems.push(item);
          }
        });
        prevActiveItems.forEach((item) => (prevWidth += item.offsetWidth));
        nextWidth = prevWidth;
        for (const item of nextActiveItems) {
          const column = getTableColumn(table, item.cellIndex);
          column.forEach((cell) => (cell.style.left = nextWidth + 'px'));
          nextWidth += item.offsetWidth;
        }
      }
    },
  };
};

const checkMenuHiddenItems = (menu) => {
  const hiddenItems = table.querySelectorAll('.table-header__item.hidden');
  for (const hiddenItem of hiddenItems) {
    const id = parseInt(hiddenItem.dataset.header, 10);
    const item = menu.querySelector(`#table-display-column-${id}`);
    item.checked = false;
  }
};

const deleteItemFunction = (target, id) => {
  const deleteColumn = getTableColumn(table, id);
  const [deleteHeaderCell] = deleteColumn;
  const { fix, order } = tableMenus;
  if (!target.checked) {
    deleteColumn.forEach((cell) => addClass(cell, HIDDEN_CLASS));
    fix.children[id].style.display = 'none';
    order.children[id].style.display = 'none';
    // fixedItem(id).onChecked(target);
    fixedItem(id).update();
  } else {
    deleteColumn.forEach((cell) => removeClass(cell, HIDDEN_CLASS));
    fix.children[id].style.display = '';
    order.children[id].style.display = '';
    if (hasClass(deleteHeaderCell, STICKY_CLASS)) {
      fixedItem(id).onChecked(target);
    }
  }
};

const syncMenuWithTable = () => {
  const headerItems = table.querySelectorAll('.table-header__item');
  const { display, fix, order, filter } = tableMenus;
  headerItems.forEach((item, index) => {
    var label = item.innerText;
    if(!label)
      label = $(item).find('.table-header__label').text();
    item.setAttribute('data-header', index);
    display.appendChild(createMenuItem(label, index).checkbox('table-display', true));

    fix.appendChild(createMenuItem(label, index).checkbox('table-fix', false));
    order.appendChild(createMenuItem(label, index).drag('table-order'));
    if (filter) {
      filter.appendChild(createMenuItem(label, index).checkbox('filter', false));
    }
  });
};

const getNextElementX = (cursorPosition, currentElem) => {
  const elementCoord = currentElem.getBoundingClientRect();
  const elemCenter = elementCoord.x + elementCoord.width / 2;
  const nextElement =
    cursorPosition < elemCenter ? currentElem : currentElem.nextElementSibling;
  return nextElement;
};

export const columnDrag = () => {
  //const dragAreas = document.querySelectorAll('.table-header__row');
  const dragAreas = document.querySelectorAll('tr');
  for (const dragArea of dragAreas) {
    const dragItems = dragArea.children;

    for (const item of dragItems) {
      const table = document.querySelector('.table__inner');
      const column = getTableColumn(table, item.cellIndex);
      item.addEventListener('dragstart', () => {
        column.forEach((cell) => addClass(cell, DRAGGABLE_CLASS));
        item.style.userSelect = 'none';
      });

      item.addEventListener('dragend', () => {
        column.forEach((cell) => removeClass(cell, DRAGGABLE_CLASS));
        // dragFunction();
        updateAllMenus(table, tableMenus.display, 'table-display');
        updateAllMenus(table, tableMenus.fix, 'table-fix');
        updateAllMenus(table, tableMenus.order, 'table-order');
        fixedItem(item.cellIndex).update();
        item.style.userSelect = '';
        activateSaveBtn();
      });
    }

    dragArea.addEventListener('dragover', (evt) => {
      evt.preventDefault();
      const currentElement = evt.target;

      // if (!hasClass(currentElement, 'table-header__item')) {
      //   return;
      // }
      const activeColumn = table.querySelectorAll(`.${DRAGGABLE_CLASS}`);
      const nextElement = getNextElementX(evt.clientX, currentElement);
      if (!nextElement) {
        return;
      }

      const nextColumn = getTableColumn(table, nextElement.cellIndex);

      for (let i = 0; i < activeColumn.length; i++) {
        const activeElement = activeColumn[i];
        if (activeElement === currentElement || activeElement === nextElement) {
          return;
        }
        const nextItem = nextColumn[i];
        if (nextItem.cellIndex === dragItems.length - 1) {
          nextItem.parentNode.appendChild(activeElement);
        } else {
          if (nextElement && activeElement === nextElement.previousElementSibling) {
            return;
          }
          nextItem.parentNode.insertBefore(activeElement, nextItem);
        }
      }
    });
  }
};

const sortRowsFunction = (a, b, column, sortAsc) => {
  const firstRow = a.querySelectorAll('td')[column];
  const secondRow = b.querySelectorAll('td')[column];
  const firstRowText = firstRow.textContent.toLowerCase().trim();
  const secondRowText = secondRow.textContent.toLowerCase().trim();
  if (sortAsc) {
    return firstRowText < secondRowText ? 1 : -1;
  } else {
    return firstRowText < secondRowText ? -1 : 1;
  }
};

const appendSortTable = (column, sortAsc, rows) => {
  const sortedArray = [...rows].sort((a, b) => sortRowsFunction(a, b, column, sortAsc));
  sortedArray.forEach((row) => row.parentNode.appendChild(row));
};

export const initTableSorting = () => {
  const tableRows = table.querySelectorAll(`.${TABLE_ROW_CLASS}`);
  const tableHeaderCells = table.querySelectorAll('.table-header__item');
  const tableCells = table.querySelectorAll('td');
  const sortClassName = 'asc';

  if($('[name="sort_index"]').val() != -1) {
    settings_sorting = [$('[name="sort_index"]').val(), $('[name="sort_asc"]').val()];
    const tableRows = table.querySelectorAll(`.${TABLE_ROW_CLASS}`);
    $('th:eq('+settings_sorting[0]+')').addClass(SORTED_CLASS);
    if(settings_sorting[1] == 'false') {
      $('th:eq('+settings_sorting[0]+')').addClass('asc');
      appendSortTable(settings_sorting[0], false, tableRows);
    } else {
      appendSortTable(settings_sorting[0], true, tableRows);
    }
    
  }

  for (const [index, headerCell] of Object.entries(tableHeaderCells)) {
    let sortAsc = true;
    headerCell.addEventListener('dblclick', (e) => {
      if (!hasClass(headerCell, 'no-sort') && !$(e.target).hasClass('form-checkbox__switcher')) {
        tableHeaderCells.forEach((cell) => removeClass(cell, SORTED_CLASS));
        addClass(headerCell, SORTED_CLASS);
        tableCells.forEach((cell) => removeClass(cell, SORTED_CLASS));
        tableRows.forEach((row) => {
          const cell = row.querySelectorAll('.table-body__item')[index];
          addClass(cell, SORTED_CLASS);
        });
        if(hasClass(headerCell, sortClassName)) {
          removeClass(headerCell, sortClassName);
        } else {
          headerCell.classList.toggle(sortClassName, sortAsc);
        }
        
        sortAsc = !hasClass(headerCell, sortClassName);
        settings_sorting = [index, sortAsc];
        appendSortTable(index, sortAsc, tableRows);
        activateSaveBtn();
      }
    });
  }
};

export const orderMenuFunc = (activeIndex, prevIndex) => {
  const activeColumn = getTableColumn(table, activeIndex);
  const prevColumn = getTableColumn(table, prevIndex);

  for (let i = 0; i < activeColumn.length; i++) {
    const activeCell = activeColumn[i];
    const prevCell = prevColumn[i];
    if (activeIndex > prevIndex) {
      prevCell.parentNode.insertBefore(activeCell, prevCell);
    } else {
      prevCell.parentNode.insertBefore(activeCell, prevCell.nextElementSibling);
    }
  }
  if (hasClass(activeColumn[0], STICKY_CLASS) && hasClass(prevColumn[0], STICKY_CLASS)) {
    fixedItem(activeIndex).update();
    fixedItem(prevIndex).update();
  }
  // fixedItem(activeIndex).update();
  updateAllMenus(table, tableMenus.display, 'table-display');
  updateAllMenus(table, tableMenus.fix, 'table-fix');
  // columnDrag();
  // columnResize();
  // sortTableFunction();
  activateSaveBtn();
};
export const initTableFunction = () => {
  

  
  columnDrag();
  columnResize();
  syncMenuWithTable();
  checkMenuHiddenItems(tableMenus.display);
  initTableSorting();
  updateAllMenus(table, tableMenus.display, 'table-display');
  updateAllMenus(table, tableMenus.fix, 'table-fix');
  updateAllMenus(table, tableMenus.order, 'table-order');
  document.addEventListener('change', ({ target }) => {
    const { id } = target;
    if (id.includes('table-display')) {
      const parseId = parseInt(id.match(/\d+/));
      deleteItemFunction(target, parseId);
      activateSaveBtn();
    }
    if (id.includes('table-fix')) {
      const parseId = parseInt(id.match(/\d+/));
      fixedItem(parseId).onChecked(target);
      activateSaveBtn();
    }
  });
  const tableSaveBtn = document.querySelector('.table__change-btn');
  if (tableSaveBtn) {
    tableSaveBtn.addEventListener('click', () => {
      const headerItems = $('.table-header__item');
      var settings = {},
          settings_reorder = [],
          settings_sizes = [],
          settings_fix = [],
          settings_visible = [];
      headerItems.each(function() {
        settings_reorder.push($(this).data('name'));
        settings_sizes.push($(this).width());
        if($(this).hasClass('sticky'))
          settings_fix.push($(this).data('name'));
        if(!$(this).hasClass('hidden'))
          settings_visible.push($(this).data('name'));
      });
      console.log(settings_sizes)
      settings = { 
        'reorder': settings_reorder, 
        'sizes': settings_sizes, 
        'fix': settings_fix,
        'visible': settings_visible,
        'sort': settings_sorting
        // 'sort_field': $('.table-header__item.sorted').data('name'),
        // 'sort_direction': $('.table-header__item.sorted').data('name'),
        // table-header__item sorted
      };
      $.ajax({
        "url": "/settings/table_update",
        "data": {'settings': settings, 'table': $('.js-entity-table').data('model'),'_token': $('input[name=_token]').val(),'_method': 'PUT'},
        "dataType": "json",
        "type": "POST",
        "success": function (res) {
          console.log(res)
        },
        "error": function(err) {
          console.log(err)
        }
      });
      tableSaveBtn.style.display = '';
    });
  }
};

