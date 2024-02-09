'use strict';
moment.locale('ru', {
    months : 'январь_февраль_март_апрель_май_июнь_июль_август_сентябрь_октябрь_ноябрь_декабрь'.split('_'),
    monthsShort : 'Январь_Февраль_Март_Апрель_Май_Июнь_Июль_Август_Сентябрь_Октябрь_Ноябрь_Декабрь'.split('_'),//'янв_фев_мар_апр_май_июн_июл_авг_сен_окт_ноя_дек'.split('_'),
    monthsParseExact : true,
    // weekdays : 'dimanche_lundi_mardi_mercredi_jeudi_vendredi_samedi'.split('_'),
    //weekdaysShort : 'dim._lun._mar._mer._jeu._ven._sam.'.split('_'),
    weekdaysMin : 'ВС_ПН_ВТ_СР_ЧТ_ПТ_СБ'.split('_'),
    weekdaysParseExact : true,
    longDateFormat : {
        LT : 'HH:mm',
        LTS : 'HH:mm:ss',
        L : 'DD.MM.YYYY',
        LL : 'D MMMM YYYY',
        LLL : 'D MMMM YYYY HH:mm',
        LLLL : 'dddd D MMMM YYYY HH:mm'
    },
    meridiem : function (hours, minutes, isLower) {
        return hours < 12 ? 'PD' : 'MD';
    },
    week : {
        dow : 1, // Monday is the first day of the week.
        doy : 4  // Used to determine first week of the year.
    }
});
$.fn.dropdown.Constructor.prototype._addEventListeners = function _addEventListeners() {
    var _this = this;
    $(this._element).on('click.bs.dropdown', function(event) {
        event.preventDefault();
        event.stopPropagation();
        $('.sticky-selected').removeClass('sticky-selected');
        $(_this._element).closest('td').addClass('sticky-selected');
        $(_this._element).closest('tr').addClass('sticky-selected');
        if($(_this._element).hasClass('dropdown-item-color')) {
            $('.select2-results__options').hide();
            $('.coloris').trigger('click');
        }

        _this.toggle();
    });
    $(this._element).on('hidden.bs.dropdown', function(event) {
    });
       

    
};
(function ($) {
    // Behind the scenes method deals with browser
    // idiosyncrasies and such
    $.caretTo = function (el, index) {
        if (el.createTextRange) { 
            var range = el.createTextRange(); 
            range.move("character", index); 
            range.select(); 
        } else if (el.selectionStart != null) { 
            el.focus(); 
            el.setSelectionRange(index, index); 
        }
    };
    // The following methods are queued under fx for more
    // flexibility when combining with $.fn.delay() and
    // jQuery effects.

    // Set caret to a particular index
    $.fn.caretTo = function (index, offset) {
        return this.queue(function (next) {
            if (isNaN(index)) {
                var i = $(this).val().indexOf(index);
                
                if (offset === true) {
                    i += index.length;
                } else if (offset) {
                    i += offset;
                }
                
                $.caretTo(this, i);
            } else {
                $.caretTo(this, index);
            }
            
            next();
        });
    };

    // Set caret to beginning of an element
    $.fn.caretToStart = function () {
        return this.caretTo(0);
    };

    // Set caret to the end of an element
    $.fn.caretToEnd = function () {
        return this.queue(function (next) {
            $.caretTo(this, $(this).val().length);
            next();
        });
    };
}(jQuery));
(function($) {

  $.fn.select2tree = function(options) {
    var defaults = {
      language: "ru-RU",
      theme: "default",
      matcher: matchCustom,
      templateSelection: templateSelectionCategories,
      templateResult: templateResultCategories
    };
    var opts = $.extend(defaults, options);
    var $this = $(this);
    $(this).select2(opts).on("select2:open", function() {
      open($this);
    });
  };

  function templateResultCategories(data, container) {
    if (data.element) {
      var $wrapper = $("<span></span><span>" + data.text + "</span>");
      var $switchSpn = $wrapper.first();
      var $element = $(data.element);
      var $select = $element.parent();
      var $container = $(container);

      $container.attr("val", $element.val());
      $container.attr("data-parent", $element.data("parent"));

      var hasChilds = $select.find("option[data-parent='" + $element.val() + "']").length > 0;
      var isSearching = $(".select2-search__field").val().length > 0;
      if (isSearching) {
        $switchSpn.css({
          "padding": "0 10px 0 10px",
        });
      } else if (hasChilds) {
        $switchSpn.addClass("switch-tree fa");
        if ($switchSpn.hasClass("fa-chevron-right"))
          $switchSpn.removeClass("fa-chevron-right")
          .addClass("fa-chevron-down");
        else
          $switchSpn.removeClass("fa-chevron-down")
          .addClass("fa-chevron-right");

        $switchSpn.css({
          "padding": "0 10px 0 10px",
          "cursor": "pointer"
        });
      }

      if (hasParent($element)) {
        var paddingLeft = getTreeLevel($select, $element.val()) * 2;
        if (!hasChilds) paddingLeft++;
        $container.css("margin-left", paddingLeft + "em");
      }

      return $wrapper;
    } else {
      return data.text;
    }
  };

  function hasParent($element) {
    return $element.data("parent") !== '';
  }

  function getTreeLevel($select, id) {
    var level = 0;
    while ($select.find("option[data-parent!=''][value='" + id + "']").length > 0) {
      id = $select.find("option[value='" + id + "']").data("parent");
      level++;
    }
    return level;
  }


  function moveOption($select, id) {
    if (id) {
      $select.find(".select2-results__options li[data-parent='" + id + "']").insertAfter(".select2-results__options li[val=" + id + "]");
      $select.find(".select2-results__options li[data-parent='" + id + "']").each(function() {
        moveOption($select, $(this).attr("val"));
      });
    } else {

      $(".select2-results__options li[data-parent!='']").css("display", "none");
      $(".select2-results__options li[data-parent='']").appendTo(".select2-results__options ul");
      $(".select2-results__options li[data-parent='']").each(function() {
        moveOption($select, $(this).attr("val"));
      });
    }
  }

  function switchAction($select, id, open) {

    var childs = $(".select2-results__options li[data-parent='" + id + "']");
    //expand childs.
    //childs.each(function() {
    //  switchAction($select, $(this).attr("val"), open);
    //});

    var parent = $(".select2-results__options li[val=" + id + "] span[class]:eq(0)");
    if (open) {
      parent.removeClass("glyphicon-chevron-right")
        .addClass("glyphicon-chevron-down");
      childs.slideDown();
    } else {
      parent.removeClass("glyphicon-chevron-down")
        .addClass("glyphicon-chevron-right");
      childs.slideUp();
    }
  }

  function open($select) {
    setTimeout(function() {

      moveOption($select);
      //override mousedown for collapse/expand 
      $(".switch-tree").mousedown(function() {
        switchAction($select, $(this).parent().attr("val"), $(this).hasClass("glyphicon-chevron-right"));
        event.stopPropagation();
      });
      //override mouseup to nothing
      $(".switch-tree").mouseup(function() {
        return false;
      });

    }, 0);
  }

  function matchCustom(params, data) {
    if ($.trim(params.term) === '') {
      return data;
    }
    if (typeof data.text === 'undefined') {
      return null;
    }
    var term = params.term.toLowerCase();
    var $element = $(data.element);
    var $select = $element.parent();
    var childMatched = checkForChildMatch($select, $element, term);
    if (childMatched || data.text.toLowerCase().indexOf(term) >= 0) {
      $("#" + data._resultId).css("display", "unset");
      return data;
    }
    return null;
  }

  function checkForChildMatch($select, $element, term) {
    var matched = false;
    var childs = $select.find('option[data-parent=' + $element.val() + ']');
    var childMatchFilter = jQuery.makeArray(childs).some(s => s.text.toLowerCase().indexOf(term) >= 0)
    if (childMatchFilter) return true;

    childs.each(function() {
      var innerChild = checkForChildMatch($select, $(this), term);
      if (innerChild) matched = true;
    });

    return matched;
  }

  function templateSelectionCustom(item) {
    if (!item.id || item.id == "-1") {
      return $("<i class='fa fa-hand-o-right'></i><span> " + item.text + "</span>");
    }

    var $element = $(item.element);
    var $select = $element.parent();

    var parentsText = getParentText($select, $element);
    if (parentsText != '') parentsText += ' - ';

    var $state = $(
      "<span> " + parentsText + item.text + "</span>"
    );
    return $state;
  }

  function getParentText($select, $element) {
    var text = '';
    var parentVal = $element.data('parent');
    if (parentVal == '') return text;

    var parent = $select.find('option[value=' + parentVal + ']');

    if (parent) {
      text = getParentText($select, parent);
      if (text != '') text += ' - ';
      text += parent.text();
    }
    return text;
  }
    
})(jQuery);

