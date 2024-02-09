function objectToQueryString(obj) {
  var str = [];
  for (var p in obj)
    if (obj.hasOwnProperty(p)) {
      str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
    }
  return str.join("&");
}
$(document).ready(function(){
    

    $("body").on('click', '.js-editable', function(e){
        console.log(e.target.tagName)
        if (!$(this).hasClass("disabled") && !$(this).hasClass('active') && e.target.tagName != 'IMG' && e.target.tagName != 'A') {
            var field = $(this).addClass('active');
            var txt = field.text().trim();
            $.ajax({
                type: 'get',
                async: false,
                url: '/field/',
                data: { name: field.data('field'), value: field.data('value'), model: $('.t-body').data('model') },
                success: function(data) {
                    field.html("").append(data);
                    // if (field.data('field') == "type") {
                    //     field.html("").append('<div class="switch switch-fluid" style="width: 300px;"><div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-val-1" value="1"><label class="form-check-label" for="type-val-1">Легковой</label></div><div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-val-2" value="2"><label class="form-check-label" for="type-val-2">Грузовой</label></div><div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-val-3" value="3"><label class="form-check-label" for="type-val-3">Пеший</label></div></div>');
                    //     field.find('#type-val-'+field.data('value')).trigger('click');
                    // }
                    // if (field.data('field') == "properties") {
                    //     var values = txt.split(', ');
                    //     field.html("").append("<div class=\"form-check\"><input class=\"form-check-input\" name=\"properties[]\" type=\"checkbox\" value=\"Гидролифт\"><label class=\"form-check-label\">Гидролифт</label></div><div class=\"form-check\"><input class=\"form-check-input\" name=\"properties[]\" type=\"checkbox\" value=\"Манипулятор\"><label class=\"form-check-label\">Манипулятор</label></div><div class=\"form-check\"><input class=\"form-check-input\" name=\"properties[]\" type=\"checkbox\" value=\"Ручная\"><label class=\"form-check-label\">Ручная</label></div>");
                    //     values.forEach(function(item, i, arr) {
                    //         field.find('[value="' + item.trim() + '"]').prop('checked', true);
                    //     });
                    // }
                    if($('.js-select').length){
                        $('.js-select').each(function(){
                            var $this = $(this),
                                $wrap = $this.closest('.position-relative');

                            $this.select2({
                                // closeOnSelect : false,
                                // allowHtml: true,
                                // allowClear: true,
                                width: 'auto',
                                dropdownParent: $wrap
                                // placeholder: function(){
                                //     $(this).data('placeholder');
                                // }
                            });
                        })
                        
                    };
                    if(field.data('type') == 'date') 
                        field.find('input').daterangepicker({
                            "singleDatePicker": true,
                            "showDropdowns": true,
                            autoUpdateInput: true,
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
                    //field.find('input').focus();
                    if(field.find('input[type="text"]').length)
                        field.find('input[type="text"]:last').caretTo(field.find('input[type="text"]:last').val().length);
                    $('.js-save-panel').show();
                }
            });
        }
    });

    


    $('body').on('click', '.js-add-model', function(e){
        var model = $(this).data('model');
        e.preventDefault();
        var form = $(this).closest('form');
        $.ajax({
            type: 'post',
            url: '/'+model,
            data: form.serialize(),
            success: function(data) {
                $.fancybox.close();
                if(model == 'field_sections')
                    return false;
                var li = $('<li/>', {'class': 'carrier-list__item d-flex position-relative active', 'data-id': data.id, 'data-model': model, 'data-carrier': $('.carrier-list__item.active').data('carrier')}),
                    btns = $('<div/>', {'class': 'btn btn-light w-100'});
                li.append('<span class="btn btn-drag position-absolute start-0 top-0"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>');
                btns.append('<span>'+data.name+'</span>');
                btns.append('<a/>', {'class': 'dropdown-toggle', 'role': 'button', 'data-toggle':'dropdown'});
                li.append(btns);
                $('.c-drag-list').append(li);
                li.trigger('click');
            }
        });
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
                var li = $('<li/>', {'class': 'carrier-list__item d-flex position-relative active', 'data-id': data.id, 'data-model': model, 'data-carrier': $('.carrier-list__item.active').data('carrier')}),
                    btns = $('<div/>', {'class': 'btn btn-light w-100'});
                li.append('<span class="btn btn-drag position-absolute start-0 top-0"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>');
                btns.append('<span>'+data.name+'</span>');
                btns.append('<a/>', {'class': 'dropdown-toggle', 'role': 'button', 'data-toggle':'dropdown'});
                li.append(btns);
                $('.c-drag-list').append(li);
                li.trigger('click');
            }
        });
    });

    $('body').on('click', '.js-delete-model', function(e){
        e.preventDefault();
        var model = $(this).data('model');
        var btn = $(this);
        $.ajax({
            type: 'post',
            url: '/'+model+'/'+btn.data('id'),
            data: {
                'id': btn.data('id'),
                '_token': $('input[name=_token]').val(),
                '_method': 'DELETE'
            },
            success: function(data) {
                if(btn.data('id') == $('.carrier-list__item.active').data('id'))
                    $('.carrier-content>.row').addClass('d-none');
                btn.closest('.carrier-list__item').remove();
            }
        });
    });

    $("body").on('dblclick', '.js-change-relation', function(){
        var item = $(this),
            field = item.data('field');
        var data = {};
        data[field] = item.data('id');
        data['_token'] = $('input[name=_token]').val();
        data['_method'] = 'PUT';
        $.ajax({
            type: 'post',
            url: '/'+item.data('relation')+'/'+$('.carrier-list__item.active').data('id'),
            data: objectToQueryString(data),
            success: function(data) {
                $('.carrier-list__item.active').trigger('click')
                $.fancybox.close();

            }
        });
    });

    

    $("body").on('click', '.js-add-field-section', function() {
        $('#addField').find('[name="section_id"]').val($(this).data('section'));
        $('#addField').find('[name="section_id"]').select2().trigger('change');
    });

    $("body").on('click', '.js-edit-section', function(){
        var model = $(this).data('model');
        if(!$(this).hasClass('active')) {
            $('.js-save-panel').show();
            $(this).addClass('active')
            //$(this).text('Сохранить');
            $(this).closest('.c-top').next('.c-body').addClass('active');
            $(this).closest('.c-top').next('.c-body').find('.js-editable').each(function(){
                var edit_position = $(this).closest('li').find('.js-edit-position'),
                    field = $(this).addClass('active'),
                    txt = field.text().trim();
                $.ajax({
                    type: 'get',
                    async: false,
                    url: '/field/',
                    data: { name: field.data('field'), value: field.data('value'), model: model },
                    success: function(data) {
                        field.html("").append(data);
                        if($('.js-select').length){
                            $('.js-select').each(function(){
                                var $this = $(this),
                                    $wrap = $this.closest('.position-relative');

                                $this.select2({
                                    width: 'auto',
                                    dropdownParent: $wrap
                                });
                            })
                            
                        }
                        edit_position.removeClass('d-none');
                    }
                });
            })
        }
    });

    $("body").on('click', '.js-save-fields', function(){
        var model = $('.t-body').data('model');
        var data = {};
        data['_token'] = $('.edit-form [name=_token]').val();
        data['_method'] = 'PUT';
        if($('.js-editable.active').length) {
            $('.js-editable.active').each(function(){
                var field = $(this), txt;
                if (field.data('type') == "switch") {
                    txt = field.find("[type='radio']:checked").val();
                } else if (field.data('type') == "select" || field.data('type') == "select_dropdown") {
                    txt = field.find("select").val()
                } else if (field.data('type') == "radio") {
                    txt = field.find("[type='radio']:checked").val();
                } else if (field.data('type') == "checkboxes" || field.data('type') == "multiple_checkbox") {
                    var val = [], txt_arr = [], txt;
                    field.find(':checkbox:checked').each(function(i){
                        val['"'+$(this).val()+'"'] = $(this).val();
                        txt_arr.push($(this).val());
                    });
                    txt = JSON.stringify(txt_arr);
                    //txt = txt_arr.join(', ');
                } else if (field.data('type') == 'file') {

                } else {
                    txt = field.find("input").val();
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
                console.log(txt)
            });
            console.log('DATA')
            console.log(data)
            $.ajax({
                type: 'post',
                url: '/'+model+'/'+$('.carrier-list__item.active').data('id'),
                data: objectToQueryString(data),
                success: function(data) {
                    console.log(data)
                    $('.edit-form input').each(function(){
                        if ($(this).attr('name') != '_token')
                            $(this).val('');
                    })
                    $('.js-save-panel').hide();
                    $('.carrier-list__item.active').trigger('click')
                }
            });
        } else {
            var items = [];
            $('.carrier-list__item').each(function(){
                items.push($(this).data('id'))
            });
            console.log(items)
            $.ajax({
                    type: 'post',
                    url: '/'+$('.t-body').data('model')+'/change-sort',
                    data: {items: items, '_token': $('[name=_token]').val()},
                    success: function(data) {
                        $('.js-save-panel').hide();
                    }
             });
        }
    });
    $("body").on('click', '.js-delete-relation', function() {
        var item = $(this),
            field = item.data('field');
        var data = {};
        data[field] = 0;
        data['_token'] = $('input[name=_token]').val();
        data['_method'] = 'PUT';
        $.ajax({
            type: 'post',
            url: '/'+item.data('model')+'/'+$('.carrier-list__item.active').data('id'),
            data: objectToQueryString(data),
            success: function(data) {
                $('.carrier-list__item.active').trigger('click')
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
    //     $.ajax({
    //         type: 'post',
    //         url: '/'+item.data('model')+'/'+$('.carrier-list__item.active').data('id'),
    //         data: objectToQueryString(data),
    //         success: function(data) {
    //             $('.carrier-list__item.active').trigger('click')
    //             $.fancybox.close();

    //         }
    //     });
    // });
    $("body").on('click', '.js-edit-section-title', function() {
        var section_id = $(this).data('id');
        $('#section-title-'+section_id).html('<input class="js-edit-section-title-input" data-id="'+section_id+'" type="text" value="'+$('#section-title-'+section_id).text()+'">');
    });

    $("body").on('change', '.js-file-list', function() {
        var filename = $(this)[0].files.length ? $(this)[0].files[0].name : "",
            field = $(this);
        if($(this)[0].files.length) {
            $.each($(this)[0].files, function(i, file) {
                //console.log(file)
                var li = $('<li/>', {'class': 'file-list-item'}),
                    link = li.append($('<a/>', {'class': 'file-list-link'}));
                link.text(file.name);
                li.append(link).append('<span class="js-delete-file"><i class="fa fa-close"></i></span>')
                console.log(li);
                field.closest('.position-relative').find('.file-list').append(li);

                var fd = new FormData();
                fd.append('file', file);
                fd.append('_token', $('input[name=_token]').val());
                fd.append('id', $('.carrier-list__item.active').data('id'));
                fd.append('model', $('.t-body').data('model'));
                fd.append('field', field.closest('.js-editable').data('field'));
                console.log(fd)
                $.ajax({
                    type: 'post',
                    async: false,
                    url: '/files/store',
                    data: fd,
                    cache:false,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        //this.reset();
                        console.log(data);
                    },
                    error: function(data){
                        console.log(data);
                    }
                });
            });
        }
    });
    $("body").on('click', '.js-delete-file', function() {
        var btn = $(this);

        var fd = new FormData();
        fd.append('_token', $('input[name=_token]').val());
        fd.append('id', $('.carrier-list__item.active').data('id'));
        fd.append('file_id', btn.data('id'));
        fd.append('model', $('.t-body').data('model'));
        fd.append('field', btn.closest('.js-editable').data('field'));
        $.ajax({
            type: 'post',
            async: false,
            url: '/files/destroy',
            data: fd,
            cache:false,
            contentType: false,
            processData: false,
            success: function(data) {
                console.log(data);
                btn.closest('li').remove();
            },
            error: function(data){
                console.log(data);
            }
        });
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
})
