import { ACTIVE_CLASS, EDIT_CLASS, SELECT_CLASS, TABLE_ROW_CLASS } from './constants.js';
import { getUrlParam } from './pagination.js';
import { initTableSorting } from './table.js';
import { addClass, removeClass } from './utils.js';

const checkboxes = [...document.querySelectorAll('[data-checkbox]')];
const editControlPanel = document.querySelector('.table-actions');
const checkboxCounter = document.querySelector('#checkboxCounter');
const mainCheckbox = document.querySelector('#mainCheckbox');
const FIELD_CLASS = 'form-control_table';

const getNumberItemsOnPage = () => {
  const selectValue = document.querySelector('.select-current');
  const storageValue = parseInt(JSON.parse(localStorage.getItem('itemsPerPage')));
  if (storageValue) {
    selectValue.innerHTML = storageValue;
  }
  return storageValue || parseInt(selectValue.innerHTML, 10);
};

let activeCheckboxCounter = 0;
let selectedRows = [];
const oldFieldData = new Map();

const activateControlPanel = () => {
  if (activeCheckboxCounter > 0) {
    addClass(editControlPanel, ACTIVE_CLASS);
  } else {
    removeClass(editControlPanel, ACTIVE_CLASS);
  }
};

const checkboxHandler = ({ target }) => {
  if (target.hasAttribute('data-checkbox')) {
    const checkbox = target;
    const currentTableRow = checkbox.closest(`.${TABLE_ROW_CLASS}`);
    if (checkbox.checked) {
      selectedRows.push(currentTableRow);
      addClass(currentTableRow, SELECT_CLASS);
      activeCheckboxCounter++;
    } else {
      const index = selectedRows.indexOf(currentTableRow);
      selectedRows.splice(index, 1);
      removeClass(currentTableRow, SELECT_CLASS);
      activeCheckboxCounter--;
    }
    activateControlPanel();
    checkboxCounter.innerText = activeCheckboxCounter;
  }
};

// const clearHeightField = (field) => {
//   field.disabled = true;
//   field.style.overflowWrap = '';
//   const regex = /[\w\d]+(\r\n|\n|\r)?/gm;
//   // const regex = /(?<!\d)(\r?\n|\r)(?!\d)/gm;
//   // console.log(field.value.match(/(?<!\n)\n(?!\n)/gm));
//   field.value = field.value.replace(/(\r\n|\n|\r)/gm, '');
//   const height = field.dataset.fieldHeight;
//   field.style.height = `${height}px`;
//   field.style.height = `${field.scrollHeight}px`;
// };