function objectToQueryString(obj) {
  var str = [];
  for (var p in obj)
    if (obj.hasOwnProperty(p)) {
      str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
    }
  return str.join("&");
}
function initSortable() {
    // Sortable
    $(".js-sort").sortable({
      group: 'no-drop',
      handle: '.btn-drag',
      update: function( event, ui ) {
        var items = [];
        var $item = ui.item;
        $('.side-list__item').each(function(){
            items.push($(this).data('id'))
        });
        $.ajax({
            type: 'post',
            url: '/'+$('.t-body').data('model')+'/change-sort',
            data: {items: items, '_token': $('[name=_token]').val(), '_method': 'PUT'},
            success: function(data) {
            }
         });
      },

    });

    $(".js-sort-side").sortable({
      group: 'no-drop',
      handle: '.js-edit-position-menu',
      update: function( event, ui ) {
        var items = [];
        var $item = ui.item;
        $('.js-sort-side>.sidebar-nav__item').each(function(){
            items.push($(this).data('id'))
        });
        $.ajax({
            type: 'post',
            url: '/menu/change-sort',
            data: {items: items, '_token': $('[name=_token]').val()},
            success: function(data) {
                //$('.js-save-panel').hide();
            }
         });
      },

    });
    
    $(".js-sort-t").sortable({
      group: 'no-drop-t',
      handle: '.btn-drag-section',
      connectWith: '.js-sort-t',
      placeholder: 'section-sortable-placeholder',
      update: function( event, ui ) {
        var $item = ui.item;
        var items_1 = [], items_2 = [];
        
        $('.js-sort-t[data-id="1"] .object-section__toolbar').each(function(){
            items_1.push($(this).data('id'))
        });
        $('.js-sort-t[data-id="2"] .object-section__toolbar').each(function(){
            items_2.push($(this).data('id'))
        });
        $.ajax({
            type: 'post',
            url: '/field_sections/change-sort',
            data: {items_1: items_1, items_2: items_2, '_token': $('[name=_token]').val()},
            success: function(data) {
            }
        });
      },
      start: function( event, ui ) {
        $('.js-sort-t .c-body').hide();
        $('.js-sort-t').sortable('refreshPositions');
      },
      stop: function( event, ui ) {
        $('.js-sort-t .c-body').show();
        $('.js-sort-t').sortable('refreshPositions');
      },

    });
    $(".js-sort-form").sortable({
      group: 'no-drop-form',
      handle: '.btn-drag-field',
      connectWith: '.js-sort-form',
      start: function( event, ui ) {
        $('.js-sort-form .field-content').hide();
        $('.js-sort-form').sortable('refreshPositions');
      },
      stop: function( event, ui ) {
        $('.js-sort-form .field-content').show();
        $('.js-sort-form').sortable('refreshPositions');
      },
      update: function( event, ui ) {
        
        var items = [];
        var $item = ui.item;
        var section = $item.closest('ul').data('section');


        $item.closest('ul').find('li').each(function(){
            if($(this).data('id'))
                items.push($(this).data('id'))
        });
        $.ajax({
            type: 'post',
            url: '/fields/change-sort',
            data: {id: $item.data('id'), items: items, section: section, '_token': $('[name=_token]').val()},
            success: function(data) {
            }
        });
      },

    });
    
    $(".js-sort-form-2").sortable({
      group: 'no-drop-form',
      handle: '.btn-drag-field',
      connectWith: '.js-sort-form-2',
      update: function( event, ui ) {
        
        var items = [];
        var $item = ui.item;
        var section = $item.closest('ul').data('section');


        $item.closest('ul').find('li').each(function(){
            if($(this).data('id'))
                items.push($(this).data('id'))
        });
        $.ajax({
            type: 'post',
            url: '/fields/change-sort',
            data: {id: $item.data('id'), items: items, section: section, '_token': $('[name=_token]').val()},
            success: function(data) {
            }
        });
      },

    });
    $(".file-list").sortable({
      group: 'no-drop-form',
      update: function( event, ui ) {
        
        
      },
    });
}

/* Ajax Side Panel script (v1.0)
* Created: May 1st, 2012. This notice must stay intact for usage 
* Author: Dynamic Drive at http://www.dynamicdrive.com/
* Visit http://www.dynamicdrive.com/ for full source code
*/
var currenturl = window.location.href;
var ddajaxsidepanel = {
    defaults: {ajaxloadgif:'/img/squareloading.gif', fx:{dur:'normal', easing:'swing'}, openamount:'80%', openamount_minthreshold:'400px'},

    $panelref: null,
    $contentarea: null,
    setting: null,
    docdimensions: null,
    paneldimensions: null,
    $ajaxloadgif: null,
    delaytimer: null,

    loadcontent: function(url, type){
        var $ = jQuery
        var loadtype
        if (url == null){
            this.$contentarea.empty()
            this.$panelref.data('state', 'closed')
        }
        else{
            this.$panelref.data('state', 'open')        
            this.$contentarea.html(this.$ajaxloadgif)
            if (type){
                loadtype = type
            }
            else if (url.indexOf('http') != -1){
                var url_rootdomain = url.match(/^http[^:]*:\/\/((?:www\.){0,1}([^\/]+))/i) // match domain name portion of http link
                if (url_rootdomain && location.hostname.indexOf(RegExp.$2)!=-1){ //if URL's root domain (without www) matches current doc's hostname, meaning it's a internal URL (ie: http://mysite.com/page1.htm)
                document.write = document.writeLn = function(){} // overwrite default document.write() function, as it causes major problems if present inside Ajax fetched document
                    url = url.replace(RegExp.$1, location.hostname)
                    loadtype = "ajax"
                }       
                else{
                    loadtype = "iframe"
                }       
            }
            else{
                loadtype = "ajax"
            }
            if (loadtype == "iframe"){
                ddajaxsidepanel.$contentarea.html('<iframe src="javascript:false" style="border:0; margin:0; padding:0; width:100%; height:100%"></iframe>')
                ddajaxsidepanel.$contentarea.find('iframe:eq(0)').attr('src', url)
                window.history.pushState("object or string", "Title", url.split('?')[0]);
                
            }
            else{
                ddajaxsidepanel.$contentarea.load(url)
            }
        }
    },

    showhidepanel:function(url, action, type){
        
        var $ = jQuery, setting = this.setting;
        if($('.sidepanel-overlay').hasClass('no-click'))
            return;
        if(action=="show") {
            $('.sidepanel-overlay').addClass('show');
            $('.sidepanel-overlay').addClass('no-click');
            setTimeout(function(){$('.sidepanel-overlay').removeClass('no-click');},1000);
            $('.panelhandle').addClass('show');
            $('body').css({'overflow': 'hidden'});
        }
        else {
            $('.sidepanel-overlay').removeClass('show');
            $('.sidepanel-overlay').removeClass('wo-save');
            $('.panelhandle').removeClass('show');
            $('body').css({'overflow': ''});
        }
        var panelstate =  this.$panelref.data('state')
        var winwidth = $(window).width(), panelwidth = this.$panelref.outerWidth()
        if (panelwidth < parseInt(setting.openamount_minthreshold))
            return true
        if (setting.openamount_maxwidth && setting.openamount_maxwidth > setting.openamount_minthreshold)
            panelwidth = Math.min(panelwidth, parseInt(setting.openamount_maxwidth))
        if (action =="show" && panelstate == "open")
            this.$panelref.animate({left: '+=50'}, function(){
                ddajaxsidepanel.loadcontent(null)
            })
        var finalcss = (action=="show")? {left: winwidth-panelwidth, opacity: 1} : {left: winwidth, opacity: 0}
        this.$panelref.animate(finalcss,200, (this.$panelref.data('state')=='open')? 'easeOutBack' : setting.fx.easing, function(){
            ddajaxsidepanel.loadcontent(url, type)
        })
        
        return false
    },

    

    init: function(setting){
        
        var $ = jQuery
        this.setting = $.extend({}, this.defaults, setting)
        if (setting.targetselector){
            var $targetlinks = $(setting.targetselector).each(function(){ // seek out targeted selectors on the page
                var $el = $(this)
                $el.on('click', function(e){
                     e.preventDefault();
                });
                $el.on('dblclick', function(e){
                    e.preventDefault();
                    
                    return ddajaxsidepanel.showhidepanel($(this).attr('href'), "show", this.getAttribute('data-loadtype'))
                });
            })
        }
        if (this.$panelref){ // if ajax content panel already defined, just exit
            return
        }
        this.$ajaxloadgif = $('<table width="100%" height="100%" align="center" valign="center" style="text-align:center"><tr><td><img src="' + this.setting.ajaxloadgif + '"/></td></tr></table>')
        this.$panelref = $('<div class="ddajaxsidepanel"><div class="panelhandle"><i class="fa fa-close"></i> Закрыть</div><div class="contentarea"></div></div>').appendTo(document.body)
        this.$panelref
            .css({width:'88%', height:'100%', left:'100%', visibility:'visible', opacity:0})
            .data('state', 'closed')
        this.$contentarea = this.$panelref.find('div.contentarea:eq(0)')
            .click(function(e){
                e.stopPropagation()
            })
        this.$panelref.find('div.panelhandle:eq(0)')
            .attr('title', 'Close Content Panel')
            .on('click', function(){
                if($('.sidepanel-overlay').hasClass('wo-save')) {
                    var result = confirm('Внимание, все несохраненные данные будут сброшены. Подтвердить?');
                    if(result) {
                        ddajaxsidepanel.showhidepanel(null, "hide");
                        window.history.pushState("object or string", "Title", currenturl);
                    }
                 } else {
                    ddajaxsidepanel.showhidepanel(null, "hide");
                    window.history.pushState("object or string", "Title", currenturl);
                 }
                
            })
        $(document).on('click', function(e){

            $(e.target).closest('.new-dropdown-menu')
            if (e.which == 1) {
                if($(e.target).closest('.sidepanel-overlay').length && $('.sidepanel-overlay').hasClass('wo-save')) {
                    var result = confirm('Внимание, все несохраненные данные будут сброшены. Подтвердить?');
                    if(result) {
                        ddajaxsidepanel.showhidepanel(null, "hide");
                        window.history.pushState("object or string", "Title", currenturl);
                    }
                } else {
                    ddajaxsidepanel.showhidepanel(null, "hide");
                    window.history.pushState("object or string", "Title", currenturl);
                }
            }
        })
        this.paneldimensions = {w: this.$panelref.outerWidth(), h: this.$panelref.outerHeight()}
    }

}

