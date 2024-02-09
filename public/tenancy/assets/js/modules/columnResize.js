import { addClass, getTableColumn, hasClass, removeClass } from './utils.js';
import { activateSaveBtn, fixedItem } from './table.js';
import { STICKY_CLASS, TABLE_CLASS } from './constants.js';
import { fixColumn } from './tableFixColumn.js';

const resizeArea = (area) => {
  area.style.height = 'auto';
  area.style.height = `${area.scrollHeight}px`;
};

const MAX_WIDTH = 25;
const BORDER_CLASS = 'has-border';
const RESIZER_CLASS = 'resizer';

export function columnResize() {
  // if (!table) {
  //   throw new Error('Table is undefined');
  // }
  // tables.forEach((table) => {

  // });
  let resizeState = false;
  const cols = document.querySelectorAll('th');

  const borderHandler = (callback, { target, type }) => {
    if (!hasClass(target, RESIZER_CLASS)) {
      return;
    }
    //console.log(type)
    //console.log(target)
    if (type === 'mouseover' && resizeState) {
      return;
    }
    // if (resizeState) {
    //   return;
    // }
    const col = target.parentNode;
    const table = col.closest('.table__inner');
    const currentColumn = getTableColumn(table, col.cellIndex);
    currentColumn.forEach((cell) => callback(cell));
  };

  const setBorder = (cell) => {
    console.log('setBorder why')
    addClass(cell, BORDER_CLASS);
    resizeState = true;
  };

  const removeBorder = (cell) => {
    removeClass(cell, BORDER_CLASS);
    resizeState = false;
  };

  const setBorderHandler = borderHandler.bind(null, setBorder);
  const removeBorderHandler = borderHandler.bind(null, removeBorder);

  function initResize(event) {
    event.stopPropagation();
    event.cancelBubble = true;
    const table = event.target.closest('.table__wrapper');
    const col = event.target.parentNode;
    const colWidth = parseFloat(col.style.width);
    const currentIndex = col.cellIndex;
    const currentFullCol = getTableColumn(table, currentIndex);
    const mouseX = event.clientX;
    const stickyItem = event.target.closest(`.table-header__item.${STICKY_CLASS}`);
    cols.forEach((col) => {
      col.draggable = false;
    });
    document.body.style.cursor = 'col-resize';
    addClass(document.documentElement, 'with-col-resize');
    document.removeEventListener('mouseout', removeBorderHandler);

    const doResize = ({ clientX }) => {
      requestAnimationFrame(() => {
        const diffX = clientX - mouseX;
        if (diffX === 0) {
          return;
        }

        /*
        if($(col).find('input')) {
          */
        const totalWidth = (colWidth + diffX).toFixed(2);
        //console.log(totalWidth)
        if($(col).find('table-header__filter-btn').length) {
          if (totalWidth >= 0) {
            console.log(totalWidth)
            col.style.width = `${totalWidth}px`;
          }
        } else {
          if (totalWidth >= MAX_WIDTH) {
            col.style.width = `${totalWidth}px`;
          }
        }
        
        col.style.overflow = totalWidth <= 50 ? 'hidden' : '';
        col.style.color = totalWidth <= MAX_WIDTH ? 'transparent' : '';
        if (totalWidth <= 20) {
          $(col).find('.table-header__inner').css({'visibility':'hidden'});
        } else {
          $(col).find('.table-header__inner').css({'visibility':'visible'});
        }
        if (stickyItem) {
          //console.log('update resize')
          fixColumn(stickyItem.cellIndex, table).update();
        }

        currentFullCol.forEach((cell) => {
          const textarea = cell.querySelector('.form-control_table');
          if (textarea) {
            resizeArea(textarea);
          }
        });
      });
    };

    const stopResize = () => {
      console.log('stopResize')
      event.stopPropagation();
      cols.forEach((col) => {
        col.draggable = true;
      });
      currentFullCol.forEach((cell) => removeBorder(cell));
      removeClass(document.documentElement, 'with-col-resize');
      document.body.style.cursor = '';
      activateSaveBtn(table);
      document.removeEventListener('mousemove', doResize);
      document.removeEventListener('mouseup', stopResize);
      document.addEventListener('mouseout', removeBorderHandler);
      // document.removeEventListener('mousedown', setBorderHandler);
    };
    document.addEventListener('mousemove', doResize);
    document.addEventListener('mouseup', stopResize);
    document.addEventListener('mousedown', setBorderHandler);
  }
  //document.addEventListener('mouseover', setBorderHandler);
  //document.addEventListener('mouseout', removeBorderHandler);
  // const cols = document.querySelectorAll('th');
  cols.forEach((col) => {
    if(!$(col).find('.resizer').length) {
      const resizer = document.createElement('div');
      addClass(resizer, RESIZER_CLASS);
      col.appendChild(resizer);
      document.addEventListener('mouseover', setBorderHandler);
      document.addEventListener('mouseout', removeBorderHandler);
      resizer.addEventListener('mousedown', initResize);
    }
  });
}