function editField(row, data) {
  const fields = row.querySelectorAll('.table-edit__field');
  const hiddenClass = 'table-edit__hidden';
  const editClass = 'with-edit-field';
  const textareaAttribute = 'data-edit-field';
  const rowData = [];

  return {
    // createField(field) {
    //   var text;
    //   $.ajax({
    //     type: 'get',
    //     async: false,
    //     url: '/field/',
    //     data: { entity_id: field.data('id'), name: field.data('field'), value: (field.data('value') != undefined ? field.data('value') : field.text()), model: $('.js-entity-table').data('model') },
    //     success: function(data) {
    //         //text = document.createElement(data);
    //         // return data;
    //         field.html("").append(data);
    //         // if($('.js-select').length){
    //         //     $('.js-select').each(function(){
    //         //         var $this = $(this),
    //         //             $wrap = $this.closest('.position-relative');
                        
    //         //         if($this.find('option').length < 10) {
    //         //             $this.select2({
    //         //                 width: 'auto',
    //         //                 dropdownParent: $wrap,
    //         //                 minimumResultsForSearch: -1
    //         //             });
    //         //         } else {
    //         //             $this.select2({
    //         //                 width: 'auto',
    //         //                 dropdownParent: $wrap
    //         //             });
    //         //         }
                    
    //         //     })
    //         //     console.log('haha')
    //         //     field.find('.js-select').select2('open');
    //         // };
    //         // if(field.data('type') == 'date') {
    //         //     var dpicker = field.find('input').daterangepicker({
    //         //         "singleDatePicker": true,
    //         //         "showDropdowns": true,
    //         //         autoUpdateInput: false,
    //         //         cancelButtonClasses: 'd-none',
    //         //         minYear: 1900,
    //         //         maxYear: 2050,
    //         //         locale: {
    //         //           //format: 'DD.MM.YYYY',
    //         //           cancelLabel: 'Отмена',
    //         //           applyLabel: "Применить",
    //         //         },
    //         //         ranges: false,
    //         //         showCustomRangeLabel: false,
    //         //     }, function(start, end, label) {
                    
    //         //     });
    //         //     dpicker.on('apply.daterangepicker', function(ev, picker) {
    //         //         field.find('input').val(picker.startDate.format('DD.MM.YYYY'));
    //         //     });
    //         // }
    //     }
    //   });
    //   // const text = document.createElement('select');
    //   // text.value = value;
    //   // addClass(text, 'form-control', FIELD_CLASS);
    //   // addClass(text, 'datepicker', FIELD_CLASS);
    //   text = field;

    //   return text;
    // },
    createDate(value) {
      const text = document.createElement('input');
      text.value = value;
      addClass(text, 'form-control', FIELD_CLASS);
      addClass(text, 'datepicker', FIELD_CLASS);
      

      return $(text);
    },
    createTextarea(value) {
      const textarea = document.createElement('textarea');
      textarea.rows = '1';
      textarea.textContent = value;
      addClass(textarea, 'form-control', FIELD_CLASS);
      return $(textarea);
    },
    textareaResizeHandler({ target }) {
      if (!target.hasAttribute(textareaAttribute)) {
        return;
      }
      target.style.height = 'auto';
      target.style.height = `${target.scrollHeight}px`;
    },

    enable() {
      fields.forEach((field, index) => {
        const fieldParrent = field.parentNode;
        const cell = field.closest('td');
        const type = $(cell).find('[data-field]').data('type');
        addClass(cell, editClass);
        // const fieldText = field.innerText.replace(/\n\n(?!.)/s, '');
        const text = $(cell).find('[data-field]').data('value') != undefined ? $(cell).find('[data-field]').data('value') : $(cell).find('[data-field]').text();
        
        // if(type == 'date') 
        //   var textarea = this.createDate(text);
        // else
        //   var textarea = this.createTextarea(text);

        if(type == 'date')
          var textarea = this.createDate(text);
        else if(type == 'select_dropdown' || type == 'relation' || type == 'status') {
          var textarea = $(data[$(cell).find('[data-field]').data('field')]); //this.createField($(cell).find('[data-field]'));
        }
        else {
          console.log(type)
          var textarea = this.createTextarea(text);
        }
        // textarea.setAttribute(textareaAttribute, index);
        textarea.attr(textareaAttribute, index);
        addClass(field, hiddenClass);
        rowData.push(textarea.val());
        //$(fieldParrent).append($('<div class="position-relative"></div>'));
        //console.log(textarea)
          $(fieldParrent).append(textarea);
        
        if(type == 'text' || type == 'program') {
          setTimeout(() => {
            textarea.css({'height': textarea.prop('scrollHeight')+'px'});
          }, 0);
          document.addEventListener('input', this.textareaResizeHandler);
        }
        
      });
      oldFieldData.set(row.rowIndex, rowData);
    },
    disable() {
      fields.forEach((field) => {
        var clearStr = '';
        console.log('field')
        
        const textarea = field.nextElementSibling;
        if($(textarea).prop("tagName") == 'INPUT') {
          clearStr = $(textarea).val();
        } else if($(textarea).prop("tagName") == 'TEXTAREA') {
          clearStr = $(textarea).val();
        } else if($(textarea).find('select') && $(textarea).find('select').data('type') == 'select_dropdown') {
          if($(textarea).find('select option:selected').text() !== 'не выбрано')
            clearStr = $(textarea).find('select option:selected').text();
        } else if($(textarea).find('select') && $(textarea).find('select').hasClass('js-field-status')) {
          $(textarea).prev('.table-body__inner').find('.point_status_rect').css({'background': $(textarea).find('select').prev('.point_status_rect').css('background')});
          console.log('text '+$(textarea).find('.js-select-text').text())
          $(textarea).prev('.table-body__inner').find('.js-select-text').text($(textarea).find('.js-select-text').text());
          $(textarea).prev('.table-body__inner').find('.d-none').text($(textarea).find('.d-none').text());
          //$(textarea).find('.status-group').addClass('disabled');
        } else {
          clearStr = $(textarea).val();//textarea.value.replace(/\n\n(?!.)/s, '');
        }
        
        const cell = field.closest('td');
        if(!$(textarea).find('select') && !$(textarea).find('select').hasClass('js-field-status'))
          field.innerText = clearStr;
        removeClass(cell, editClass);
        removeClass(field, hiddenClass);
        //const clearStr = textarea.value.replace(/(?<!\S)\n+/gm, '');
        document.removeEventListener('input', this.textareaResizeHandler);
        $(textarea).remove();
      });
    },
  };
}