jQuery.extend(jQuery.easing, {  
    easeOutBack:function(x, t, b, c, d, s){
        if (s == undefined) s = 1.70158;
        return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
    },
    easeInQuad: function (x, t, b, c, d) {
        return c*(t/=d)*t + b;
    }
})


// Initialize Ajax Side Panel script:

jQuery(function(){
    ddajaxsidepanel.init({
        targetselector: '[rel="ajaxpanel"]',
        ajaxloadgif: '/img/squareloading.gif', //full path to "loading" gif relative to document. When in doubt use absolute URL to image.
        fx: {dur:500, easing: 'easeInQuad'}, // dur: duration of slide effect (milliseconds), easing: 'ease_in_type_string'
        openamount:'80%', // Width of panel when fully opened (Percentage value relative to page, or pixel value
        openamount_minthreshold:'400px' //Minimum required width of panel (when fully opened)  before panel is shown. This prevents panel from being shown on small screens or devices.
    })
});
function formatStatusSelect (state) {
    if (!state.id) {
        return state.text;
    }
    var txt = state.text;
    if(state.element.dataset.file)
        var $state = $(
            '<span id="status-option-'+state.id+'" data-color="'+state.element.dataset.color+'" data-file="'+state.element.dataset.file+'" class="bg-status bg-status-doc" style="background: url('+state.element.dataset.file+') '+state.element.dataset.color+'"></span><span>'+txt+'</span>'
        );
    else
        var $state = $(
            '<span id="status-option-'+state.id+'" data-color="'+state.element.dataset.color+'" class="bg-status" style="background: '+state.element.dataset.color+'"></span><span>'+txt+'</span>'
        );
    return $state;
};
function select2init() {
    $(".js-field-status").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        templateResult: formatStatusSelect,
        templateSelection: formatStatusSelect
    });
    $('.js-field-status').each(function(){
        $(this).on('select2:select', function (e) {
            var data = e.params.data;
            $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
        });
    });
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
            
        });
    };
}

