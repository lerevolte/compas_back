import { DRAGGABLE_CLASS } from './constants.js';
import { headerOrderFunction } from './header.js';
import { orderMenuFunc } from './table.js';
import { addClass, hasClass, removeClass } from './utils.js';

const getNextElementY = (cursorPosition, currentElem) => {
  const { y, height } = currentElem.getBoundingClientRect();
  const elemCenter = y + height / 2;
  return cursorPosition < elemCenter ? currentElem : currentElem.nextElementSibling;
};

export function initDragFunction() {
  const dragAreas = document.querySelectorAll('[data-drag="area"]');
  for (const dragArea of dragAreas) {
    const storageName = dragArea.dataset.dragname;
    const dragItems = dragArea.children;
    for (const item of dragItems) {
      let startIndex;
      const dragBtn = item.querySelector('.drag-btn');
      if (!dragBtn) {
        return;
      }
      dragBtn.addEventListener('dragstart', (event) => {
        startIndex = [...dragItems].indexOf(item);
        addClass(item, DRAGGABLE_CLASS);
        event.dataTransfer.effectAllowed = 'move';
        document.body.style.cursor = 'grab';
      });

      dragBtn.addEventListener('dragend', () => {
        removeClass(item, DRAGGABLE_CLASS);
        const array = [...dragItems];

        if (storageName === 'orderMenu') {
          // const headerList = [...document.querySelectorAll('th')];
          // headerList.shift();
          const endIndex = array.indexOf(item);
          console.log(startIndex)
          console.log(endIndex)
          orderMenuFunc(startIndex, endIndex, item);
        }

        if (storageName === 'headerOrderMenu') {
          const endIndex = array.indexOf(item);
          headerOrderFunction(startIndex, endIndex);
        }
        document.body.style.cursor = '';
      });
    }

    dragArea.addEventListener('dragenter', (event) => {
      // if (!hasClass(event.target, 'drag-btn')) {
      //   return;
      // }
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
    });

    dragArea.addEventListener('dragover', (event) => {
      // if (!hasClass(event.target, 'drag-item')) {
      //   return;
      // }
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
      const activeElement = dragArea.querySelector(`.${DRAGGABLE_CLASS}`);
      // const currentElement = event.target;
      const currentElement = event.target.closest('.drag-item');
      const isMoveable =
        activeElement !== currentElement && hasClass(currentElement, 'drag-item');
      if (!isMoveable) {
        return;
      }
      const nextElement = getNextElementY(event.clientY, currentElement);
      if (
        (nextElement && activeElement === nextElement.previousElementSibling) ||
        activeElement === nextElement
      ) {
        return;
      }
      if (activeElement) {
        dragArea.insertBefore(activeElement, nextElement);
      }
    });
  }
  $(".js-sort-submenu").sortable({
    group: 'no-drop',
    handle: '.drag-btn',
    items: '.drag-item',
    update: function( event, ui ) {
      var items = [];
      var $item = ui.item;
      $('.js-sort-submenu>li').each(function(){
          items.push($(this).data('id'))
      });
      $.ajax({
          type: 'post',
          url: '/menu/change-sort',
          data: {items: items, '_token': $('[name=_token]').val()},
          success: function(data) {
            console.log(data)
              //$('.js-save-panel').hide();
          },
          error: function(error) {
            console.log(error)
          }
       });
    },

  });
}
$(document).ready(function(){
  console.log('DRAG INIT')
  $(".js-sort-side").sortable({
    group: 'no-drop',
    handle: '.js-edit-position-menu',
    start: function( event, ui ) {
      $('.sidebar-nav__submenu.active').hide();
      $('.js-sort-side').sortable('refreshPositions');
    },
    stop: function( event, ui ) {
      $('.sidebar-nav__submenu.active').show();
      $('.js-sort-side').sortable('refreshPositions');
    },
    update: function( event, ui ) {
      var items = [];
      var $item = ui.item;
      $('.js-sort-side>.sidebar-nav__item').each(function(){
          items.push($(this).data('id'))
      });
      console.log('ITEMS');
      console.log(items)
      $.ajax({
          type: 'post',
          url: '/menu/change-sort',
          data: {items: items, '_token': $('[name=_token]').val()},
          success: function(data) {
            console.log(data)
              //$('.js-save-panel').hide();
          },
          error: function(error) {
            console.log(error)
          }
       });
    },

  });
  
  $(".js-sort-form-filter").sortable({
      group: 'no-drop-form',
      handle: '.drag-btn',
      items: '.drag-item',
      start: function( event, ui ) {
        $('.js-sort-form-filter .field-content').hide();
        $('.js-sort-form-filter').sortable('refreshPositions');
      },
      stop: function( event, ui ) {
        $('.js-sort-form-filter .field-content').show();
        $('.js-sort-form-filter').sortable('refreshPositions');
      },
      update: function( event, ui ) {
        
        var items = [];
        var $item = ui.item;
        var filter = $item.closest('ul').data('filter');


        $item.closest('ul').find('li').each(function(){
            if($(this).data('field'))
                items.push($(this).data('field'))
        });
        $.ajax({
            type: 'post',
            url: '/filters/change-sort-fields',
            data: {id: $item.data('id'), items: items, filter: filter, '_token': $('[name=_token]').val()},
            success: function(data) {
            }
        });
      },

    });
  
});