const editFunction = (row, data = '') => {
  checkboxes.forEach((checkbox) => {
    checkbox.disabled = true;
  });
  mainCheckbox.disabled = true;
  addClass(row, EDIT_CLASS);
  addClass(editControlPanel, EDIT_CLASS);

  editField(row, data).enable();
};

const resetCheckboxes = (checkboxes) => {
  checkboxes.forEach((checkbox) => {
    checkbox.checked = false;
    checkbox.disabled = false;
  });
};

export const resetEditControlPanel = () => {

  resetCheckboxes(checkboxes);
  mainCheckbox.checked = false;
  mainCheckbox.disabled = false;
  removeClass(editControlPanel, EDIT_CLASS, ACTIVE_CLASS);
  activeCheckboxCounter = 0;
  checkboxCounter.innerHTML = activeCheckboxCounter;
  selectedRows = [];

};

const saveFunction = (row) => {
  editField(row).disable();
  
  
  removeClass(row, EDIT_CLASS, SELECT_CLASS);
  resetEditControlPanel();
  initTableSorting();
};

const deleteThroughtMenu = (event) => {
  const { target } = event;
  if (!target.hasAttribute('data-delete')) {
    return;
  }
  event.preventDefault();
  console.log('delete')
  console.log($(target));
  $(target).closest('.dropdown__menu').prev('.table-options__btn').trigger('click');
  setTimeout(() => {
      var result = confirm('Уверены, что хотите удалить элемент?');
      if(result) {
          var model = $(target).data('model');
          var btn = $(target);
          $.ajax({
              type: 'post',
              url: '/objects/'+model+'/destroy/'+btn.data('id'),
              data: {
                  'id': btn.data('id'),
                  '_token': $('input[name=_token]').val(),
                  '_method': 'DELETE'
              },
              success: function(data) {
                  //table.row(btn.closest("tr")).remove().draw();
              }
          });
          const tableItem = target.closest(`.${TABLE_ROW_CLASS}`);
          tableItem.remove();
          if (activeCheckboxCounter > 0) {
            activeCheckboxCounter--;
            checkboxCounter.innerText = activeCheckboxCounter;
          }
      }
  }, 100);
  
  
};