$(document).ready(function(){
    $('body').on('click', '.js-close-modal', function(e){
        e.preventDefault();
        $.fancybox.close();
    })
    $('body').on('change', '.js-field-status', function(e){
        var select = $(this), val = $(this).val(), order = $(this).closest('.form-group').data('id');

        var field_name = select.attr('name'),
            model = $('.t-body').data('model');
        if(!model)
            model = 'orders';
        select.prev('.point_status_rect').css({'background-color': $('#status-option-'+val).data('color')});
        if($('#status-option-'+val).data('file'))
            select.prev('.point_status_rect').css({'background-image': 'url('+$('#status-option-'+val).data('file')+')'});
        else
            select.prev('.point_status_rect').css({'background-image': ''});
        var data = [];
        data['id'] = order;
        data[field_name] = val;

        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        if(!select.closest('tr').length)
            $.ajax({
                type: 'post',
                data: objectToQueryString(data),
                url: '/objects/'+model+'/update/'+order,
                success: function(data) {
                    if($('body', window.parent.document).find('table')) {
                        var row = $('body', window.parent.document).find('tr[data-id="'+$('.side-list__item.active').data('id')+'"]');
                        var ids = [];
                        ids.push($('.side-list__item.active').data('id'));
                        $.ajax({
                              type: 'get',
                              async: false,
                              url: '/objects/'+$('body', window.parent.document).find('.js-entity-table').data('model')+'/show_list',
                              data: {ids: ids},
                              success: function(res) {
                                row.find('.table-body__inner[data-f="'+field_name+'"]').html(res[$('.side-list__item.active').data('id')][field_name]);
                              },
                              error: function(error) {
                                  console.log(error)
                              }
                        });
                        if(row.find('.js-field-status').length) {
                            row.find('.js-field-status').each(function(){
                                var $this = $(this),
                                $wrap = $this.closest('.position-relative');
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
                                    });
                            })
                        }
                    }
                      
                    
                }
            });
    });
    //Select2
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
        
    }
    initSortable();

    $.fancybox.defaults.hash = false;

    //click outside panel without saving
    $('body').on('click', 'a.nav-link, a.pl-25, a.btn', function(e) {
        if($(this).hasClass('dropdown-toggle')) return;
        if($('.js-editable.active').length && $('.save-panel').is(":visible")) {
            var link = this;

            e.preventDefault();
            var result = confirm('Внимание, все несохраненные данные будут сброшены. Подтвердить?');
            if(result) {
                window.location = link.href;
            }
        }
        
    });

    //click color rectangle near select2
    $('body').on('click', '.point_status_rect', function(e){
        $(this).next('select').select2('open');
    });

    //go to first sublink by click parent node
    $('body').on('click', '.js-first-sublink', function(e){
        e.stopPropagation();
        location.href = $(this).closest('.sidebar-nav__link_sub').next('.sidebar-nav__submenu').find('li:first').find('a').attr('href');
    });

    $("body").on('mousedown', "#table input", function(e) {
        e.stopPropagation();
    });
    $("body").on('click', 'td', function(){
        if($(this).find('.not-selectable').length)
            return;
        $('td.selected').removeClass('selected');
        $(this).addClass('selected');
    });

    $('body').on('click', '.js-filter-field-add', function(){
        var data = {};
        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        data['field'] = $(this).data('field');
        data['data_type'] = $(this).data('type');
        var id = $(this).data('filter');
        
        $.ajax({
            type: 'post',
            url: '/filters/show_field',
            data: data,
            success: function(data) {
                $('.js-filter-fields').append(data);
                if($('.js-filter-fields li:last-child').find('select').length) {
                    var $this = $('.js-filter-fields li:last-child').find('select'),
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
                }
                
                
            }
        });
    });
    $('body').on('click', '.js-add-filter', function(e){
        e.preventDefault();

        $('.js-add-filter').addClass('d-none');
        $('.js-search-panel').addClass('d-none');
        $('.js-filter-save-panel').removeClass('d-none');

        var item = $('.filter-item:last-child').clone();
        item.removeClass('active');
        item.addClass('edit');
        item.find('.filter-name').html('<input type="text" class="form-control">');
        $('.filter-list').append(item);
        $('.js-save-filter').attr('data-id', '');
    });
    $('body').on('click', '.js-filter-edit', function(e){
        e.preventDefault();

        $('.js-search-panel').addClass('d-none');
        $('.js-filter-save-panel').removeClass('d-none');
        $('.filter-item.edit').removeClass('edit');

        var item = $(this).closest('.filter-item');
        item.addClass('edit');
        item.find('.filter-name').html('<input type="text" class="form-control" value="'+item.find('.filter-name').text()+'">');
        $('.js-save-filter').attr('data-id', item.attr('data-id'));
    });

    $('body').on('click', '.js-reset-filter', function(e){
        e.preventDefault();

        $('.js-search-panel').removeClass('d-none');
        $('.js-filter-save-panel').addClass('d-none');

        $('.filter-item.edit').remove();
        $('.js-add-filter').removeClass('d-none');
    });

    $('body').on('click', '.js-delete-filter', function(e){
        e.preventDefault();
        var result = confirm('Уверены, что хотите удалить элемент?');
        if(result) {
            var btn = $(this);
            $.ajax({
                type: 'post',
                url: '/filters/'+btn.data('id'),
                data: {
                    'id': btn.data('id'),
                    '_token': $('input[name=_token]').val(),
                    '_method': 'DELETE'
                },
                success: function(data) {
                    btn.closest('.filter-item').remove();
                }
            });
        }
    });
    $('body').on('click', '.js-filter-item input', function(e){
        e.preventDefault();
        e.stopPropagation();
    });
    $('body').on('click', '.js-filter-item', function(e){
        e.preventDefault();
        if($(e.target).closest('[data-toggle="dropdown"]').length)
            return;
        var id = $(this).data('id');
        $('.js-filter-item.active').removeClass('active');
        $(this).addClass('active');
        $.ajax({
            type: 'get',
            url: '/filters/'+id,
            success: function(data) {
                $('.filter-content').html(data);
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
                    
                }
            }
        });
    });
    $('body').on('click', '.js-filter-sort', function(e){
        e.preventDefault();
        e.stopPropagation();
        var $item = $(this),
            filter_item = $(this).closest('.filter-item');
        $item.closest('.dropdown-menu').prev('[data-toggle="dropdown"]').trigger('click');
        if($item.data('direction') == 'up')
            filter_item.prev().before(filter_item);
        else
            filter_item.next().after(filter_item);
        $.ajax({
            type: 'post',
            url: '/filters/change-sort/'+$item.data('id'),
            data: {direction: $item.data('direction'),'_token': $('[name=_token]').val()},
            success: function(data) {
            }
        });
    });
    $('body').on('click', '.js-search-form', function(e){
        if($('.js-filter-form.d-none').length)
            $('.js-filter-form').removeClass('d-none');
        else
            $('.js-filter-form').addClass('d-none');
    });

    $('body').on('dblclick', 'td', function(e){
        if($(e.target).prop('tagName') == 'TD' || $(e.target).hasClass('form-group')) {
            var tr = $(e.target).closest('tr'),
                model = tr.closest('table').data('model');
            ddajaxsidepanel.showhidepanel('/objects/'+model+'/show/'+tr.data('id')+'?ajax=Y', "show", 'iframe');
        }
    });



});
$.fn.dropdown.Constructor.prototype._addEventListeners = function _addEventListeners() {
    var _this = this;
    $(this._element).on('click.bs.dropdown', function(event) {
        event.preventDefault();
        event.stopPropagation();
        if($(_this._element).hasClass('dropdown-item-color')) {
            $('.select2-results__options').hide();
            $('.coloris').trigger('click');
        }

        _this.toggle();
    });
};
$(document).on('click', '.dropcolor .dropdown-menu', function (e) {
  e.stopPropagation();
});
$(document).on('click', '#clr-picker', function(e) {
    e.preventDefault();
    e.stopPropagation();
});
function statusFieldInit() {
    $(".js-field-status").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: formatStatusSelect,
        templateSelection: formatStatusSelect
    }).on('select2:open', (e) => {
        $('.dropcolor').remove();
        if(!$('.dropcolor').length && !$(e.target).closest('table').length) {
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
        
    }).on('select2:close', () => {
        $('.dropcolor').remove();
    }).on('select2:select', function (e) {
        var data = e.params.data;
        $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
    });
    
}
function removeParams(sParam)
{
    var url = window.location.href.split('?')[0]+'?';
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;
 
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] != sParam) {
            url = url + sParameterName[0] + '=' + sParameterName[1] + '&'
        }
    }
    return url.substring(0,url.length-1);
}
function addOrUpdateUrlParam(name, value)
{
  var href = window.location.href;
  var regex = new RegExp("[&\\?]" + name + "=");
  if(regex.test(href))
  {
    regex = new RegExp("([&\\?])" + name + "=\\d+");
    return href.replace(regex, "$1" + name + "=" + value);
  }
  else
  {
    if(href.indexOf("?") > -1)
      return href + "&" + name + "=" + value;
    else
      return href + "?" + name + "=" + value;
  }
}
function updateContent() {
    let url = new URL(location.href);
    let params = new URLSearchParams(url.search.slice(1));
    var u = removeParams('edit');
    u = removeParams('create')
    params.delete('edit');
    params.delete('create');
    $.ajax({
        type: 'get',
        url: u,
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.find(".object-content").html(),
                s = t.find(".js-sort").html();
            $('.js-sort').html(s);
            $('.object-content').html(n);
            $('.select2-container').remove();
            select2init();
            statusFieldInit();
            initSortable();
            if(!$('.side-list__item.active').length)
                $('.side-list__item:first').trigger('click');
            if($('#map').length) {
                myMap = new ymaps.Map("map", {
                    center: [55.76, 37.64],
                    zoom: 9,
                    controls: []
                });
                var defaultMark = new ymaps.Placemark([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);
                if($('[name="latitude"]').length && $('[name="latitude"]').val().length)
                    myMap.setCenter([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);
                else if($('[name="map_data"]').length) {
                    var color = $('.point_status_rect').css('background-color');
                    if($('[name="map_data"]').val().length) {
                        var map_data=$('[name="map_data"]').val().match(/[^,]+,[^,]+/g);
                        var data = [];
                        $.each(map_data, function(i, obj){
                            data.push(obj.split(','));
                        });
                        myPolygon = new ymaps.Polygon([data], {}, {
                            editorDrawingCursor: "crosshair",
                            editorMaxPoints: 1000,
                            fillColor: color,//color,
                            fillOpacity: 0.7,
                            strokeColor: color,
                            strokeWidth: 3
                        });
                    } else
                        myPolygon = new ymaps.Polygon([], {}, {
                            editorDrawingCursor: "crosshair",
                            editorMaxPoints: 1000,
                            fillColor: color,//color,
                            fillOpacity: 0.7,
                            strokeColor: color,
                            strokeWidth: 3
                        });
                    myMap.geoObjects.add(myPolygon);

                    var stateMonitor = new ymaps.Monitor(myPolygon.editor.state);
                    stateMonitor.add("drawing", function (newValue) {
                        //myPolygon.options.set("strokeColor", newValue ? color : '#0000FF');
                    });
                    myPolygon.editor.startDrawing();

                    
                    myPolygon.events.add(['geometrychange'], function (e) {
                        var positions = myPolygon && myPolygon.geometry ? myPolygon.geometry.getCoordinates() : [];
                        var positions_in = [];

                        if(positions[0])
                        {
                            positions = positions[0];

                            if(positions[1])
                                positions_in = positions_in[1];
                        }
                        else
                            positions = [];
                        $('[name="map_data"]').val(positions);
                        $('.js-save-panel').show();
                    });
                }

                myMap.geoObjects.add(defaultMark);
            }
            
            $('.save-panel .blue-btn.disabled').removeClass('disabled');
            $('.save-panel').hide();
        }
    });
    
}
function updateContentEdit() {
    $.ajax({
        type: 'get',
        url: addOrUpdateUrlParam('edit', 'y'),
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.find(".object-content").html(),
                s = t.find(".js-sort").html();
            $('.js-sort').html(s);
            $('.object-content').html(n);
            if(!$('.side-list__item.active').length)
                $('.side-list__item:first').trigger('click');
            if($('#map').length) {
                myMap = new ymaps.Map("map", {
                    center: [55.76, 37.64],
                    zoom: 9,
                    controls: []
                });
                var defaultMark = new ymaps.Placemark([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);

                myMap.setCenter([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);
                myMap.geoObjects.add(defaultMark);
            }
            statusFieldInit();
        }
    });
    
}

function editSection(model, id, section_id) {
    $.ajax({
        type: 'get',
        url: '/objects/edit_section/'+model+'/'+id+'/'+section_id+'?edit=y',
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.html();
            $('.toolbar-section[data-id="'+section_id+'"]').next('.c-body').html(n);
            if($('#map').length) {
                myMap = new ymaps.Map("map", {
                    center: [55.76, 37.64],
                    zoom: 9,
                    controls: []
                });
                var defaultMark = new ymaps.Placemark([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);

                myMap.setCenter([$('[name="latitude"]').val(), $('[name="longitude"]').val()]);
                myMap.geoObjects.add(defaultMark);
            }
            statusFieldInit();
            select2init();
            initSortable();
            if($('.project', window.parent.document).length)
                $('.project', window.parent.document).find('.sidepanel-overlay').addClass('wo-save');
        }
    });
    
}
$(document).ready(function(){
    $("body").on('click', '.js-save-fields', function(){
        var model_id;
        if($('.js-editable.active[data-field="emergency_fund_end_day"]').length) {
            if($('.side-list__item.active').length)
                model_id = $('.side-list__item.active').data('id');
            else
                model_id = $('.active-entity').data('id');
            $.ajax({
                type: 'get',
                async: false,
                url: '/get_count_funds?id='+model_id,
                data: {id: model_id},
                cache:false,
                contentType: false,
                processData: false,
                success: function(data) {
                    $('<div class="d-none js-count-funds">'+data+'</div>').appendTo($('body'));
                    console.log(data);
                },
                error: function(err){
                    console.log(err);
                }
            });
            var result = confirm('Будут изменены '+$('.js-count-funds').text()+' записей, вы уверены?');
            if(!result) {
                $('.js-reset-fields').trigger('click');
                return false
            }
        }
        var btn = $(this);
        var model = $('.t-body').data('model');
        var data = {};
        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        if($('.js-sort-files.active').length) {
            $('.js-sort-files.active').each(function(){
                if(!$(this).closest('.js-editable').hasClass('active')) {
                    var field = $(this).closest('.js-editable'), txt;
                    var fd = new FormData();
                    var file_ids = [];
                    field.find('.file-item').each(function(){
                        if($(this).data('id'))
                            file_ids.push($(this).data('id'));
                    });
                    fd.append('_token', $('input[name=_token]').val());
                    fd.append('id', $('.side-list__item.active').data('id'));

                    fd.append('file_ids', JSON.stringify(file_ids));
                    fd.append('model', $('.t-body').data('model'));
                    fd.append('field', field.data('field'));
                    $.ajax({
                        type: 'post',
                        async: false,
                        url: '/files/update',
                        data: fd,
                        cache:false,
                        contentType: false,
                        processData: false,
                        success: function(data) {
                            console.log(data);
                        },
                        error: function(err){
                            console.log(err);
                        }
                    });
                }
            })
        }
        if($('.js-editable.active').length) {
            btn.addClass('disabled');
            $('.js-editable.active').each(function(){
                var field = $(this), txt;
                if (field.data('type') == "status") {
                    return;
                }
                if (field.data('type') == "switch") {
                    txt = field.find("[type='radio']:checked").val();
                } else if (field.data('type') == "relation") {
                    var select_values = [];
                    if(field.find('.card-relations').data('multiple') != undefined || field.data('multiple') != undefined) {
                        field.find("select").each(function(i){
                            if($(this).val()) {
                                select_values.push($(this).val())
                            }
                        });
                        txt = JSON.stringify(select_values);
                    } else {
                        txt = field.find(".js-select").val();
                    }
                } else if (field.data('type') == "select" || field.data('type') == "select_dropdown") {

                    var select_values = [];
                    if(field.data('multiple') != undefined) {
                        field.find("select").find(':selected').each(function(i){
                            select_values.push($(this).val())
                        });
                        txt = JSON.stringify(select_values);
                    } else {
                        txt = field.find("select").val()
                    }
                } else if (field.data('type') == "multiple_checkbox") {
                    txt = JSON.stringify(field.find("select").val());
                } else if (field.data('type') == "radio") {
                    txt = field.find("[type='radio']:checked").val();
                } else if (field.data('type') == "checkboxes") {
                    var val = [], txt_arr = [], txt;
                    field.find(':checkbox:checked').each(function(i){
                        val['"'+$(this).val()+'"'] = $(this).val();
                        txt_arr.push($(this).val());
                    });
                    if(txt_arr.length)
                        txt = JSON.stringify(txt_arr);
                    else
                        txt = '';
                } else if (field.data('type') == 'file' || field.data('type') == 'image') {
                    if(field.data('type') == 'file' && $('[name="'+field.data('field')+'"]').val()) {
                        var field_val = $('[name="'+field.data('field')+'"]').val();
                        var model_id;
                        if($('.side-list__item.active').length)
                            model_id = $('.side-list__item.active').data('id');
                        else
                            model_id = $('.active-entity').data('id');
                        $.ajax({
                            type: 'post',
                            async: false,
                            url: '/files/store_to_model',
                            data: {
                                model: model, 
                                id: model_id, 
                                field: field.data('field'), 
                                value: field_val, 
                                '_token': $('input[name=_token]').val()
                            },
                            success: function(data) {
                                console.log(data)
                                
                            },
                            error: function(err){
                            }
                        });
                    }
                    if(field.find('.js-sort-files').val()) {
                        var fd = new FormData();
                        var file_ids = [];
                        field.find('.file-item').each(function(){
                            if($(this).data('id'))
                                file_ids.push($(this).data('id'));
                        });
                        fd.append('_token', $('input[name=_token]').val());
                        fd.append('id', $('.side-list__item.active').data('id'));

                        fd.append('file_ids', JSON.stringify(file_ids));
                        fd.append('model', $('.t-body').data('model'));
                        fd.append('field', field.data('field'));
                        $.ajax({
                            type: 'post',
                            async: false,
                            url: '/files/update',
                            data: fd,
                            cache:false,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                                console.log(data);
                            },
                            error: function(data){
                                console.log(data);
                            }
                        });
                    }
                    if(field.find('.js-delete-file.removed').length) {
                        var fd = new FormData();
                        var file_ids = [];
                        fd.append('_token', $('input[name=_token]').val());
                        fd.append('id', $('.side-list__item.active').data('id'));
                        field.find('.js-delete-file.removed').each(function(){
                            file_ids.push($(this).data('id'));
                        });
                        fd.append('file_ids', JSON.stringify(file_ids));
                        fd.append('model', $('.t-body').data('model'));
                        fd.append('field', field.data('field'));
                        $.ajax({
                            type: 'post',
                            async: false,
                            url: '/files/destroy',
                            data: fd,
                            cache:false,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                            },
                            error: function(err){
                                console.log(err);
                            }
                        }); 
                    };

                    
                } else {
                    if(field.find("input").length)
                        txt = field.find("input").val();
                    else
                        txt = field.find("textarea").val();
                }
                if(field.find("input[type='text']").length > 1) {
                    field.find("input").each(function(){
                        txt = $(this).val();
                        data[$(this).attr('name')] = txt
                    })
                } else {
                    if(field.data('type') != 'file')
                        data[field.data('field')] = txt
                }
                if (field.data('field') == "address" && $('#map').length) {
                    data['latitude'] = $('[name="latitude"]').val();
                    data['longitude'] = $('[name="longitude"]').val();
                };
            });
            if($('.side-list__item.active').length) {
                
                if(model == 'users')
                    model = 'profile';
                data['_token'] = $('input[name=_token]').val();
                console.log(data)
                $.ajax({
                    type: 'post',
                    url: '/objects/'+model+'/update/'+$('.side-list__item.active').data('id'),
                    data: objectToQueryString(data),
                    success: function(res) {
                        if($('body', window.parent.document).find('table')) {
                            var row = $('body', window.parent.document).find('tr[data-id="'+$('.side-list__item.active').data('id')+'"]');
                            var ids = [];
                            ids.push($('.side-list__item.active').data('id'));
                            $.ajax({
                                  type: 'get',
                                  async: false,
                                  url: '/objects/'+$('body', window.parent.document).find('.js-entity-table').data('model')+'/show_list',
                                  data: {ids: ids},
                                  success: function(res) {
                                    $.each(data, function(field_name, value){
                                        row.find('.table-body__inner[data-f="'+field_name+'"]').html(res[$('.side-list__item.active').data('id')][field_name]);
                                    });
                                      
                                  },
                                  error: function(error) {
                                      console.log(error)
                                  }
                            });
                            
                        }
                        
                        $('.edit-form input').each(function(){
                            if ($(this).attr('name') != '_token')
                                $(this).val('');
                        })
                        if($('.object-content input[name="create"]').length) {
                            if($('.project', window.parent.document).length) {
                                $('body', window.parent.document).find('.panelhandle').trigger('click');
                                $('.project', window.parent.document).find('#table').DataTable().ajax.reload();
                            }
                        };
                        updateContent();
                        
                    },
                    error: function(err) {
                        console.log(err)
                    }
                });
            }
                
        } else {
            var items = [];
            $('.side-list__item').each(function(){
                items.push($(this).data('id'))
            });
            $.ajax({
                    type: 'post',
                    url: '/'+$('.t-body').data('model')+'/change-sort',
                    data: {items: items, '_token': $('[name=_token]').val(), '_method': 'PUT'},
                    success: function(data) {
                        $('.js-save-panel').hide();
                    }
             });
        }
        if($('.project', window.parent.document).length)
            $('.project', window.parent.document).find('.sidepanel-overlay').removeClass('wo-save');

    });
    $('body').on('click', '.dropdown-item-color', function(){
        $(this).closest('.select2-results').find('ul').hide();
        $('.coloris').trigger('click');
    });
    $('body').on('click', '.dropdown-back', function(){
        $(this).closest('.select2-results').find('ul').show();
        $(this).closest('.dropdown-submenu__actions').removeClass('show')
        Coloris.close();
    });
    $('body').on('click', '.js-btn-add-color', function(){
        Coloris.close();
        if($(this).closest('.dropcolor').length) {
            var val = $('.dropcolor').find('input').val(),
                field_id = $('.dropcolor').data('field'),
                model = $('.side-list__item.active').data('model'),
                id = $('.side-list__item.active').data('id');
            if(model == undefined) {
                model = $('.t-body').data('model');
            }
            if(val) {
                $.ajax({
                    type: 'get',
                    async: false,
                    url: '/field/add_color/',
                    data: { 'id': id, 'model': model, 'field_id': field_id, 'color': val, '_token': $('[name=_token]').val() },
                    success: function(data) {
                        $('.select2-container').hide();
                        updateContent();
                    }
                });
            } else {
                $(".js-field-status").select2('destroy');
                updateContent();
            }
            
        }
        
    });
    $('body').on('click', '.js-generate-token', function(){
        $.ajax({
            type: 'post',
            async: false,
            url: '/user/generate_token',
            data: { '_token': $('[name=_token]').val() },
            success: function(val) {
                $('.token').val(val);
            },
            error: function(err) {
                console.log(err)
            }
        });
        
    });
    $('body').on('change', '[data-field="car_category"]', function() {
        $('[data-field="car_mark"]').attr('data-value', '');
        $('[data-field="car_model"]').attr('data-value', '');
        $('[data-field="car_mark"]').attr('data-category', $(this).find(":selected").val());
        $('[data-field="car_model"]').attr('data-category', $(this).find(":selected").val());
        $('[data-field="car_model"]').attr('data-mark', '');
        $('[data-field="car_mark"]').html('<span class="empty-val">не заполнено</span>');
        $('[data-field="car_model"]').html('<span class="empty-val">не заполнено</span>');
        $('[data-field="car_model"]').removeClass('active');
        $('[data-field="car_mark"]').removeClass('active');
        $('[data-field="car_mark"]').removeClass('disabled');
        $('[data-field="car_model"]').addClass('disabled');
    });
    $('body').on('change', '[data-field="car_mark"]', function() {
        $('[data-field="car_model"]').attr('data-mark', $(this).find(":selected").val());
        $('[data-field="car_model"]').attr('data-value', '');
        $('[data-field="car_model"]').html('<span class="empty-val">не заполнено</span>');
        $('[data-field="car_model"]').removeClass('active');
        $('[data-field="car_model"]').removeClass('disabled');
    });
    $("body").on('click', '.js-editable', function(e){
        var category = 0,
            mark = 0;
        if($(this).hasClass('card-relations') || $(this).closest('li').data('blocked'))
            return;
        if (!$(this).hasClass("disabled") && !$(this).hasClass('active') && e.target.tagName != 'IMG' && e.target.tagName != 'A' && !e.target.closest('.file-control-wrap')) {
            $(this).closest('li').addClass('active');
            if($(this).hasClass('status-group'))
                return;
            var field = $(this).addClass('active');
            
            var txt = field.text().trim();
            if($(this).closest('.js-sort-form').length)
                $('.sidepanel-overlay').addClass('wo-save');

            if($('.project', window.parent.document).length)
                $('.project', window.parent.document).find('.sidepanel-overlay').addClass('wo-save');
            if(field.data('field') == 'car_mark') {
                $('[data-field="car_model"]').removeClass('active').addClass('disabled');
            }
            if(field.data('field') == 'car_category') {
                $('[data-field="car_model"]').removeClass('active').addClass('disabled');
                $('[data-field="car_mark"]').removeClass('active').addClass('disabled');
                if($('[data-field="car_mark"]').data('value'))
                    $('[data-field="car_mark"]').html($('[data-field="car_mark"]').data('value'))
                else
                    $('[data-field="car_mark"]').html('<span class="empty-val">не заполнено</span>');
                if($('[data-field="car_model"]').data('value'))
                    $('[data-field="car_model"]').html($('[data-field="car_model"]').data('value'))
                else
                    $('[data-field="car_model"]').html('<span class="empty-val">не заполнено</span>');
                
            };
            if(field.data('field') == 'car_category') {
                $('[data-field="car_model"]').removeClass('active');
                if($('[data-field="car_model"]').data('value'))
                    $('[data-field="car_model"]').html($('[data-field="car_model"]').data('value'))
                else
                    $('[data-field="car_model"]').html('<span class="empty-val">не заполнено</span>');
                
            }
            if(field.data('field') == 'car_mark' || field.data('field') == 'car_model') {
                category = field.attr('data-category');

            }
            if(field.data('field') == 'car_model') {
                mark = field.attr('data-mark');
            }
            $.ajax({
                type: 'get',
                async: false,
                url: '/field/',
                data: { entity_id: field.data('id'), name: field.data('field'), value: field.data('value'), model: $('.t-body').data('model'), category: category, mark: mark },
                success: function(data) {
                    field.html("").append(data);
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
                        field.find('.js-select').select2('open');
                    };
                    if(field.data('type') == 'date') {
                        var dpicker = field.find('input').daterangepicker({
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
                            field.find('input').val(picker.startDate.format('DD.MM.YYYY'));
                        });
                    }
                    if(field.data('field') == 'address' && $('#map').length) {
                        init();
                    }
                    if(field.data('type') == 'file')
                        $(".file-list").sortable({
                          group: 'no-drop-form',
                          items: ".file-item",
                          update: function( event, ui ) {
                            var items = [];
                            var $item = ui.item;
                            field.find('.file-item').each(function(){
                                items.push($(this).data('id'))
                            });
                            field.find('.js-sort-files').val(items);
                            
                          },
                        });
                    if(field.find('input[type="text"]').length)
                        field.find('input[type="text"]:last').caretTo(field.find('input[type="text"]:last').val().length);
                    if(field.find('textarea').length)
                        field.find('textarea').caretTo(field.find('textarea').val().length);
                    $('.js-save-panel').show();
                    $('.js-save-panel-roles').show();
                }
            });
        }
    });
    
    $('body').on('click', '.js-panel-delete', function(e){
        e.preventDefault();
        var model = $('.t-body').data('model'),
            id = $('.side-list__item.active').data('id');

        var result = confirm('Уверены, что хотите удалить элемент?');
        if(result && id && model) {
            $.ajax({
                type: 'post',
                url: '/objects/' + model + '/destroy/' + id,
                data: {
                    'id': id,
                    '_token': $('input[name=_token]').val(),
                    '_method': 'DELETE'
                },
                success: function(data) {
                    if($('.sidebar', window.parent.document).length) {
                        $('body', window.parent.document).find('.panelhandle').trigger('click');
                    }
                    if(id == $('.side-list__item.active').data('id')) {
                        $('.object-content>.row').addClass('d-none');
                        updateContent();
                    }
                    if(model == 'users')
                        location.href = '/users';
                    else {
                        if($('tr[data-id="'+id+'"]', window.parent.document).length) {
                            $('tr[data-id="'+id+'"]', window.parent.document).remove();
                            $('.sidepanel-overlay', window.parent.document).removeClass('wo-save');
                            $('.panelhandle', window.parent.document).trigger('click');
                        } else if($('.side-list__item').length)
                            $('.side-list__item.active').remove();
                            location.href = $('.side-list__item:first').data('url-template') + $('.side-list__item:first').data('id');  
                    }
                    
                    
                }
            });
        }
        
    });

    $('body').on('click', '.js-link', function(e){
        ddajaxsidepanel.showhidepanel($(this).data('href'), "show", 'iframe');
    });
    
    $("body").on('click', '.js-add-field-section', function() {
        $('#addField').find('[name="section_id"]').val($(this).data('section'));
        $('#addField').find('[name="section_id"]').trigger('change');
    });
    $("body").on('click', '.js-add-section', function() {
        $('.js-section-create').find('[name="column_id"]').val($(this).data('column'));
        $('.js-section-create').find('[name="name"]').val('');
    });
    $('body').on('click', '.js-add-model', function(e){
        var model = $(this).data('model');
        e.preventDefault();
        var form = $(this).closest('form');
        console.log(form.serialize())
        $.ajax({
            type: 'post',
            url: '/'+model,
            data: form.serialize(),
            success: function(data) {
                console.log(data)
                $.fancybox.close();
                var name = '';
                if(data.store_name)
                    name = data.store_name;
                else if(data.address)
                    name = data.address;
                else
                    name = data.name;
                if(model == 'field_sections') {
                    updateContent();
                    return false;
                }
                if(model == 'fines')
                    location.href = '/fines/edit/' + data.id;
                else if(model == 'orders')
                    location.href = $('.side-list__item:last').data('url-template') + data.id;
                else {
                    //console.log($('.side-list__item:last').data('url-template') + data.id + '?edit=y')
                    location.href = $('.side-list__item:last').data('url-template') + data.id + '?edit=y';
                }
            },
            error: function(error) {
                console.log(error)
            }
        });
    });
    $("body").on('click', '.js-section-delete', function(e){
        e.preventDefault()
        var item = $(this),
            section = item.data('section');
        var data = {};
        data['section'] = section;
        data['_token'] = $('input[name=_token]').val();
        $.ajax({
            type: 'post',
            url: '/field_sections/destroy',
            data: objectToQueryString(data),
            success: function(res) {
                if(res)
                    alert(res);
                else
                    item.closest('li.object-section').remove();
            }
        });
    });
    $("body").on('click', '.js-edit-section', function(){
        if(!$(this).hasClass('active')) {
            editSection($('.t-body').data('model'), $('.side-list__item.active').data('id'), $(this).closest('.toolbar-section').data('id'));
            $(this).text('Отменить');
            $(this).addClass('active');
            $('.js-save-panel').show();
        } else {
            var result = confirm('Уверены, что хотите отменить изменения?');
            if(result) {
                updateContent();
                $('.js-save-panel').hide();
            }
        }
    });

    
    $("body").on('click', '.js-reset-fields', function(){
        var result = confirm('Уверены, что хотите отменить изменения?');

        if(result) {
            $('.js-save-panel').hide();
            updateContent();
        }
    });

    $("body").on('change', '.js-file-list', function() {
        var filename = $(this)[0].files.length ? $(this)[0].files[0].name : "",
            field = $(this), file_list = $(this).closest('.file-list');
        if($(this)[0].files.length) {
            var hasError = false;
            $.each($(this)[0].files, function(i, file) {
                var fd = new FormData();
                fd.append('file', file);
                fd.append('_token', $('input[name=_token]').val());
                if($('.side-list__item.active').length)
                    fd.append('id', $('.side-list__item.active').data('id'));
                else
                    fd.append('id', $('.active-entity').data('id'));
                fd.append('model', $('.t-body').data('model'));
                fd.append('field', field.closest('.js-editable').data('field'));

                $.ajax({
                    type: 'post',
                    async: false,
                    url: '/files/store',
                    data: fd,
                    cache:false,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        if(field.closest('.js-editable').data('field') == 'avatar')
                            $('.file-list-item').remove();
                        if(data.extension == 'jpg' || data.extension == 'jpeg' || data.extension == 'png' || data.extension == 'gif' || data.extension == 'svg') {
                            var li = $('<div/>', {'class': 'file-item file-list-item'});
                            li.data('id', data.id);
                            var actions_wrap = $('<div/>', {'class': 'file-control-wrap'});
                            var ul = $('<ul/>', {'class': 'dropdown-menu dropdown-menu__actions', 'x-placemen': 'bottom-start', 'style': 'position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;'});
                            actions_wrap.append($('<div/>', {'class': 'file-control', 'role': 'button', 'data-toggle': 'dropdown', 'aria-expanded': 'false'}).append('<svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>'));
                            
                            ul.append($('<li/>').append('<a href="'+data.file+'" data-fancybox="'+data.field_name+'" class="dropdown-item">Просмотреть</a></li>'))
                            ul.append($('<li/>').append($('<span/>', {'class': 'dropdown-item js-delete-file', 'data-id': data.id}).append('<span class="text-danger">Удалить</span>')));
                            actions_wrap.append(ul);
                            li.append(actions_wrap);
                            li.append($('<img/>', {'src': data.file, 'height': 96}));
                        } else {
                            var li = $('<div/>', {'class': 'file-item file-item-pdf'});
                            li.data('id', data.id);
                            var actions_wrap = $('<div/>', {'class': 'file-control-wrap'});
                            var ul = $('<ul/>', {'class': 'dropdown-menu dropdown-menu__actions', 'x-placemen': 'bottom-start', 'style': 'position: absolute; transform: translate3d(200px, 26px, 0px); top: 0px; left: 0px; will-change: transform;'});
                            actions_wrap.append($('<div/>', {'class': 'file-control', 'role': 'button', 'data-toggle': 'dropdown', 'aria-expanded': 'false'}).append('<svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>'));
                            ul.append($('<li/>').append('<a href="'+data.file+'" data-fancybox="'+data.field_name+'" class="dropdown-item">Просмотреть</a></li>'))
                            ul.append($('<li/>').append($('<span/>', {'class': 'dropdown-item js-delete-file', 'data-id': data.id}).append('<span class="text-danger">Удалить</span>')))
                            actions_wrap.append(ul);
                            li.append(actions_wrap);
                            if(data.extension == 'pdf')
                                li.append($('<img/>', {'src': '/img/pdf.svg', 'height': 30}));
                            else
                                li.append($('<img/>', {'src': '/img/file.svg', 'height': 30}));
                        }
                        
                        li.insertBefore(file_list.find('.add-file-btn'))
                        if($('.t-body').data('model') == 'users') {
                            $('.js-save-panel-roles').show();
                        } else {
                            if($('[name="'+data.field_name+'"]').val() != 'null' && $('[name="'+data.field_name+'"]').val()) {
                                var values = $.parseJSON($('[name="'+data.field_name+'"]').val());
                                $.each($.parseJSON(data.field_value), function(i, item) {
                                    if($.inArray(item.toString(), values) == -1 && $.inArray(item, values) == -1)
                                        values.push(item);
                                });
                                $('[name="'+data.field_name+'"]').val(JSON.stringify(values));
                            } else {
                                $('[name="'+data.field_name+'"]').val(data.field_value);
                            }
                            
                            $('.js-save-panel').show();
                            $('.js-save-panel-roles').show();
                        }
                    },
                    error: function(data){
                        field.val('');
                        hasError = true;
                    }
                });
                
            });
            if(hasError)
                alert('Не удалось загрузить файл');
        }
    });

    $("body").on('click', '.js-delete-file', function() {
        var item = $(this).closest('.file-item'),
            field = $(this).closest('.js-editable');
        if(field.find('.file-item[data-id="'+item.data('id')+'"]').length) {
            $(this).closest('.js-editable').trigger('click');
            item = field.find('.file-item[data-id="'+item.data('id')+'"]');
            item.find('.js-delete-file').addClass('removed');
            item.addClass('d-none');
        } else {
            item.addClass('d-none');
            item.find('.js-delete-file').addClass('removed');
            item = field.find('.file-item[data-id="'+item.data('id')+'"]');
            
            $(this).closest('.js-editable').trigger('click');
        }
        
        $('.js-save-panel').show();
        $('.js-save-panel-roles').show();
        
    });

    $(window).on('click', function(e){
        if($('.js-edit-section-title-input').length && !e.target.closest('.js-edit-section-title-input') && !e.target.closest('.js-edit-section-title')) {
            var section_id = $('.js-edit-section-title-input').data('id');
            var data = {};
            data['name'] = $('.js-edit-section-title-input').val();
            data['_token'] = $('input[name=_token]').val();
            data['_method'] = 'PUT';

            $.ajax({
                type: 'post',
                url: '/field_sections/'+section_id,
                data: objectToQueryString(data),
                success: function(data) {
                    $('#section-title-'+section_id).text($('.js-edit-section-title-input').val());
                }
            });
        }
    });
    $("body").on('click', '.js-edit-section-title', function() {
        var section_id = $(this).data('id');
        if(!$('.js-edit-section-title-input').length)
            $('#section-title-'+section_id).html('<input class="js-edit-section-title-input" data-id="'+section_id+'" type="text" value="'+$('#section-title-'+section_id).text()+'">');
    });
    
    statusFieldInit();

    $('body').on('mouseout', '.c-list>li', function() {
        var settings_btn = $(this).find('.settings');
        if(settings_btn.hasClass('show') && settings_btn.is(":hidden") && !$(this).closest('.c-body').hasClass('active')) {
            settings_btn.trigger('click');
        }
    });

    $('body').on('change', '.hide-color', function(){
        $('[for="'+$(this).attr('id')+'"]').css({'color': $(this).val(), 'border-color': $(this).val()});
    });
    $('body').on('change', '.js-list-color', function(){
        $(this).closest('label').css({'background-color': $(this).val()});
    });

    $('body').on('change', '.js-list-file', function(){
        var input = this,
            url = $(this).val(),
            ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase(),
            label = $(this).closest('label'),
            reader = new FileReader();

        reader.onload = function (e) {
           label.css({'background-image': 'url('+e.target.result+')', 'background-size': 'cover'});
        }
        reader.readAsDataURL(input.files[0]);
        label.next('.js-list-file-delete').removeClass('d-none');
        label.prev('.js-list-file-val').val(0);
    });
    $('body').on('click', '.js-list-file-delete', function(e){
        $(this).prev('.list-label-file').find('.js-list-file').val('');
        $(this).closest('.position-relative').find('.js-list-file-val').val(1);
        $(this).prev('.list-label-file').css({'background-image': 'url(/img/file-upload.svg?v=2)', 'background-size': '10px'});
        $(this).addClass('d-none');
    });
    
    
    $('body').on('change', '.js-select-compare-field', function(){
        var field = $(this).val(),
            model = $(this).data('model');

        $.ajax({
            type: 'get',
            async: false,
            url: '/field/',
            data: { name: field, model: model },
            success: function(data) {
                $('.js-compare-status').each(function(i, elem){
                    $(this).html(data);
                    $(this).find('select').attr('name', 'rules[field_value]['+i+']');
                })
                $(".js-compare-status .js-field-status").select2({
                    dropdownCssClass : "select2-delivery",
                    minimumResultsForSearch: -1,
                    width: 32,
                    templateResult: formatStatusSelect,
                    templateSelection: formatStatusSelect
                });
                $(".js-compare-status .js-select").select2();
            }
        });
    });
    $('body').on('click', '.js-add-relation-object', function(e){
        var new_card = $(this).closest('li').find('.card.d-none').clone();
        $(this).closest('li').find('.card-relations').addClass('active');
        $('.js-save-panel').show();
        new_card.removeClass('d-none');
        new_card.find('select').addClass('js-select');

        $(this).closest('li').find('.card-relations').append(new_card);
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
                
            });
        };

    });
    $('body').on('click', '.js-delete-relation-object', function(e){
        e.preventDefault();
        if($(this).closest('.card-relations').attr('data-multiple') == 1) {
            $(this).closest('.card').find('.pic').remove();
            $(this).closest('.card').hide();
            $(this).closest('.card')./*find('.js-relation-input').*/find('select').prop('selectedIndex',0);
        } else {
            $(this).closest('.card').find('.pic').remove();
            $(this).closest('.card').find('.empty-val').text('не выбрано');
            $(this).closest('.js-editable').find('select').prop('selectedIndex',0);
        }

        $(this).closest('.js-editable').addClass('active');
        $('.js-save-panel').show();
    });
    $('body').on('click', '.js-change-relation-object', function(e){
        $(this).closest('.js-editable').addClass('active');
        $(this).closest('.card').find('.pic').remove();
        $(this).closest('.card').find('.empty-val').remove();
        $(this).closest('.card').find('.js-relation-input').find('.position-relative').removeClass('d-none');
        $('.js-save-panel').show();
    });
    
})


