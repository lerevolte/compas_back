$("body").on('mousedown', "#table input", function(e) {
    e.stopPropagation();
});
$("body").on('click', 'td', function(){
    if($(this).find('.not-selectable').length)
        return;
    $('td.selected').removeClass('selected');
    $(this).addClass('selected');
});
$("body").on('click', '.js-add-field-section', function() {
    $('#addField').find('[name="section_id"]').val($(this).data('section'));
    $('#addField').find('[name="section_id"]').trigger('change');
});
// $("body").on('click', '#table .js-editable', function(e){
//     var field,txt;
//     var $td = $(this).closest('td'), arr_val;

//     if($(this).closest('td').hasClass('selected') && !$(this).hasClass('active') && !$(this).hasClass('disabled')) {
//         if ($(this).data('field') == "date_delivery_status" && $(this).closest('tr').find('[data-field="number"]').data('route')) {
//             return;
//         }
//         var field = $(this).addClass('active');

//         $(this).closest('li').addClass('active');
//         var txt = field.text().trim();
//         var model = field.data('model'),
//             value = field.data('value');
//         if(!model)
//             model = 'orders';
//         if(!value)
//             value = field.text();
//         $.ajax({
//             type: 'get',
//             async: false,
//             url: '/field',
//             data: { name: field.data('field'), value: value, model: model },
//             success: function(data) {
//                 field.html(data);
//                 if(field.find('input').data('type') == 'date') {
//                     var dpicker = field.find('input').daterangepicker({
//                         "singleDatePicker": true,
//                         "showDropdowns": true,
//                         autoUpdateInput: false,
//                         cancelButtonClasses: 'd-none',
//                         minYear: 1900,
//                         maxYear: 2050,
//                         locale: {
//                           //format: 'DD.MM.YYYY',
//                           cancelLabel: 'Отмена',
//                           applyLabel: "Применить",
//                         },
//                         ranges: false,
//                         showCustomRangeLabel: false,
//                     }, function(start, end, label) {
                        
//                     });
//                     dpicker.on('apply.daterangepicker', function(ev, picker) {
//                         console.log('SET')
//                         console.log(picker.startDate.format('DD.MM.YYYY'))
//                         field.find('input').val(picker.startDate.format('DD.MM.YYYY'));
//                         field.find('input').closest('table').trigger('click');
//                     });
//                 }
//                 if(field.find('textarea').length) {
//                     calcHeightTextArea();
//                 }
//                 if(field.find('input[type="text"]').length)
//                     field.find('input[type="text"]:last').caretTo(field.find('input[type="text"]:last').val().length);
//             }
//         });
//         $(this).closest('td').addClass('active');
//     } else if($(this).closest('td').hasClass('selected') && $(this).hasClass('disabled')) {
//         $('.alert-perms').addClass('show');
//     }
// });

 
// $(window).click(function(e) {
//     console.log('click')
//     var $target = $(e.target);
//     var field = $('.js-editable.active');
//     var model = field.closest('table').data('model');
//     if(!$target.closest('td.selected').length)
//         $('td.selected').removeClass('selected');
//     if(!$target.closest('.search').length)
//         $('.js-filter-form').addClass('selected');
    