const deleteFunction = (row) => {
  var tr, tr_id;
  var data = {}, ids = [];
  data['_token'] = $('[name=_token]').val();
  data['_method'] = 'DELETE';
  $('.table-body__row.selected').each(function(){
      tr_id = $(this).data('id');
      ids.push(tr_id);
  });
  data['ids'] = JSON.stringify(ids);

  row.remove();
  resetEditControlPanel();
  const pageRows = document.querySelectorAll(`.${TABLE_ROW_CLASS}`);
  const pageNumber = parseInt(getUrlParam('page'), 10) || 1;
  if (pageRows.length === 0 && pageNumber > 1) {
    window.location.search = `?page=${pageNumber - 1}`;
  }
  initTableSorting();
  $.ajax({
      type: 'post',
      async: false,
      url: '/objects/'+$('.js-entity-table').data('model')+'/delete',
      data: objectToQueryString(data),
      success: function(data) {
          console.log(data);
      },
      error: function(error) {
          console.log(error)
      }
  });
};

const undoFieldChange = (field, index) => {

  oldFieldData.forEach((values) => {
    // console.log(field)
    // console.log(index)
    // console.log(values[index])
    if(values[index] !== undefined)
      field.value = values[index];
  });
};

const cancelFunction = (row) => {
  const fields = row.querySelectorAll(`.${FIELD_CLASS}`);

  fields.forEach((field, index) => {
    //console.log(field)
    //console.log(index)
    undoFieldChange(field, index);
  });
  editField(row).disable();
  removeClass(row, EDIT_CLASS, SELECT_CLASS);
  resetEditControlPanel();
};

const selectAll = () => {
  const pageNumber = parseInt(getUrlParam('page'), 10) || 1;
  const rows = document.querySelectorAll(`.${TABLE_ROW_CLASS}`);
  const maxItems = getNumberItemsOnPage();
  selectedRows = [];
  for (const [index, checkbox] of Object.entries(checkboxes)) {
    if (index <= maxItems * pageNumber) {
      const currentTableRow = checkbox.closest(`.${TABLE_ROW_CLASS}`);
      if (mainCheckbox.checked) {
        selectedRows.push(currentTableRow);
        checkbox.checked = true;
        addClass(currentTableRow, SELECT_CLASS);
        activeCheckboxCounter = rows.length;
      } else {
        const index = selectedRows.indexOf(currentTableRow);
        selectedRows.splice(index, 1);
        removeClass(currentTableRow, SELECT_CLASS);
        checkbox.checked = false;
        activeCheckboxCounter = 0;
      }
      activateControlPanel();
    }
  }
  checkboxCounter.innerText = activeCheckboxCounter;
};

