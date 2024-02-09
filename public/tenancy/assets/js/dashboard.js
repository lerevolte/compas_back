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
function statusFieldInit() {

    console.log('statusFieldInit')
    $(".js-field-status").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: formatStatusSelect,
        templateSelection: formatStatusSelect
    }).on('select2:open', (e) => {
        console.log('statusFieldInit')
        if(!$(e.target).closest('table').length) {
            console.log($('.dropcolor.show').find('.coloris'))
            console.log('add11')
            $('.dropcolor').remove();
            if(!$('.dropcolor').length) {
                console.log('add2')
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
    console.log('edit 1')
    console.log(u)
    $.ajax({
        type: 'get',
        url: u,
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.find(".carrier-content").html(),
                s = t.find(".js-sort").html();
            $('.js-sort').html(s);
            $('.carrier-content').html(n);
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
                    console.log('UPDATE')
                    console.log($('.point_status_rect').css('background-color'))
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
    console.log('edit 2')
    $.ajax({
        type: 'get',
        url: addOrUpdateUrlParam('edit', 'y'),
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.find(".carrier-content").html(),
                s = t.find(".js-sort").html();
            $('.js-sort').html(s);
            $('.carrier-content').html(n);
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
function updateContentEditSection(id) {
    console.log('UPDATE SECTION')
    $.ajax({
        type: 'get',
        url: addOrUpdateUrlParam('edit', 'y'),
        success: function(e) {
            $('.toolbar-section[data-id="'+id+'"]').next('.c-body')
            var t = $($.parseHTML(e)),
                n = t.find('.toolbar-section[data-id="'+id+'"]').next('.c-body').html();
            //$('.js-sort').html(s);
            //console.log(n)
            $('.toolbar-section[data-id="'+id+'"]').next('.c-body').html(n);
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
        }
    });
    
}

function editSection(parent_model, model, id, section_id) {
    console.log('updatesection')
    console.log('/'+parent_model+'/edit_section/'+model+'/'+id+'/'+section_id+'?edit=y')
    $.ajax({
        type: 'get',
        url: '/objects/edit_section/'+model+'/'+id+'/'+section_id+'?edit=y',
        success: function(e) {
            var t = $($.parseHTML(e)),
                n = t.html();
            console.log('updatesection1')
            $('.toolbar-section[data-id="'+section_id+'"]').next('.c-body').html(n);
            console.log('updatesection2')
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
        var btn = $(this);
        var model = $('.t-body').data('model');
        var data = {};
        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        console.log('token '+data['_token']);
        console.log('сдшсл1');
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
                    console.log(fd)
                    $.ajax({
                        type: 'post',
                        async: false,
                        url: '/files/update',
                        data: fd,
                        cache:false,
                        contentType: false,
                        processData: false,
                        success: function(data) {
                            console.log('files update')
                            console.log(data);
                            //btn.closest('li').remove();
                        },
                        error: function(data){
                            console.log(data);
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
                            console.log('select '+$(this).val())
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
                    //txt = txt_arr.join(', ');
                } else if (field.data('type') == 'file' || field.data('type') == 'image') {
                    if(field.data('type') == 'file' && $('[name="'+field.data('field')+'"]').val()) {
                        var field_val = $('[name="'+field.data('field')+'"]').val();
                        var model_id;
                        if($('.side-list__item.active').length)
                            model_id = $('.side-list__item.active').data('id');
                        else
                            model_id = $('.active-entity').data('id');
                        //console.log(model+' '+$('.side-list__item.active').data('id')+' '+field.data('field')+' '+field_val+' '+$('input[name=_token]').val())
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
                                console.log('add files')
                                console.log(data)
                                
                            },
                            error: function(data){
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
                                console.log('files update')
                                console.log(data);
                                //btn.closest('li').remove();
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
                        console.log(JSON.stringify(file_ids))
                        fd.append('file_ids', JSON.stringify(file_ids));
                        fd.append('model', $('.t-body').data('model'));
                        fd.append('field', field.data('field'));
                        console.log('REMOVEEE')
                        console.log(fd)
                        $.ajax({
                            type: 'post',
                            async: false,
                            url: '/files/destroy',
                            data: fd,
                            cache:false,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                                //btn.closest('li').remove();
                            },
                            error: function(data){
                                console.log(data);
                            }
                        }); 
                    };

                    
                } else {
                    if(field.find("input").length)
                        txt = field.find("input").val();
                    else
                        txt = field.find("textarea").val();
                    console.log(txt)
                }
                if(field.find("input[type='text']").length > 1) {
                    console.log('MULTIPLE')
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
                console.log('сохраняем')
                
                if(model == 'users')
                    model = 'profile';
                console.log('send data')
                data['_token'] = $('input[name=_token]').val();
                $.ajax({
                    type: 'post',
                    url: '/objects/'+model+'/update/'+$('.side-list__item.active').data('id'),
                    data: objectToQueryString(data),
                    success: function(res) {
                        console.log('saved')
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
                        
                        console.log('сохраняем1')
                        console.log(data)
                        $('.edit-form input').each(function(){
                            if ($(this).attr('name') != '_token')
                                $(this).val('');
                        })
                        if($('.carrier-content input[name="create"]').length) {
                            if($('.project', window.parent.document).length) {
                                $('body', window.parent.document).find('.panelhandle').trigger('click');
                                console.log('before reload')
                                $('.project', window.parent.document).find('#table').DataTable().ajax.reload();
                                console.log('after reload')
                            }
                        };
                        updateContent();
                        
                    },
                    error: function(data) {
                        console.log('error')
                        console.log(data)
                    }
                });
            } else if($('.active-entity').length) {
                console.log('сохраняем')
                console.log('/'+model+'/'+$('.active-entity').data('id'))
                $.ajax({
                    type: 'post',
                    url: '/'+model+'/'+$('.active-entity').data('id'),
                    data: objectToQueryString(data),
                    success: function(data) {
                        console.log('сохраняем1')
                        console.log(data)
                        $('.edit-form input').each(function(){
                            if ($(this).attr('name') != '_token')
                                $(this).val('');
                        })

                        updateContent();

                    },
                    error: function(data) {
                        console.log('error')
                        console.log(data)
                        btn.removeClass('disabled');
                    }
                });
            } else {
                data['_method'] = '';
                $.ajax({
                    type: 'post',
                    url: '/'+model,
                    data: objectToQueryString(data),
                    success: function(data) {
                        location.href = '/'+model
                    }
                });
            }
                
        } else {
            var items = [];
            $('.side-list__item').each(function(){
                items.push($(this).data('id'))
            });
            console.log(items);
            console.log('/'+$('.t-body').data('model')+'/change-sort')
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
        console.log('100px')

        //$(this).closest('.select2-results').css({'height': '100px'});
        $(this).closest('.select2-results').find('ul').hide();
        $('.coloris').trigger('click');
    });
    $('body').on('click', '.dropdown-back', function(){
        //$(this).closest('.select2-results').css({'height': 'auto'});
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
            console.log(val+' - '+field_id+' - '+$('[name=_token]').val());
            if(val) {
                $.ajax({
                    type: 'get',
                    async: false,
                    url: '/field/add_color/',
                    data: { 'id': id, 'model': model, 'field_id': field_id, 'color': val, '_token': $('[name=_token]').val() },
                    success: function(data) {
                        //$(".js-field-status").select2('destroy');
                        $('.select2-container').hide();
                        updateContent();
                        //location.reload();
                    }
                });
            } else {
                $(".js-field-status").select2('destroy');
                updateContent();
            }
            
        }
        
    });
    $('body').on('click', '.js-generate-token', function(){
        console.log('generate_token')
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
    $('body').on('click', '.dropcolor', function(e){
        //e.preventDefault();
        //e.stopPropagation();
    })
    $('body').on('change', '[data-field="car_category"]', function() {
        console.log('CHANGE CATEGORY')
        console.log($(this).find(":selected").val())
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
        console.log('CHANGE MARK')
        $('[data-field="car_model"]').attr('data-mark', $(this).find(":selected").val());
        $('[data-field="car_model"]').attr('data-value', '');
        $('[data-field="car_model"]').html('<span class="empty-val">не заполнено</span>');
        $('[data-field="car_model"]').removeClass('active');
        $('[data-field="car_model"]').removeClass('disabled');
    });
    $("body").on('click', '.js-editable', function(e){
        console.log('tarrara')
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
            console.log('SHOW FIELD')
            console.log('/field/?name='+field.data('field')+'&value='+field.data('value')+'&model='+$('.t-body').data('model')+'&category='+category+'&mark='+mark)
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
                        console.log('haha')
                        field.find('.js-select').select2('open');
                    };
                    if(field.data('type') == 'date') {
                        console.log(field.find('input'))
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
                        init()
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
                            console.log('files')
                            console.log(items)
                            field.find('.js-sort-files').val(items);
                            console.log($('.js-sort-files').val())
                            // $.ajax({
                            //     type: 'post',
                            //     url: '/field_sections/change-sort',
                            //     data: {items: items, '_token': $('[name=_token]').val()},
                            //     success: function(data) {
                            //         //$('.js-save-panel').hide();
                            //     }
                            // });
                            
                          },
                        });
                    //field.find('input').focus();
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
    $('body').on('click', '.js-history', function(e){
        e.preventDefault();
        if ($('#history-table_wrapper').length == 0) {
            var history_table = $('#history-table').DataTable({
                paging: false,
                searching: false,
                autoWidth: false,
                ajax: "/getHistory?entity="+$(this).data('entity')+"&entity_id="+$(this).data('id'),
                
                columns: [
                    { 'data': 'created_at' },
                    { 'data': 'text' },
                    { 'data': 'user' },
                ],
                
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json'
                }
            });
        } else {
            var history_table = $('#history-table').DataTable();
            history_table.ajax.url("/getHistory?entity="+$(this).data('entity')+"&entity_id="+$(this).data('id')).load();
        }

        $('#historyModal').modal('show'); 
    });
    $('body').on('click', '.js-copy-model', function(e){
        e.preventDefault();
        var model = $(this).data('model');
        var btn = $(this);
        $.ajax({
            type: 'post',
            url: '/'+model+'/copy/'+btn.data('id'),
            data: {
                'id': btn.data('id'),
                '_token': $('input[name=_token]').val(),
            },
            success: function(data) {
                var name = '';
                if(data.store_name)
                    name = data.store_name;
                else if(data.address)
                    name = data.address;
                else
                    name = data.name;
                var li = $('<li/>', {'class': 'side-list__item d-flex position-relative active', 'data-id': data.id, 'data-url-template': $('.side-list__item:last').data('url-template'),'data-model': model, 'data-carrier': $('.side-list__item.active').data('carrier')}),
                    btns = $('<div/>', {'class': 'btn btn-light w-100'});
                li.append('<span class="btn btn-drag position-absolute start-0 top-0"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>');
                btns.append('<span>'+name+'</span>');
                btns.append('<a/>', {'class': 'dropdown-toggle', 'role': 'button', 'data-toggle':'dropdown'});
                li.append(btns);
                location.href = $('.side-list__item:last').data('url-template') + data.id;
            }
        });
    });

    $('body').on('click', '.js-restore-model', function(e){
        e.preventDefault();
        var model = $(this).data('model');
        var btn = $(this);
        $.ajax({
            type: 'post',
            url: '/'+model+'/restore/'+btn.data('id'),
            data: {
                'id': btn.data('id'),
                '_token': $('input[name=_token]').val(),
            },
            success: function(data) {
                //updateContent();
                location.reload()
            }
        });
    });
    
    $('body').on('click', '.js-panel-delete', function(e){
        e.preventDefault();
        var model = $('.t-body').data('model'),
            id;
        if($('.side-list__item.active').length) {
            id = $('.side-list__item.active').data('id');
        } else if($('.active-entity').length) {
            id = $('.active-entity').data('id');
        }
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
                    console.log('search sidebar')
                    if($('.sidebar', window.parent.document).length) {
                        console.log('search sidebar ok')
                        $('body', window.parent.document).find('.panelhandle').trigger('click');
                    }
                    if(data == 'Уберите заказ с машины!') {
                        alert(data)
                    } else {
                        if(id == $('.side-list__item.active').data('id')) {
                            $('.carrier-content>.row').addClass('d-none');
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
                    
                    
                }
            });
        }
        
    });

    $("body").on('dblclick', '.js-change-relation', function(){
        var item = $(this),
            field = item.data('field');
        var data = {};
        data[field] = item.data('id');
        data['relation_id'] = item.data('relation-id');
        data['_token'] = $('input[name=_token]').val();
        data['_method'] = 'PUT';
        $.ajax({
            type: 'post',
            url: '/'+item.data('relation')+'/'+$('.side-list__item.active').data('id'),
            data: objectToQueryString(data),
            success: function(data) {
                //$('.side-list__item.active').trigger('click')
                updateContent();
                $.fancybox.close();

            }
        });
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
                //$('.carrier-list__item.active').trigger('click')
            }
        });
    });
    $("body").on('click', '.js-edit-section', function(){
        
        // var model = $(this).data('model');
        if(!$(this).hasClass('active')) {
            //
            if($('.t-body').data('model') == 'cars' || $('.t-body').data('model') == 'drivers' || $('.t-body').data('model') == 'carriers')
                editSection('carriers', $('.t-body').data('model'), $('.side-list__item.active').data('id'), $(this).closest('.toolbar-section').data('id'));
            else if($('.t-body').data('model') == 'supplies' || $('.t-body').data('model') == 'orders') {
                editSection('orders', 'orders', $('.side-list__item.active').data('id'), $(this).closest('.toolbar-section').data('id'));
            }
            else if($('.t-body').data('model') == 'addresses') {
                editSection('addresses', 'orders', $('.side-list__item.active').data('id'), $(this).closest('.toolbar-section').data('id'));
            } else
                updateContentEditSection($(this).closest('.toolbar-section').data('id'));
            $(this).text('Отменить');
            $('.js-save-panel').show();
            $(this).addClass('active')
            // //$(this).text('Сохранить');
            // $(this).closest('.c-top').addClass('active');
            // $(this).closest('.c-top').next('.c-body').addClass('active');//
            // $(this).closest('.c-top').next('.c-body').find('.js-editable').each(function(){
            //     if($(this).closest('li').data('blocked'))
            //         return;
            //     var edit_position = $(this).closest('li').find('.js-edit-position'),
            //         field = $(this).addClass('active'),
            //         txt = field.text().trim();
            //     $.ajax({
            //         type: 'get',
            //         async: false,
            //         url: '/field/',
            //         data: { name: field.data('field'), value: field.data('value'), model: model },
            //         success: function(data) {
            //             field.html("").append(data);
            //             if($('.js-select').length){
            //                 $('.js-select').each(function(){
            //                     var $this = $(this),
            //                         $wrap = $this.closest('.position-relative');

            //                     $this.select2({
            //                         width: 'auto',
            //                         dropdownParent: $wrap
            //                     });
            //                 })
                            
            //             }
            //             edit_position.removeClass('d-none');
            //         }
            //     });
            // })
        } else {
            var result = confirm('Уверены, что хотите отменить изменения?');
            if(result) {
                if($('.carrier-content input[name="create"]').length) {
                    console.log('DROP ORDER');
                    $('.panelhandle').trigger('click');
                    $.ajax({
                        type: 'post',
                        url: '/'+model+'/'+$('.side-list__item.active').data('id'),
                        data: {
                            'id': $('.side-list__item.active').data('id'),
                            '_token': $('input[name=_token]').val(),
                            '_method': 'DELETE'
                        },
                        success: function(data) {
                            console.log('REMOVED ORDER');
                            table.ajax.reload();
                            orders_table_route.ajax.reload();
                        }
                    });
                    $('.js-save-panel').hide();
                    return;
                }
                updateContent();
                $('.js-save-panel').hide();
            }
        }
    });

    // $("body").on('click', '.js-save-fields', function(){
    //     var btn = $(this);
    //     var model = $('.t-body').data('model');
    //     var data = {};
    //     data['_token'] = $('[name=_token]').val();
    //     data['_method'] = 'PUT';
    //     console.log('token '+data['_token']);
        
    //     if($('.js-sort-files.active').length) {
    //         $('.js-sort-files.active').each(function(){
    //             if(!$(this).closest('.js-editable').hasClass('active')) {
    //                 var field = $(this).closest('.js-editable'), txt;
    //                 var fd = new FormData();
    //                 var file_ids = [];
    //                 field.find('.file-item').each(function(){
    //                     if($(this).data('id'))
    //                         file_ids.push($(this).data('id'));
    //                 });
    //                 fd.append('_token', $('input[name=_token]').val());
    //                 fd.append('id', $('.side-list__item.active').data('id'));

    //                 fd.append('file_ids', JSON.stringify(file_ids));
    //                 fd.append('model', $('.t-body').data('model'));
    //                 fd.append('field', field.data('field'));
    //                 console.log(fd)
    //                 $.ajax({
    //                     type: 'post',
    //                     async: false,
    //                     url: '/files/update',
    //                     data: fd,
    //                     cache:false,
    //                     contentType: false,
    //                     processData: false,
    //                     success: function(data) {
    //                         console.log('files update')
    //                         console.log(data);
    //                         //btn.closest('li').remove();
    //                     },
    //                     error: function(data){
    //                         console.log(data);
    //                     }
    //                 });
    //             }
    //         })
    //     }
    //     if($('.js-editable.active').length) {
    //         btn.addClass('disabled');
    //         $('.js-editable.active').each(function(){
    //             var field = $(this), txt;
    //             if (field.data('type') == "switch") {
    //                 txt = field.find("[type='radio']:checked").val();
    //             } else if (field.data('type') == "select" || field.data('type') == "select_dropdown") {
    //                 txt = field.find("select").val()
    //             } else if (field.data('type') == "multiple_checkbox") {
    //                 txt = JSON.stringify(field.find("select").val());
    //             } else if (field.data('type') == "radio") {
    //                 txt = field.find("[type='radio']:checked").val();
    //             } else if (field.data('type') == "checkboxes") {
    //                 var val = [], txt_arr = [], txt;
    //                 field.find(':checkbox:checked').each(function(i){
    //                     val['"'+$(this).val()+'"'] = $(this).val();
    //                     txt_arr.push($(this).val());
    //                 });
    //                 if(txt_arr.length)
    //                     txt = JSON.stringify(txt_arr);
    //                 else
    //                     txt = '';
    //                 //txt = txt_arr.join(', ');
    //             } else if (field.data('type') == 'file' || field.data('type') == 'image') {
    //                 if(field.data('type') == 'file' && $('[name="'+field.data('field')+'"]').val()) {
    //                     var field_val = $('[name="'+field.data('field')+'"]').val();
    //                     var model_id;
    //                     if($('.side-list__item.active').length)
    //                         model_id = $('.side-list__item.active').data('id');
    //                     else
    //                         model_id = $('.active-entity').data('id');
    //                     //console.log(model+' '+$('.side-list__item.active').data('id')+' '+field.data('field')+' '+field_val+' '+$('input[name=_token]').val())
    //                     $.ajax({
    //                         type: 'post',
    //                         async: false,
    //                         url: '/files/store_to_model',
    //                         data: {
    //                             model: model, 
    //                             id: model_id, 
    //                             field: field.data('field'), 
    //                             value: field_val, 
    //                             '_token': $('input[name=_token]').val()
    //                         },
    //                         success: function(data) {
    //                             console.log('add files')
    //                             console.log(data)
                                
    //                         },
    //                         error: function(data){
    //                         }
    //                     });
    //                 }
    //                 if(field.find('.js-sort-files').val()) {
    //                     var fd = new FormData();
    //                     var file_ids = [];
    //                     field.find('.file-item').each(function(){
    //                         if($(this).data('id'))
    //                             file_ids.push($(this).data('id'));
    //                     });
    //                     fd.append('_token', $('input[name=_token]').val());
    //                     fd.append('id', $('.side-list__item.active').data('id'));

    //                     fd.append('file_ids', JSON.stringify(file_ids));
    //                     fd.append('model', $('.t-body').data('model'));
    //                     fd.append('field', field.data('field'));
    //                     $.ajax({
    //                         type: 'post',
    //                         async: false,
    //                         url: '/files/update',
    //                         data: fd,
    //                         cache:false,
    //                         contentType: false,
    //                         processData: false,
    //                         success: function(data) {
    //                             console.log('files update')
    //                             console.log(data);
    //                             //btn.closest('li').remove();
    //                         },
    //                         error: function(data){
    //                             console.log(data);
    //                         }
    //                     });
    //                 }
    //                 if(field.find('.js-delete-file.removed').length) {
    //                     var fd = new FormData();
    //                     var file_ids = [];
    //                     fd.append('_token', $('input[name=_token]').val());
    //                     fd.append('id', $('.side-list__item.active').data('id'));
    //                     field.find('.js-delete-file.removed').each(function(){
    //                         file_ids.push($(this).data('id'));
    //                     });
    //                     console.log(JSON.stringify(file_ids))
    //                     fd.append('file_ids', JSON.stringify(file_ids));
    //                     fd.append('model', $('.t-body').data('model'));
    //                     fd.append('field', field.data('field'));
    //                     console.log('REMOVEEE')
    //                     console.log(fd)
    //                     $.ajax({
    //                         type: 'post',
    //                         async: false,
    //                         url: '/files/destroy',
    //                         data: fd,
    //                         cache:false,
    //                         contentType: false,
    //                         processData: false,
    //                         success: function(data) {
    //                             //btn.closest('li').remove();
    //                         },
    //                         error: function(data){
    //                             console.log(data);
    //                         }
    //                     }); 
    //                 };

                    
    //             } else {
    //                 if(field.find("input").length)
    //                     txt = field.find("input").val();
    //                 else
    //                     txt = field.find("textarea").val();
    //                 console.log(txt)
    //             }
    //             if(field.find("input[type='text']").length > 1) {
    //                 console.log('MULTIPLE')
    //                 field.find("input").each(function(){
    //                     txt = $(this).val();
    //                     data[$(this).attr('name')] = txt
    //                 })
    //             } else {
    //                 if(field.data('type') != 'file')
    //                     data[field.data('field')] = txt
    //             }
    //             if (field.data('field') == "address" && $('#map').length) {
    //                 data['latitude'] = $('[name="latitude"]').val();
    //                 data['longitude'] = $('[name="longitude"]').val();
    //             };
    //         });
    //         console.log('send data1')
    //         console.log(data)
    //         if($('.side-list__item.active').length) {
    //             console.log('сохраняем')
                
    //             if(model == 'users')
    //                 model = 'profile';
    //             console.log('/'+model+'/'+$('.side-list__item.active').data('id'))
    //             $.ajax({
    //                 type: 'post',
    //                 url: '/'+model+'/'+$('.side-list__item.active').data('id'),
    //                 data: objectToQueryString(data),
    //                 success: function(data) {
    //                     console.log('сохраняем1')
    //                     console.log(data)
    //                     $('.edit-form input').each(function(){
    //                         if ($(this).attr('name') != '_token')
    //                             $(this).val('');
    //                     })
    //                     if($('.carrier-content input[name="create"]').length) {
    //                         if($('.project', window.parent.document).length) {
    //                             $('body', window.parent.document).find('.panelhandle').trigger('click');
    //                         }
    //                     };
    //                     updateContent();
                        
    //                 },
    //                 error: function(data) {
    //                     console.log('error')
    //                     console.log(data)
    //                 }
    //             });
    //         } else if($('.active-entity').length) {
    //             console.log('сохраняем')
    //             console.log('/'+model+'/'+$('.active-entity').data('id'))
    //             $.ajax({
    //                 type: 'post',
    //                 url: '/'+model+'/'+$('.active-entity').data('id'),
    //                 data: objectToQueryString(data),
    //                 success: function(data) {
    //                     console.log('сохраняем1')
    //                     console.log(data)
    //                     $('.edit-form input').each(function(){
    //                         if ($(this).attr('name') != '_token')
    //                             $(this).val('');
    //                     })

    //                     updateContent();

    //                 },
    //                 error: function(data) {
    //                     console.log('error')
    //                     console.log(data)
    //                     btn.removeClass('disabled');
    //                 }
    //             });
    //         } else {
    //             data['_method'] = '';
    //             $.ajax({
    //                 type: 'post',
    //                 url: '/'+model,
    //                 data: objectToQueryString(data),
    //                 success: function(data) {
    //                     location.href = '/'+model
    //                 }
    //             });
    //         }
                
    //     } else {
    //         var items = [];
    //         $('.side-list__item').each(function(){
    //             items.push($(this).data('id'))
    //         });
    //         console.log(items);
    //         console.log('/'+$('.t-body').data('model')+'/change-sort')
    //         $.ajax({
    //                 type: 'post',
    //                 url: '/'+$('.t-body').data('model')+'/change-sort',
    //                 data: {items: items, '_token': $('[name=_token]').val(), '_method': 'PUT'},
    //                 success: function(data) {
    //                     $('.js-save-panel').hide();
    //                 }
    //          });
    //     }
    //     if($('.project', window.parent.document).length)
    //         $('.project', window.parent.document).find('.sidepanel-overlay').removeClass('wo-save');
    // });
    $("body").on('click', '.js-reset-fields', function(){
        var result = confirm('Уверены, что хотите отменить изменения?');

        if(result) {
            console.log('NEED DROP?');
            if($('.carrier-content input[name="create"]').length) {
                
                var model = $('.t-body').data('model');
                if($('.project', window.parent.document).length) {
                    $('body', window.parent.document).find('.panelhandle').trigger('click');
                    $('body', window.parent.document).find('tr[data-id="'+$('.side-list__item.active').data('id')+'"]').remove();
                    //$('body', window.parent.document).find('.ddajaxsidepanel').remove();
                    // $('body', window.parent.document).find('.sidepanel-overlay').removeClass('show');
                    // $('body', window.parent.document).find('.sidepanel-overlay').removeClass('wo-save');
                    // $('body', window.parent.document).find('.panelhandle').removeClass('hide');
                    // $('body', window.parent.document).find('tr[data-id="'+$('.side-list__item.active').data('id')+'"]').remove();
                    // $('body', window.parent.document).css({'overflow': ''});
                }
                $.ajax({
                    type: 'post',
                    url: '/'+model+'/'+$('.side-list__item.active').data('id'),
                    data: {
                        'id': $('.side-list__item.active').data('id'),
                        '_token': $('input[name=_token]').val(),
                        '_method': 'DELETE'
                    },
                    success: function(data) {
                        
                    }
                });
                $('.js-save-panel').hide();
                return;
            }
            $('.js-save-panel').hide();
            updateContent();
        }
    });
    $("body").on('click', '.js-delete-relation', function() {
        var item = $(this),
            field = item.data('field');
        var data = {};
        data[field] = 0;
        data['relation_id'] = item.data('relation-id');
        data['_token'] = $('input[name=_token]').val();
        data['_method'] = 'PUT';
        $.ajax({
            type: 'post',
            url: '/'+item.data('model')+'/'+$('.side-list__item.active').data('id'),
            data: objectToQueryString(data),
            success: function(data) {
                updateContent();
                $.fancybox.close();

            }
        });
    });

    // $("body").on('click', '.js-field-hide', function() {
    //     var item = $(this),
    //         field = item.data('field');
    //     var data = {};
    //     data[field] = 0;
    //     data['_token'] = $('input[name=_token]').val();
    //     data['_method'] = 'PUT';
    //     console.log('/'+item.data('model')+'/'+$('.side-list__item.active').data('id'))
    //     $.ajax({
    //         type: 'post',
    //         url: '/'+item.data('model')+'/'+$('.side-list__item.active').data('id'),
    //         data: objectToQueryString(data),
    //         success: function(data) {
    //             $('.side-list__item.active').trigger('click')
    //             $.fancybox.close();

    //         }
    //     });
    // });
    $("body").on('click', '.js-edit-section-title', function() {
        var section_id = $(this).data('id');
        if(!$('.js-edit-section-title-input').length)
            $('#section-title-'+section_id).html('<input class="js-edit-section-title-input" data-id="'+section_id+'" type="text" value="'+$('#section-title-'+section_id).text()+'">');
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
                console.log(file)

                $.ajax({
                    type: 'post',
                    async: false,
                    url: '/files/store',
                    data: fd,
                    cache:false,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        console.log(data)
                        //this.reset();
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
                        

                           


                        // link.text(file.name);
                        // li.append(link).append('<span class="js-delete-file"><i class="fa fa-close"></i></span>')
                        //console.log(field);
                        //field.closest('.position-relative').find('.file-list').append(li);
                        li.insertBefore(file_list.find('.add-file-btn'))
                        if($('.t-body').data('model') == 'users') {
                            //console.log('1')
                            $('.js-save-panel-roles').show();
                        }
                        else {
                            console.log('SAVVVVE')
                            if($('[name="'+data.field_name+'"]').val() != 'null' && $('[name="'+data.field_name+'"]').val()) {
                                console.log(data.field_name+' '+$('[name="'+data.field_name+'"]').val());
                                var values = $.parseJSON($('[name="'+data.field_name+'"]').val());
                                //console.log($('[name="'+data.field_name+'"]').val())
                                //console.log(val)
                                $.each($.parseJSON(data.field_value), function(i, item) {
                                    console.log(item);
                                    console.log(values);
                                    if($.inArray(item.toString(), values) == -1 && $.inArray(item, values) == -1)
                                        values.push(item);
                                });
                                //console.log(JSON.stringify(values))
                                $('[name="'+data.field_name+'"]').val(JSON.stringify(values));
                            } else {
                                 console.log('SAVVVVE1')
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
                alert('Размер файла не должен превышать 2мб')
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
        // btn.addClass('removed');
        // btn.closest('.file-item').addClass('d-none');
        // btn.closest('.js-editable').addClass('active');
        // $('.js-save-panel').show();
        
    });

    $(window).on('click', function(e){
        if($('.js-edit-section-title-input').length && !e.target.closest('.js-edit-section-title-input') && !e.target.closest('.js-edit-section-title')) {
            var section_id = $('.js-edit-section-title-input').data('id');
            var data = {};
            data['name'] = $('.js-edit-section-title-input').val();
            data['_token'] = $('input[name=_token]').val();
            data['_method'] = 'PUT';
            console.log('/field_sections/'+section_id);
            $.ajax({
                type: 'post',
                url: '/field_sections/'+section_id,
                data: objectToQueryString(data),
                success: function(data) {
                    console.log(data)
                    $('#section-title-'+section_id).text($('.js-edit-section-title-input').val());
                }
            });
        }
    });

    $('body').on('click', '.side-list__item', function(e){
        if($(this).hasClass('active'))
            return;
        if ($(e.target).closest('.dropdown-toggle').length || $(e.target).closest('.dropdown-menu').length) {
            return
        }
        if($('.js-editable.active').length && $('.save-panel').is(":visible")) {
            var link = this;

            e.preventDefault();
            var result = confirm('Внимание, все несохраненные данные будут сброшены. Подтвердить?');
            if(!result) {
                return;
            }
        }
        
        var $target = $(e.target);
        
        var btn = $(this), url = $(this).data('url-template');
        if($(this).data('id'))
            url+=$(this).data('id');
        if($(this).data('params'))
            url+=$(this).data('params');
        $('.side-list__item.active').removeClass('active');
        btn.addClass('active');
        if($(this).data('href'))
            url = $(this).data('href');
        location.href = url
        
    });
    if(!$('.side-list__item.active').length ) {
        $('.side-list__item:first').trigger('click');
    };

    $('body').on('change', '[name="address_type"]', function(){
        var type = $(this).val();
        console.log(type)
        $.ajax({
            type: 'get',
            url: '/addresses/list',
            async: false,
            data: {type: type},
            success: function(data) {
                console.log(data);
                var list_html = '<option value>не выбрано</option>';
                $.each(data, function(i, item) {
                    if(data[i].name)
                        list_html += '<option value='+data[i].id+'>'+data[i].name+'</option>';
                    else
                        list_html += '<option value='+data[i].id+'>'+data[i].address+'</option>';
                });
                $('.js-address-list').html(list_html);
                if($('.js-address-list').find('option').length < 10) {
                    $('.js-address-list').select2({
                        width: 'auto',
                        dropdownParent:  $('.js-address-list').closest('.position-relative'),
                        minimumResultsForSearch: -1
                    });
                } else {
                    $('.js-address-list').select2({
                        width: 'auto',
                        dropdownParent:  $('.js-address-list').closest('.position-relative')
                    });
                }
            }
        });
        
    });
    $('body').on('click', '.js-reset-color', function(){
        $(this).prev().val('#dee2e6');
    });
    $(".form-control-status-delivery").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: formatState,
        templateSelection: formatState
    });
    $(".form-control-status-payment").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: formatStatePayment,
        templateSelection: formatStatePayment
    });
    $(".form-control-status-docs").select2({
        minimumResultsForSearch: -1,
        width: 40,
        templateResult: formatStateDoc,
        templateSelection: formatStateDoc
    });
    $(".js-field-status").select2({
        dropdownCssClass : "select2-delivery",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: formatStatusSelect,
        templateSelection: formatStatusSelect
    }).on('select2:open', (e) => {
        $('.dropcolor').remove();
        if(!$('.dropcolor').length && !$(e.target).closest('table').length) {
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
        
    }).on('select2:close', () => {
        
    }).on('select2:select', function (e) {
        var data = e.params.data;
        $(e.currentTarget).closest('.form-group').find('.js-select-text').text(data.text)
    });

    $('body').on('mouseout', '.c-list>li', function() {
        var settings_btn = $(this).find('.settings');
        if(settings_btn.hasClass('show') && settings_btn.is(":hidden") && !$(this).closest('.c-body').hasClass('active')) {
            console.log('trigger')
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
        var input = this;
        var url = $(this).val();
        var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
        var label = $(this).closest('label');
        
        //if (input.files && input.files[0]&& (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) 
        //{
            var reader = new FileReader();

            reader.onload = function (e) {
               label.css({'background-image': 'url('+e.target.result+')', 'background-size': 'cover'});
            }
            reader.readAsDataURL(input.files[0]);
            label.next('.js-list-file-delete').removeClass('d-none');
            label.prev('.js-list-file-val').val(0);
            console.log('val '+label.prev('.js-list-file-val').val());
        //}
      });
    $('body').on('click', '.js-list-file-delete', function(e){
        console.log('click')
        $(this).prev('.list-label-file').find('.js-list-file').val('');
        $(this).closest('.position-relative').find('.js-list-file-val').val(1);
        console.log('val '+$(this).closest('.position-relative').find('.js-list-file-val').val());
        $(this).prev('.list-label-file').css({'background-image': 'url(/img/file-upload.svg?v=2)', 'background-size': '10px'});
        $(this).addClass('d-none');
    });
    $('body').on('click', '#clr-picker', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    $('body').on('click', '.js-create-task', function(e){
        var id = $(this).data('id');
        e.preventDefault();
        var form = $(this).closest('form');
        $.ajax({
            type: 'post',
            url: '/orders/copy/'+id,
            data: {'_token': $('[name=_token]').val(), 'add_task': 1},
            success: function(data) {
                console.log(data)
                $('.carrier-content').append('<div style="display:none"><a rel="ajaxpanel" data-loadtype="iframe" href="/orders/edit/'+data.id+'?ajax=y&create=Y" style="color:#143966"></a></div>')
                
                ddajaxsidepanel.init({
                    targetselector: '[rel="ajaxpanel"]',
                    ajaxloadgif: '/img/squareloading.gif',
                    fx: {dur:500, easing: 'easeInQuad'},
                    openamount:'80%',
                    openamount_minthreshold:'400px'
                });
                ddajaxsidepanel.showhidepanel('/orders/edit/'+data.id+'?ajax=y&create=Y', "show", "iframe");
                window.history.pushState("object or string", "Title", '/orders/edit/'+data.id);
                $('.carrier-content [rel="ajaxpanel"]').trigger('click');
                console.log('.orders-table-routes tr[data-id="'+data.id+'"] [rel="ajaxpanel"]')
                
                
            }
        });
    });
    $('[data-type="file"]').sortable({
      group: 'no-drop-form',
      items: ".file-item",
      update: function( event, ui ) {
        var field = $(ui.item).closest('[data-type="file"]');
        var items = [];
        var $item = ui.item;
        field.find('.file-item').each(function(){
            items.push($(this).data('id'))
        });
        console.log('files')
        console.log(items)
        field.find('.js-sort-files').val(items);
        field.find('.js-sort-files').addClass('active');
        console.log($('.js-sort-files').val())
        $('.js-save-panel').show();
        $('.js-save-panel-roles').show();
        // $.ajax({
        //     type: 'post',
        //     url: '/field_sections/change-sort',
        //     data: {items: items, '_token': $('[name=_token]').val()},
        //     success: function(data) {
        //         //$('.js-save-panel').hide();
        //     }
        // });
        
      },
    });
    $('body').on('change', '.js-select-compare-field', function(){
        var field = $(this).val(),
            model = $(this).data('model');
        console.log('/field/?name='+field+'&model='+model);
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
        console.log('clack')
        if($(this).closest('.card-relations').attr('data-multiple') == 1) {
            $(this).closest('.card').find('.pic').remove();
            $(this).closest('.card').hide();
            $(this).closest('.card')./*find('.js-relation-input').*/find('select').prop('selectedIndex',0);
            console.log('clack')
        } else {
            $(this).closest('.card').find('.pic').remove();
            $(this).closest('.card').find('.empty-val').text('не выбрано');
            $(this).closest('.js-editable').find('select').prop('selectedIndex',0);
        }

        $(this).closest('.js-editable').addClass('active');
        $('.js-save-panel').show();
        // var item = $(this),
        //     field = item.data('field');
        // var data = {};
        // var model = $('.t-body').data('model');
        // data['field_id'] = field;
        // data['relation_id'] = item.data('id');
        // data['_token'] = $('input[name=_token]').val();
        // data['_method'] = 'PUT';
        // console.log(data)f
        // $.ajax({
        //     type: 'post',
        //     url: '/objects/'+model+'/update/'+$('.side-list__item.active').data('id'),
        //     data: objectToQueryString(data),
        //     success: function(data) {
        //         updateContent();

        //     }
        // });
        // $(this).closest('.card');
    });
    $('body').on('click', '.js-change-relation-object', function(e){
        $(this).closest('.js-editable').addClass('active');
        $(this).closest('.card').find('.pic').remove();
        $(this).closest('.card').find('.empty-val').remove();
        $(this).closest('.card').find('.js-relation-input').find('.position-relative').removeClass('d-none');
        $('.js-save-panel').show();
    });
    
    
})
