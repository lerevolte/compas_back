/* eslint-disable no-loop-func */
import { STICKY_CLASS } from './constants.js';
// import { initDrag } from '../logistics.js';
import { addClass, getTableColumn, hasClass, removeClass } from './utils.js';
// import { updateAllMenus } from './table.js';
export const fixColumn = (id, table) => {
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
        const column = getTableColumn(table, item.cellIndex);
        column.forEach((cell) => (cell.style.left = prevWidth + 'px'));
        currentColumn.forEach((cell) => {
          cell.style.left = prevFullWidth + 'px';
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
      // initDrag();
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
            const prevFullWidth = lastPrevItem.offsetWidth + prevWidth;
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
      // initDrag();
    },
  };
  // });
};