function init_settings_select2() {
    $(".js-sort-values").sortable({
      group: 'no-drop',
      handle: '.btn-drag',
      isValidTarget: function ($item, container) { return $item.parent("ul")[0] == container.el[0]; },
      onDragStart: function ($item, container, _super) {
        // Duplicate items of the no drop area
        if(!container.options.drop)
          $item.clone().insertAfter($item);
        _super($item, container);
        $item.next('.ui-sortable-placeholder').addClass('d-none')
      },
      onDrop: function ($item, container, _super, event) {
        
        }
    });

    if($('.js-select').length){
        $('.js-select').each(function(){
            if($(this).closest('.js-compare-status').length) {
                var $this = $(this),
                    $wrap = $this.closest('.position-relative');

                if($this.find('option').length < 10) {
                    $this.select2({
                        width: '118px',
                        dropdownParent: $wrap,
                        minimumResultsForSearch: -1
                    });
                } else {
                    $this.select2({
                        width: '118px',
                        dropdownParent: $wrap,
                    });
                }
            } else {
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
                        dropdownParent: $wrap,
                    });
                    // var searchfield = $(this).find('.select2-search--inline')
                    // var selection = $(this).find('.select2-selection__rendered')
                    // $(this).find('.select2-search__field').html("")
                    // selection.prepend(searchfield)
                }
            }
            
        })
        
    }
}
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('[name="_token"]').val()
    }
});
$(document).ready(function(){
    init_settings_select2();
    $('body').on('change', '.js-type-field', function(e){
        var type = $(this).val(),
            model = $('.t-body').data('model'),
            is_address = 0;

        if(model == 'addresses')
            is_address = 1;
        $.ajax({
            type: 'get',
            async: false,
            url: '/field/properties/'+type,
            data: { is_address: is_address },
            success: function(data) {
                $('.js-field-props').html("").append(data);
                init_settings_select2();
            }
        });
    })
    $('body').on('click', '.js-delete-dropdown-item', function(){
        $(this).closest('li').remove();
    });
    $('body').on('click', '.js-add-field-value', function(){
        $('.js-sort-values li:first .js-compare-status select').select2("destroy");
        var el = $('.js-sort-values li:first').clone();
         
        el.find('input').val('');
        el.find('.list-label-file').css({'background-image': 'url(/img/file-upload.svg?v=2)', 'background-size': '10px'});
        $('.js-sort-values').append(el);
        el = $('.js-sort-values li:last');
        if(el.find('.js-compare-status')) {
            $('.js-compare-status').each(function(i, elem){
                $(this).find('select').attr('name', 'rules[field_value]['+i+']');
            });
            $('.js-compare-status select').select2({
                dropdownCssClass : "select2-delivery",
                minimumResultsForSearch: -1,
                width: 32,
                templateResult: formatStatusSelect,
                templateSelection: formatStatusSelect
            });
        }
    });
    $('.js-field-create').submit(function (e){
        e.preventDefault();
        var form_data = new FormData($('.js-field-create')[0]);
        console.log(...form_data)
        $.ajax({
            type: 'POST',
            async: false,
            data: form_data,
            //dataType: 'json',
            processData: false,
            contentType: false,
            cache: false,
            url: '/field/store',
            success: function(data) {
                console.log(data);
                location.reload();
            },
            error: function(data) {
                console.log(data);
            }
        });
        return false
    });
    $('body').on('click', '.js-field-update-btn', function(e){
        $.ajax({
            type: 'get',
            async: false,
            data: {field: $(this).data('field')},
            url: '/field/edit/',
            success: function(data) {
                $('.field-content-edit').html(data);
                init_settings_select2();
                statusFieldInit();
            }
        });
        Coloris({
              el: '.coloris-edit',
              parent: 'body',
              swatches: [
              ]
            });
    });

    $('body').on('click', '.js-field-update', function(e){
        e.preventDefault();

        var form_data = new FormData($('.js-field-update').closest('form')[0]);
        const queryString = new URLSearchParams(form_data).toString();
        $.ajax({
            type: 'POST',
            async: false,
            data: form_data,
            //dataType: 'json',
            processData: false,
            contentType: false,
            cache: false,
            url: '/field/update',
            success: function(data) {
                console.log(data);
                location.reload();
            },
            error: function(data) {
                console.log(data);
            }
        });
    });
    $("body").on('click', '.js-field-show', function(e){
        var item = $(this),
            field = item.data('field'),
            section = item.data('section'),
            model = item.data('model');

        var data = {};
        data['field'] = field;
        data['section'] = section;
        data['model'] = model;
        data['visible_always'] = item.val();
        data['_token'] = $('input[name=_token]').val();
        $.ajax({
            type: 'post',
            url: '/field/show',
            data: objectToQueryString(data),
            success: function(data) {
                if(data['visible_always'] == 0 && !item.closest('li[data-id]').find('.js-editable').data('value')) 
                    item.closest('li[data-id]').addClass('hidden-field');
                else
                    item.closest('li[data-id]').removeClass('hidden-field');
                updateContent();
            }
        });
    });
    $("body").on('click', '.js-field-hide', function(e){
        e.preventDefault()
        var item = $(this),
            field = item.data('field'),
            model = item.data('model');
        var data = {};
        data['field'] = field;
        data['model'] = item.closest('.t-body').data('model');
        data['_token'] = $('input[name=_token]').val();
        $.ajax({
            type: 'post',
            url: '/field/hide',
            data: objectToQueryString(data),
            success: function(data) {
                item.closest('li[data-id]').remove();
                updateContent();
            }
        });
    });
    $("body").on('click', '.js-field-destroy', function(e){
        e.preventDefault()
        var result = confirm('Уверены, что хотите удалить поле?');
        if(result) {
            var item = $(this),
                field = item.data('field');
            var data = {};
            data['_token'] = $('input[name=_token]').val();
            $.ajax({
                type: 'post',
                url: '/field/destroy/'+field,
                data: objectToQueryString(data),
                success: function(data) {
                    item.closest('li[data-id]').remove();
                }
            });
        }
    });
    
})