export const tableEditor = () => {
  
  document.addEventListener('click', deleteThroughtMenu);
  mainCheckbox.addEventListener('change', selectAll);
  document.addEventListener('change', checkboxHandler);
  editControlPanel.addEventListener('click', ({ target }) => {
    const { id } = target;
    //console.log(selectedRows);
    var ids = [];
    for (const row of selectedRows) {
      ids.push($(row).data('id'));
    };
    if(id === 'editBtn') {
      //console.log('/objects/'+$('.js-entity-table').data('model')+'/edit_list'+objectToQueryString(ids));
      $.ajax({
          type: 'get',
          async: false,
          url: '/objects/'+$('.js-entity-table').data('model')+'/edit_list',
          data: {ids: ids},
          success: function(data) {
            console.log(data)
              //console.log(data);
              for (const row of selectedRows) {
                editFunction(row, data[$(row).data('id')]);
                //console.log($(row).data('id'));
                //console.log(data[$(row).data('id')]);
              };
              if($('.js-select').length){
                  $('.js-select').each(function(){
                      var $this = $(this),
                          $wrap = $this.closest('.position-relative');
                          
                      if($this.find('option').length < 10) {
                          $this.select2({
                              width: 'auto',
                              dropdownParent: $wrap,
                              minimumResultsForSearch: -1
                          });
                      } else {
                          $this.select2({
                              width: 'auto',
                              dropdownParent: $wrap
                          });
                      }
                      
                  })
              };
              if($('.datepicker').length){
                $('.datepicker').each(function(){
                  var field = $(this);
                  var dpicker = $(this).daterangepicker({
                      parentEl: $(this).closest('.date-field'),//'.position-relative',
                      "singleDatePicker": true,
                      "showDropdowns": true,
                      autoUpdateInput: false,
                      cancelButtonClasses: 'd-none',
                      minYear: 1900,
                      maxYear: 2050,
                      locale: {
                        //format: 'DD.MM.YYYY',
                        cancelLabel: 'Отмена',
                        applyLabel: "Применить",
                      },
                      ranges: false,
                      showCustomRangeLabel: false,
                  }, function(start, end, label) {
                  });
                  dpicker.on('apply.daterangepicker', function(ev, picker) {
                      field.val(picker.startDate.format('DD.MM.YYYY'));
                  });
                });
              }
              if($('.js-field-status').length) {
                $('.selected .js-field-status').each(function(){
                  var $this = $(this),
                    $wrap = $this.closest('.position-relative');
                    console.log($wrap)
                    if($wrap.length)
                      $(".js-field-status").select2({
                          dropdownCssClass : "select2-delivery",
                          //dropdownParent: $wrap,
                          minimumResultsForSearch: -1,
                          width: 32,
                          templateResult: formatStatusSelect,
                          templateSelection: formatStatusSelect
                      }).on('select2:open', (e) => {
                          if(!$(e.target).closest('table').length) {
                              $('.dropcolor').remove();
                              if(!$('.dropcolor').length) {
                                  console.log('add')
                                  $('.select2-results__options').show();
                                  $(".select2-results:not(:has(.dropcolor))")
                                      .append('<div class="dropcolor" data-field="'+$(e.currentTarget).data('field')+'"></div>')
                                  $(".select2-results .dropcolor:not(:has(a))").append('<a class="dropdown-item dropdown-item-color" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="bg-status"></span><span>Палитра цветов</span> <i class="fa fa-chevron-right"></i></a>')
                                      .append('<div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions"><a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Палитра цветов</b></a><div class="d-flex"><div class="clr-field"><button aria-labelledby="clr-open-label"></button><input type="text" class="coloris"></div></div></div>');
                                  Coloris({
                                    el: '.coloris',
                                    parent: '.dropcolor',
                                    defaultColor: $(e.currentTarget).data('color'),
                                    swatches: [
                                    ]
                                  });
                                  $('#clr-color-value').val($(e.currentTarget).data('color'))
                                  $('.clr-field').css({'color': $(e.currentTarget).data('color')})
                              }
                          }
                      }).on('select2:close', () => {
                          $('.dropcolor').remove();
                      }).on('select2:select', function (e) {
                          var data = e.params.data;
                          $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
                      });
                    else
                      $(".js-field-status").select2({
                          dropdownCssClass : "select2-delivery",
                          minimumResultsForSearch: -1,
                          width: 32,
                          templateResult: formatStatusSelect,
                          templateSelection: formatStatusSelect
                      }).on('select2:open', (e) => {
                          if(!$(e.target).closest('table').length) {
                              $('.dropcolor').remove();
                              if(!$('.dropcolor').length) {
                                  console.log('add')
                                  $('.select2-results__options').show();
                                  $(".select2-results:not(:has(.dropcolor))")
                                      .append('<div class="dropcolor" data-field="'+$(e.currentTarget).data('field')+'"></div>')
                                  $(".select2-results .dropcolor:not(:has(a))").append('<a class="dropdown-item dropdown-item-color" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="bg-status"></span><span>Палитра цветов</span> <i class="fa fa-chevron-right"></i></a>')
                                      .append('<div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions"><a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Палитра цветов</b></a><div class="d-flex"><div class="clr-field"><button aria-labelledby="clr-open-label"></button><input type="text" class="coloris"></div></div></div>');
                                  Coloris({
                                    el: '.coloris',
                                    parent: '.dropcolor',
                                    defaultColor: $(e.currentTarget).data('color'),
                                    swatches: [
                                    ]
                                  });
                                  $('#clr-color-value').val($(e.currentTarget).data('color'))
                                  $('.clr-field').css({'color': $(e.currentTarget).data('color')})
                              }
                          }
                      }).on('select2:close', () => {
                          $('.dropcolor').remove();
                      }).on('select2:select', function (e) {
                          var data = e.params.data;
                          $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
                      });
                })
              }
          },
          error: function(error) {
              console.log(error)
          }
      });
    }
    if (id === 'saveBtn') {
      $('#saveBtn').addClass('disabled');
    }
    setTimeout(() => {
      for (const row of selectedRows) {
      if (id === 'editBtn') {
        //console.log('editBtn')
        //console.log(row)
        //editFunction(row);
      }
      if (id === 'saveBtn') {
        
        var tr, tr_id;
        var data = {}, r = {}, fields = {};
        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        data['rows'] = {};
        $('.table-body__row.edit').each(function(){
            tr = $(this);
            tr_id = $(this).data('id');
            fields = {};
            fields['id'] = tr_id;
            tr.find('.with-edit-field').each(function(){
                //console.log($(this).find('.table-edit__field').data('field')+': '+$(this).find('textarea').val())
                if($(this).find('textarea').length)
                  fields[$(this).data('field')] = $(this).find('textarea').val();
                else if($(this).find('input').length)
                  fields[$(this).data('field')] = $(this).find('input').val();
                else if($(this).find('select').length)
                  fields[$(this).data('field')] = $(this).find('select').val();
            });
            r[tr_id] = fields;
            data['rows'] = JSON.stringify(r);
        });
        $.ajax({
            type: 'get',
            async: false,
            url: '/objects/'+$('.js-entity-table').data('model')+'/batch',
            data: objectToQueryString(data),
            success: function(data) {
                console.log(data);
                
            },
            error: function(error) {
                console.log(error)
            }
        });
        saveFunction(row);
      }
      if (id === 'deleteBtn') {
        deleteFunction(row);
      }
      if (id === 'cancelBtn') {
        cancelFunction(row);
      }
    }
    }, 1000);
    
    if (id === 'saveBtn') {
      setTimeout(() => {
            $('#saveBtn').removeClass('disabled');
          }, 1000);
    }
    
  });
};
$('body').on('click', '.js-edit-model', function(){
  const row = document.getElementById($(this).closest('tr').attr('id'));
  $(this).closest('.dropdown__menu').prev('.table-options__btn').trigger('click');
  $(this).closest('tr').find('.form-checkbox__input').trigger('click');
    //console.log(selectedRows);
    var ids = [];
    ids.push($(row).data('id'));
    $.ajax({
          type: 'get',
          async: false,
          url: '/objects/'+$('.js-entity-table').data('model')+'/edit_list',
          data: {ids: ids},
          success: function(data) {
            console.log(data)
              editFunction(row, data[$(row).data('id')]);
              if($('.js-select').length){
                  $('.js-select').each(function(){
                      var $this = $(this),
                          $wrap = $this.closest('.position-relative');
                          
                      if($this.find('option').length < 10) {
                          $this.select2({
                              width: 'auto',
                              dropdownParent: $wrap,
                              minimumResultsForSearch: -1
                          });
                      } else {
                          $this.select2({
                              width: 'auto',
                              dropdownParent: $wrap
                          });
                      }
                      
                  })
              };
              if($('.datepicker').length){
                $('.datepicker').each(function(){
                  var field = $(this);
                  var dpicker = $(this).daterangepicker({
                      parentEl: $(this).closest('.date-field'),
                      "singleDatePicker": true,
                      "showDropdowns": true,
                      autoUpdateInput: false,
                      cancelButtonClasses: 'd-none',
                      minYear: 1900,
                      maxYear: 2050,
                      locale: {
                        //format: 'DD.MM.YYYY',
                        cancelLabel: 'Отмена',
                        applyLabel: "Применить",
                      },
                      ranges: false,
                      showCustomRangeLabel: false,
                  }, function(start, end, label) {
                  });
                  dpicker.on('apply.daterangepicker', function(ev, picker) {
                      field.val(picker.startDate.format('DD.MM.YYYY'));
                  });
                });
              };
              if($('.js-field-status').length) {
                $('.selected .js-field-status').each(function(){
                  var $this = $(this),
                    $wrap = $this.closest('.position-relative');
                    console.log($wrap)
                    if($wrap.length)
                      $(".js-field-status").select2({
                          dropdownCssClass : "select2-delivery",
                          //dropdownParent: $wrap,
                          minimumResultsForSearch: -1,
                          width: 32,
                          templateResult: formatStatusSelect,
                          templateSelection: formatStatusSelect
                      }).on('select2:open', (e) => {
                          if(!$(e.target).closest('table').length) {
                              $('.dropcolor').remove();
                              if(!$('.dropcolor').length) {
                                  console.log('add')
                                  $('.select2-results__options').show();
                                  $(".select2-results:not(:has(.dropcolor))")
                                      .append('<div class="dropcolor" data-field="'+$(e.currentTarget).data('field')+'"></div>')
                                  $(".select2-results .dropcolor:not(:has(a))").append('<a class="dropdown-item dropdown-item-color" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="bg-status"></span><span>Палитра цветов</span> <i class="fa fa-chevron-right"></i></a>')
                                      .append('<div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions"><a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Палитра цветов</b></a><div class="d-flex"><div class="clr-field"><button aria-labelledby="clr-open-label"></button><input type="text" class="coloris"></div></div></div>');
                                  Coloris({
                                    el: '.coloris',
                                    parent: '.dropcolor',
                                    defaultColor: $(e.currentTarget).data('color'),
                                    swatches: [
                                    ]
                                  });
                                  $('#clr-color-value').val($(e.currentTarget).data('color'))
                                  $('.clr-field').css({'color': $(e.currentTarget).data('color')})
                              }
                          }
                      }).on('select2:close', () => {
                          $('.dropcolor').remove();
                      }).on('select2:select', function (e) {
                          var data = e.params.data;
                          $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
                      });
                    else
                      $(".js-field-status").select2({
                          dropdownCssClass : "select2-delivery",
                          minimumResultsForSearch: -1,
                          width: 32,
                          templateResult: formatStatusSelect,
                          templateSelection: formatStatusSelect
                      }).on('select2:open', (e) => {
                          if(!$(e.target).closest('table').length) {
                              $('.dropcolor').remove();
                              if(!$('.dropcolor').length) {
                                  console.log('add')
                                  $('.select2-results__options').show();
                                  $(".select2-results:not(:has(.dropcolor))")
                                      .append('<div class="dropcolor" data-field="'+$(e.currentTarget).data('field')+'"></div>')
                                  $(".select2-results .dropcolor:not(:has(a))").append('<a class="dropdown-item dropdown-item-color" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true"><span class="bg-status"></span><span>Палитра цветов</span> <i class="fa fa-chevron-right"></i></a>')
                                      .append('<div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions"><a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Палитра цветов</b></a><div class="d-flex"><div class="clr-field"><button aria-labelledby="clr-open-label"></button><input type="text" class="coloris"></div></div></div>');
                                  Coloris({
                                    el: '.coloris',
                                    parent: '.dropcolor',
                                    defaultColor: $(e.currentTarget).data('color'),
                                    swatches: [
                                    ]
                                  });
                                  $('#clr-color-value').val($(e.currentTarget).data('color'))
                                  $('.clr-field').css({'color': $(e.currentTarget).data('color')})
                              }
                          }
                      }).on('select2:close', () => {
                          $('.dropcolor').remove();
                      }).on('select2:select', function (e) {
                          var data = e.params.data;
                          $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
                      });
                })
              }
          },
          error: function(error) {
              console.log(error)
          }
  });
});