//     var data = {};
//     data['_token'] = $('[name=_token]').val();
//     data['_method'] = 'PUT';
//     if($target.closest('.js-editable.active').length || $target.prop('tagName') == 'TEXTAREA' || $target.prop('tagName') == 'INPUT')
//         return;
//     if($('#next-to').find('input').length && !$target.closest('#next-to').length) {
//         $('.edit-form [name="id"]').val($('#next-to').data('id'));
//         var txt = $('#next-to').find("input").val();
//         $('#next-to').html(txt);
//         if (!txt)
//             txt = 'null';
//         $('.edit-form [name="mileage_to"]').val(txt);
//         $.ajax({
//             type: 'post',
//             async: false,
//             url: '/editExpense',
//             data: $('.edit-form').serialize(),
//             success: function(data) {
//                 console.log('sds')
//                 console.log(data)
//                 $('.edit-form input').each(function(){
//                     if ($(this).attr('name') != '_token')
//                         $(this).val('');
//                 });
//             }
//         });
//         return false;
//     }
//     if($('#mileage_edit').find('input').length && !$target.closest('#mileage_edit').length) {
//         $('.edit-form [name="id"]').val($('#mileage_edit').data('id'));
//         var txt = $('#mileage_edit').find("input").val();
//         $('#mileage_edit').html(txt);
//         if (!txt)
//             txt = 'null';
//         $('.edit-form [name="mileage_full"]').val(txt);
//         $.ajax({
//             type: 'post',
//             async: false,
//             url: '/editExpense',
//             data: $('.edit-form').serialize(),
//             success: function(data) {
//             console.log(data)
//                 $('.edit-form input').each(function(){
//                     if ($(this).attr('name') != '_token')
//                         $(this).val('');
//                 });
//             }
//         });
//         return false;
//     }

//     if ((!$target.closest('.js-editable.active').length && $target != field) && field.length) {
//         console.log('EDIT')
//         var txt = field.find("input").val(), tr = field.closest('tr');
//         if (!txt)
//             txt = 'null';
//         if(field.data('type') == 'textarea') {
//             txt = field.find("textarea").val()
//             if (!txt)
//                 txt = 'null';
//         }
//         data[field.data('field')] = txt
//         if(field.closest('tr').data('id'))
//             var id = field.closest('tr').data('id');
//         else
//             var id = field.data('id');
        
//         $.ajax({
//             type: 'post',
//             async: false,
//             url: '/'+model+'/'+id,
//             data: objectToQueryString(data),
//             success: function(data) {
//                 if(data == 'success') {
//                     field.closest('td').css({'background': 'green', 'color': 'white'});
//                     setTimeout(function(){field.closest('td').css({'background': 'transparent', 'color': 'black'})}, 500);
//                 }
//                 if(data == 'error') {
//                     field.closest('td').css({'background': 'red', 'color': 'white'});
//                     setTimeout(function(){field.closest('td').css({'background': 'transparent', 'color': 'black'})}, 500);
//                 }

//                 tr.find('.dynamic-val').each(function(){
//                     console.log($(this).data('field'))
//                     $(this).text(data[$(this).data('field')])
//                 });
                
//                 if($('.js-editable.active').find('input').length)
//                     $('.js-editable.active').text($('.js-editable.active').find('input').val())
//                 else
//                     $('.js-editable.active').text($('.js-editable.active').find('textarea').val())

//                 $('.js-editable.active').removeClass('active');
//             },
//             error: function(error) {
//                 console.log(error)
//             }
//         });
//     }
    
// });
$('body').on('click', '.js-filter-field-add', function(){
    var data = {};
    data['_token'] = $('[name=_token]').val();
    data['_method'] = 'PUT';
    data['field'] = $(this).data('field');
    data['data_type'] = $(this).data('type');
    var id = $(this).data('filter');
    
    // $.ajax({
    //     type: 'post',
    //     url: '/filters/'+id+'/add_field',
    //     data: data,
    //     success: function(data) {

    //     }
    // });
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
$('body').on('click', '.js-add-model', function(e){
    var btn = $(this),
        model = $(this).data('model');
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
            location.href = btn.data('url-template') + data.id ;//+ '?edit=y';
        },
        error: function(error) {
            console.log(error)
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
    console.log('/filters/'+id)
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
    console.log($(e.target))
    if($(e.target).prop('tagName') == 'TD' || $(e.target).hasClass('form-group')) {
        var tr = $(e.target).closest('tr'),
            model = tr.closest('table').data('model');
        ddajaxsidepanel.showhidepanel('/objects/'+model+'/show/'+tr.data('id')+'?ajax=Y', "show", 'iframe');
    }
});


