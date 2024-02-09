function objectToQueryString(obj) {
  var str = [];
  for (var p in obj)
    if (obj.hasOwnProperty(p)) {
      str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
    }
  return str.join("&");
}
$(document).ready(function(){
    $("body").on('click', 'tr', function(){
        $('tr.selected').removeClass('selected');
        $(this).addClass('selected');
    });
    $("body").on('click', 'td', function(){
        $('td.selected').removeClass('selected');
        $(this).addClass('selected');
    });
    $("body").on('click', '.js-editable', function(){
        var field,txt;

        if($(this).closest('td').hasClass('selected') && !$(this).hasClass('active') && !$(this).hasClass('disabled')) {
            field = $(this).addClass('active');
            txt = field.text().trim();
            if(field.data('type') == 'textarea') {
                field.html("").append("<textarea>"+txt+"</textarea>");
                calcHeightTextArea();
                field.find('textarea').caretTo(field.find('textarea').val().length);
            } else {
                field.html("").append('<input type="text" value="'+txt+'">');
                field.find('input').caretTo(field.find('input').val().length);
            }
        }
    });
    
    $(window).click(function(e) {
        var $target = $(e.target);
        var field = $('.js-editable.active');
        var model = field.closest('table').data('model');
        if(!$target.closest('td.selected').length)
            $('td.selected').removeClass('selected');
        if (model != 'expenses' && model != 'salaries' && model != 'products' && model != 'shipments') {

            return;
        }
        var data = {};
        data['_token'] = $('[name=_token]').val();
        data['_method'] = 'PUT';
        if($target.closest('.js-editable.active').length || $target.prop('tagName') == 'TEXTAREA' || $target.prop('tagName') == 'INPUT')
            return;
        if($('#next-to').find('input').length && !$target.closest('#next-to').length) {
            $('.edit-form [name="id"]').val($('#next-to').data('id'));
            var txt = $('#next-to').find("input").val();
            $('#next-to').html(txt);
            if (!txt)
                txt = 'null';
            $('.edit-form [name="mileage_to"]').val(txt);
            $.ajax({
                type: 'post',
                async: false,
                url: '/editExpense',
                data: $('.edit-form').serialize(),
                success: function(data) {
                    console.log('sds')
                    console.log(data)
                    $('.edit-form input').each(function(){
                        if ($(this).attr('name') != '_token')
                            $(this).val('');
                    });
                }
            });
            return false;
        }
        if($('#mileage_edit').find('input').length && !$target.closest('#mileage_edit').length) {
            $('.edit-form [name="id"]').val($('#mileage_edit').data('id'));
            var txt = $('#mileage_edit').find("input").val();
            $('#mileage_edit').html(txt);
            if (!txt)
                txt = 'null';
            $('.edit-form [name="mileage_full"]').val(txt);
            $.ajax({
                type: 'post',
                async: false,
                url: '/editExpense',
                data: $('.edit-form').serialize(),
                success: function(data) {
                console.log(data)
                    $('.edit-form input').each(function(){
                        if ($(this).attr('name') != '_token')
                            $(this).val('');
                    });
                }
            });
            return false;
        }
        if ((!$target.closest('.js-editable.active').length && $target != field) && field.length) {
            var txt = field.find("input").val(), tr = field.closest('tr');
            if (!txt)
                txt = 'null';
            if(field.data('type') == 'textarea') {
                txt = field.find("textarea").val()
                if (!txt)
                    txt = 'null';
                //$('.edit-form [name="'+field.data('field')+'"]').val(txt);
            }
            data[field.data('field')] = txt
            if(field.closest('tr').data('id'))
                var id = field.closest('tr').data('id');
            else
                var id = field.data('id');
            
            $.ajax({
                type: 'post',
                async: false,
                url: '/'+model+'/'+id,
                data: objectToQueryString(data),
                success: function(data) {
                    // $('.edit-form input').each(function(){
                    //     if ($(this).attr('name') != '_token')
                    //         $(this).val('');
                    // })
                    console.log(data)
                    if(data == 'success') {
                        field.closest('td').css({'background': 'green', 'color': 'white'});
                        setTimeout(function(){field.closest('td').css({'background': 'transparent', 'color': 'black'})}, 500);
                    }
                    if(data == 'error') {
                        field.closest('td').css({'background': 'red', 'color': 'white'});
                        setTimeout(function(){field.closest('td').css({'background': 'transparent', 'color': 'black'})}, 500);
                    }

                    tr.find('.dynamic-val').each(function(){
                        console.log($(this).data('field'))
                        $(this).text(data[$(this).data('field')])
                    });
                    
                    if($('.js-editable.active').find('input').length)
                        $('.js-editable.active').text($('.js-editable.active').find('input').val())
                    else
                        $('.js-editable.active').text($('.js-editable.active').find('textarea').val())

                    $('.js-editable.active').removeClass('active');
                }
            });
        }
        
    });
    $(document).on('keypress',function(e) {
        var field, txt;
        console.log(e.which)
        if(e.which != 13 && $('td.selected').length && !$('td.selected .js-editable.active').length) {
            field = $('td.selected .js-editable').addClass('active')//.removeClass('selected');
            txt = field.text().trim();
            if(field.data('type') == 'textarea') {
                field.html("").append("<textarea></textarea>");
                calcHeightTextArea();
                $('.js-editable.active textarea').caretTo($('.js-editable.active textarea').val().length);
            } else {
                field.html("").append('<input type="text" value="">');
                $('.js-editable.active input').caretTo($('.js-editable.active input').val().length);
            }
            
        }
    });
    $('body').on('click', '.side-list__item', function(e){
        if($(this).hasClass('active'))
            return;
        if ($(e.target).closest('.dropdown-toggle').length || $(e.target).closest('.dropdown-menu').length) {
            return
        }
        e.preventDefault();
        var $target = $(e.target);
        
        var btn = $(this), url = $(this).data('url-template')+$(this).data('id');
        $('.side-list__item.active').removeClass('active');
        btn.addClass('active');

        location.href = url
        
    });
    if(!$('.side-list__item.active').length) {
        $('.side-list__item:first').trigger('click');
    };
});