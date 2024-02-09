/* eslint-disable max-len */
import { initDragFunction } from './drag.js';
import { closeDropdown } from './dropdown.js';
import { ACTIVE_CLASS, EDIT_CLASS, SHOW_CLASS } from './constants.js';
import { addClass, hasClass, parseString, removeClass, sortArray } from './utils.js';

export function filterInit() {
  const headerSearch = document.querySelector('.header__search');
  if (!headerSearch) {
    return;
  }
  const searchInput = headerSearch.querySelector('.form-control_search');
  const filterMenu = document.querySelector('.filter');
  const FILTER_STORAGE_NAME = 'filters';
  let isEditState = false;

  const classNames = {
    filtering: 'filtering',
    controlInput: 'form-control_filter',
    savedInput: 'filter-saved__input',
    savedItem: 'filter-saved__item',
    previewBlock: 'filter-block',
  };

  const filter = {
    preview: headerSearch.querySelector('.filter__blocks'),
    form: filterMenu.querySelector('.filter__list'),
    createBtn: filterMenu.querySelector('.filter__create'),
    actions: filterMenu.querySelector('.filter__actions'),
    savedFiltersList: filterMenu.querySelector('.filter-saved__list'),
    selectionMenu: filterMenu.querySelector('#filterMenu'),
  };

  const placeholders = {
    default: 'Фильтр + поиск',
    searching: 'поиск',
  };

  const filterAttrs = {
    edit: 'data-filter="edit"',
    delete: 'data-filter="delete"',
    next: 'data-filter="next"',
    prev: 'data-filter="prev"',
  };

  const filterShow = () => {
    addClass(headerSearch, ACTIVE_CLASS);
    addClass(filterMenu, SHOW_CLASS);
    searchInput.placeholder = '';
  };

  const createPreviewBlock = (label, value) => {
    const item = document.createElement('DIV');
    addClass(item, classNames.previewBlock);
    const pattern = `
    <div class="filter-block__text">
      <span class="filter-block__label">${label} </span>
      <span class="filter-block__value">${value}</span>
    </div>
    <button class="filter-block__close btn-clear" type="button">
      <img src="img/icons/close.svg" alt="Close icon">
    </button>
    `;
    item.innerHTML = pattern;
    return item;
  };

  const createFilterField = (index, label, text) => {
    const item = document.createElement('DIV');
    const value = text || '';
    const id = `filter-${index}`;
    const pattern = `
    <span class="filter__drag btn-clear drag-btn" >
      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="12" fill="none">
        <path fill="#A6B7D4" fill-rule="evenodd" d="M0 1a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm0 5a1 1 0 0 1 1-1h9a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h9a1 1 0 1 0 0-2H1Z" clip-rule="evenodd"/>
      </svg>
    </span>
    <label class="form-label" for="${id}">${label}</label>
    <input class="form-control ${classNames.controlInput}" type="text" placeholder="не заполнено" id="${id}" value="${value}">
    `;
    addClass(item, 'filter__item', 'drag-item');
    item.innerHTML = pattern;
    return item;
  };

  const createSaveField = (index, text) => {
    const item = document.createElement('LI');
    const value = text || '';
    const id = `savedFilter-${index}`;
    addClass(item, classNames.savedItem);
    const dropdownMenu = `
    <div class="dropdown" data-dropdown>
      <button class="filter__options btn-clear" data-dropdown="btn">
        <svg width="3" height="13" fill="none">
          <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 
          5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" 
          clip-rule="evenodd"/>
        </svg>
      </button>
      <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
        <ul class="dropdown__list">
          <li class="dropdown__item">
            <button class="dropdown__link" type="button" ${filterAttrs.next}>Вверх</button>
          </li>
          <li class="dropdown__item">
            <button class="dropdown__link" type="button" ${filterAttrs.prev}>Вниз</button>
          </li>
          <li class="dropdown__item">
            <button class="dropdown__link" type="button" ${filterAttrs.edit}>Редактировать</button>
          </li>
          <li class="dropdown__item">
            <button class="dropdown__link" type="button" ${filterAttrs.delete}>Удалить</button>
          </li>
        </ul>
      </div>
    </div>`;
    const pattern = `
    <input class="${classNames.savedInput}" type="text" value="${value}" placeholder="Название" id="${id}" title="${value}">
      <div class="filter-saved__menu">
        ${dropdownMenu}
      </div>
    `;
    item.innerHTML = pattern;
    item.tabIndex = 0;
    return item;
  };

  const getFilterData = () => {
    const data = [];
    const filterInputs = headerSearch.querySelectorAll(`.${classNames.controlInput}`);
    for (const input of filterInputs) {
      const label = input.previousElementSibling.innerText;
      const dataObj = {};
      dataObj.label = label;
      dataObj.value = input.value;
      data.push(dataObj);
      input.value = '';
    }
    return data;
  };

  const insertPreviewBlock = (dataArray) => {
    dataArray.forEach((data) => {
      const { label, value } = data;
      if (value.trim() !== '') {
        const filterBlock = createPreviewBlock(`${label}:`, value);
        const filterPreivew = headerSearch.querySelector('.filter__blocks');
        addClass(filterPreivew, ACTIVE_CLASS);
        filterPreivew.appendChild(filterBlock);
        filter.preview.scrollLeft = filterPreivew.scrollWidth;
      }
    });
  };

  const activateSearchField = () => {
    const filtersLen = filter.preview.children.length;
    const addBtn = headerSearch.querySelector('#filterAdd');
    if (filtersLen > 0) {
      addClass(addBtn, ACTIVE_CLASS);
      addClass(searchInput, ACTIVE_CLASS);
      searchInput.placeholder = placeholders.searching;
    } else {
      removeClass(addBtn, ACTIVE_CLASS);
      removeClass(searchInput, ACTIVE_CLASS);
      removeClass(headerSearch, ACTIVE_CLASS);
      removeClass(filter.preview, ACTIVE_CLASS);
      searchInput.placeholder = placeholders.default;
    }
  };

  const filterSubmit = () => {
    const data = getFilterData();
    removeClass(filterMenu, SHOW_CLASS);
    removeClass(headerSearch, ACTIVE_CLASS);
    searchInput.placeholder = placeholders.default;
    insertPreviewBlock(data);
    activateSearchField();
    document.removeEventListener('keydown', enterHandler);
  };

  const setDefaultFields = (selectItem, index) => {
    const text = selectItem.querySelector('.form-checkbox__text').textContent;
    const input = selectItem.querySelector('.form-checkbox__input');
    const minPreviewFields = 4;
    if (index <= minPreviewFields - 1) {
      input.checked = true;
    }
    if (input.checked) {
      filter.form.appendChild(createFilterField(index, text));
    }
    const getFieldData = () => ({ input, text });
    return { getFieldData };
  };

  const resetInputsValue = () => {
    const inputs = filter.form.querySelectorAll(`.${classNames.controlInput}`);
    inputs.forEach((input) => {
      input.value = '';
    });
  };

  const filterReset = () => {
    const { savedItem, filtering } = classNames;
    const { form, savedFiltersList, preview, selectionMenu } = filter;
    const savedFilters = savedFiltersList.querySelectorAll(`.${savedItem}.${filtering}`);
    const selectionItems = selectionMenu.children;

    savedFilters.forEach((filter) => removeClass(filter, filtering));
    preview.innerHTML = '';
    form.innerHTML = '';
    searchInput.value = '';
    resetInputsValue();

    for (const [index, item] of Object.entries(selectionItems)) {
      setDefaultFields(item, index);
    }
    initDragFunction();
  };

  const saveFilterToStorage = (element) => {
    const methods = {
      getStorageData() {
        let storageData = localStorage.getItem(FILTER_STORAGE_NAME);
        if (!storageData) {
          storageData = [];
        } else {
          storageData = JSON.parse(localStorage.getItem(FILTER_STORAGE_NAME));
        }
        return storageData;
      },
      getFieldData() {
        const fieldData = new Map();
        const filterFields = filterMenu.querySelectorAll(`.${classNames.controlInput}`);
        filterFields.forEach((field) => {
          if (field.value.trim() !== '') {
            fieldData.set(field.id, field.value);
          }
        });
        return JSON.stringify([...fieldData.entries()]);
      },
      setFilter() {
        const filter = {
          name: element.value,
          fieldData: this.getFieldData(),
          id: element.id,
        };
        return filter;
      },
      editStorageData(storageData) {
        storageData.forEach((filter) => {
          if (element.id === filter.id) {
            filter.name = element.value;
          }
        });
      },
    };

    const storageData = methods.getStorageData();
    if (!isEditState) {
      const filter = methods.setFilter();
      storageData.push(filter);
    } else {
      methods.editStorageData(storageData);
      isEditState = false;
    }
    localStorage.setItem(FILTER_STORAGE_NAME, JSON.stringify(storageData));
  };

  const deleteSavedFilter = (element) => {
    const storageData = JSON.parse(localStorage.getItem(FILTER_STORAGE_NAME));
    const input = element.querySelector(`.${classNames.savedInput}`);
    const id = parseString(input.id);
    element.remove();
    storageData.splice(id, 1);
    localStorage.setItem(FILTER_STORAGE_NAME, JSON.stringify(storageData));
  };

  const editSavedFilters = (element) => {
    const input = element.querySelector(`.${classNames.savedInput}`);
    const endInputValue = input.value.length;
    isEditState = true;
    addClass(element, EDIT_CLASS);
    addClass(filter.actions, EDIT_CLASS);
    input.disabled = false;
    input.setSelectionRange(endInputValue, endInputValue);
    input.focus();
  };

  const removeSwapButtons = () => {
    const filters = filter.savedFiltersList.querySelectorAll(
      `.${classNames.savedItem}:not(.first)`,
    );
    filters.forEach((filter, index) => {
      const nextField = filter.querySelector(`[${filterAttrs.next}]`).parentNode;
      const prevField = filter.querySelector(`[${filterAttrs.prev}]`).parentNode;
      nextField.style.display = '';
      prevField.style.display = '';
      if (index === 0) {
        nextField.style.display = 'none';
      }
      if (index === filters.length - 1) {
        prevField.style.display = 'none';
      }
    });
  };

  const swapFilters = (target) => {
    const { savedFiltersList } = filter;
    const curentItem = target.closest(`.${classNames.savedItem}`);
    const list = [...savedFiltersList.children];
    const currentId = list.indexOf(curentItem);
    return {
      prev() {
        if (!curentItem.nextElementSibling) {
          return;
        }
        const prevItemId = currentId + 1;
        const sortedList = sortArray(list, currentId, prevItemId);
        closeDropdown(target);
        sortedList.forEach((item, index) => {
          list[index].outerHTML = item.outerHTML;
        });
        removeSwapButtons();
      },
      next() {
        if (hasClass(curentItem.previousElementSibling, 'first')) {
          return;
        }
        const nextItemId = currentId - 1;
        const sortedList = sortArray(list, currentId, nextItemId);
        closeDropdown(target);
        sortedList.forEach((item, index) => {
          list[index].outerHTML = item.outerHTML;
        });
        removeSwapButtons();
      },
    };
  };

  const filterLoader = (insertFilter = true) => {
    const { form, preview, selectionMenu, savedFiltersList } = filter;
    const methods = {
      getStorageData() {
        return JSON.parse(localStorage.getItem(FILTER_STORAGE_NAME));
      },
      createSavedItem(index, value) {
        const item = createSaveField(index, value);
        const input = item.querySelector('input');
        input.removeAttribute('placeholder');
        input.disabled = true;
        return item;
      },
      setInputValue(fieldData) {
        fieldData.forEach((value, key) => {
          const parseId = parseString(key);
          let curentInput;
          curentInput = form.querySelector(`#${key}`);
          if (!curentInput) {
            curentInput = selectionMenu.querySelector(`#filter-column-${parseId}`);
            const label = curentInput.labels[0].innerText;
            curentInput.checked = true;
            form.appendChild(createFilterField(parseId, label, value));
          }
          curentInput.value = value;
        });
      },
      createPreviewItem(value) {
        const filterBlock = createPreviewBlock('', value);
        addClass(preview, ACTIVE_CLASS);
        preview.innerHTML = '';
        preview.appendChild(filterBlock);
      },
      activateField(item, value) {
        this.createPreviewItem(value);
        const submitBtn = filterMenu.querySelector('#filterSubmit');
        submitBtn.click();
        addClass(item, classNames.filtering);
        initDragFunction();
      },
    };

    const storageData = methods.getStorageData();
    if (!storageData) {
      return;
    }
    storageData.forEach((data, index) => {
      const { name, fieldData, id } = data;
      const parseFieldData = new Map([...JSON.parse(fieldData)]);
      if (parseFieldData.size === 0) {
        return;
      }
      const item = methods.createSavedItem(index, name);
      savedFiltersList.addEventListener('click', ({ target }) => {
        if (target.id !== id) {
          return;
        }
        methods.setInputValue(parseFieldData);
        methods.activateField(item, name);
      });
      if (insertFilter) {
        savedFiltersList.appendChild(item);
      }
    });

    return methods;
  };

  filterLoader();

  const createFilter = () => {
    const { createBtn, savedFiltersList, actions } = filter;
    const id = savedFiltersList.children.length;
    createBtn.disabled = true;
    addClass(actions, EDIT_CLASS);
    savedFiltersList.appendChild(createSaveField(id - 1));
    addClass(savedFiltersList.children[id], EDIT_CLASS);
    const [input] = savedFiltersList.children[id].children;
    input.focus();
    removeSwapButtons();
  };

  const filterInputHandler = ({ target }) => {
    if (hasClass(target, classNames.controlInput)) {
      const condition = target.value.trim() !== '';
      filter.createBtn.classList.toggle(ACTIVE_CLASS, condition);
    }
  };

  const updatePreviewValue = (input) => {
    const previewItems = filter.preview.querySelectorAll(`.${classNames.previewBlock}`);
    const { title, value } = input;
    previewItems.forEach((item) => {
      const previewLabel = item.querySelector('.filter-block__value');
      if (title === previewLabel.innerText) {
        previewLabel.innerText = value;
      }
    });
  };

  const saveFilter = (input) => {
    if($(input).attr('data-id')) {
        $.ajax({
            type: 'post',
            url: '/filters/'+$(this).attr('data-id'),
            data: $('.filter-form-fields').serialize() + '&_method=PUT&_token=' + $('[name=_token]').val() + '&filter_name=' + $('.filter-saved__item.edit .filter-saved__input').val() + '&filter_type=' + $('.js-save-filter').data('type'),
            success: function(data) {
                
            }
        });
    } else {
        $.ajax({
            type: 'get',
            url: '/filters/',
            data: $('.filter-form-fields').serialize() + '&_token=' + $('[name=_token]').val() + '&filter_name=' + $('.filter-item.edit input').val() + '&filter_type=' + $('.js-save-filter').data('type'),
            success: function(data) {

            }
        });
    }
    input.removeAttribute('placeholder');
    input.disabled = true;
    input.setAttribute('value', input.value);
    filter.createBtn.disabled = false;
    saveFilterToStorage(input);
    removeClass(input.parentNode, EDIT_CLASS);
    removeClass(filter.actions, EDIT_CLASS);
    filterLoader(false);
    updatePreviewValue(input);
  };

  function enterHandler(event) {
    if (event.key !== 'Enter') {
      return;
    }
    if (!hasClass(filter.actions, EDIT_CLASS)) {
      filterSubmit();
    } else {
      const input = filterMenu.querySelector(`.${classNames.savedInput}:not([disabled])`);
      if (input.value.trim() !== '') {
        saveFilter(input);
      } else {
        input.style.borderColor = '#ae0a0a';
      }
    }
  }

  document.addEventListener('mousedown', ({ target }) => {
    if (!target.closest('.filter') && !target.closest('.header__search')) {
      removeClass(filterMenu, SHOW_CLASS);
      removeClass(headerSearch, ACTIVE_CLASS);
      activateSearchField();
      if (!hasClass(filter.preview, ACTIVE_CLASS)) {
        searchInput.placeholder = placeholders.default;
      }
      document.removeEventListener('input', filterInputHandler);
      document.removeEventListener('keydown', enterHandler);
    }
  });

  document.addEventListener('click', ({ target }) => {
    const { id } = target;

    if (id === 'filterSubmit') {
      filterSubmit();
    }
    if (id === 'filterReset') {
      filterReset();
      activateSearchField();
    }
    if (id === 'searchField') {
      filterShow();
      removeSwapButtons();
      document.addEventListener('input', filterInputHandler);
      document.addEventListener('keydown', enterHandler);
    }
    if (target.closest('.filter-block__close')) {
      const { savedItem, filtering, previewBlock } = classNames;
      const filterBlock = target.closest(`.${previewBlock}`);
      const activeFilters = filter.savedFiltersList.querySelectorAll(
        `.${savedItem}.${filtering}`,
      );
      const value = filterBlock.querySelector('.filter-block__value').innerText;
      activeFilters.forEach((filter) => {
        const label = filter.children[0].value;
        if (value === label) {
          removeClass(filter, filtering);
        }
      });
      filterBlock.remove();
      activateSearchField();
    }
    if (target === filter.createBtn) {
      createFilter();
    }
    if (id === 'filterSave') {
      const input = filterMenu.querySelector(`.${classNames.savedInput}:not([disabled])`);
      if (input.value.trim() !== '') {
        saveFilter(input);
      } else {
        input.style.borderColor = '#ae0a0a';
      }
    }
    if (id === 'filterCancel') {
      const input = filterMenu.querySelector(`.${classNames.savedInput}:not([disabled])`);
      if (input) {
        input.remove();
        removeClass(filter.actions, EDIT_CLASS);
      }
    }

    if (target.closest(`[${filterAttrs.delete}]`)) {
      const item = target.closest(`.${classNames.savedItem}`);
      deleteSavedFilter(item);
      closeDropdown(target);
    }
    if (target.closest(`[${filterAttrs.edit}]`)) {
      const item = target.closest(`.${classNames.savedItem}`);
      editSavedFilters(item);
      closeDropdown(target);
    }
    if (target.closest(`[${filterAttrs.prev}]`)) {
      swapFilters(target).prev();
    }
    if (target.closest(`[${filterAttrs.next}]`)) {
      swapFilters(target).next();
    }
  });

  const setControlFields = () => {
    const { form, selectionMenu } = filter;

    for (const [index, item] of Object.entries(selectionMenu.children)) {
      const { getFieldData } = setDefaultFields(item, index);
      const { input, text } = getFieldData();
      const selectCheckboxHandler = () => {
        if (input.checked) {
          const field = createFilterField(index, text);
          form.appendChild(field);
          initDragFunction();
        } else {
          const activeItem = form.querySelector(`#filter-${index}`).parentNode;
          activeItem.remove();
        }
      };

      input.addEventListener('change', selectCheckboxHandler);
    }
  };
  document.addEventListener(
    'focus',
    ({ target }) => {
      if (hasClass(target, classNames.controlInput)) {
        removeClass(headerSearch, ACTIVE_CLASS);
      }
    },
    true,
  );

  setControlFields();
}
