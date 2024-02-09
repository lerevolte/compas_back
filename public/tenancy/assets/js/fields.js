function isVisible($el) {
  var winTop = $(window).scrollTop();
  var winBottom = winTop + $(window).height();
  var elTop = $el.offset().top;
  var elBottom = elTop + $el.height();
  return ((elBottom<= winBottom) && (elTop >= winTop));
}
function select2init() {
    $(".field-color").select2({
        dropdownCssClass : "select2-color",
        minimumResultsForSearch: -1,
        width: 32,
        templateResult: fieldColor,
        templateSelection: fieldColor
    });
    $('body').on('change', '.field-color', function(e){
        var val = $(this).find('option:selected').data('value'), select = $(this);
        select.prev('.point_status_rect').removeClass().addClass('point_status_rect point_status_rect-'+val);
    });
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
    select2init();
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
                select2init();
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
        console.log(el)
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
                console.log($(this).data('field'))
                console.log(data)
                $('.field-content-edit').html(data);
                select2init();
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
        // var form = $(this).closest('form');
        // $.ajax({
        //     type: 'post',
        //     async: false,
        //     data: form.serialize(),
        //     url: '/field/update',
        //     success: function(data) {
        //         location.reload();
        //     }
        // });

        var form_data = new FormData($('.js-field-update').closest('form')[0]);
        console.log('FIELD IPDATE')
        console.log(...form_data)
        const queryString = new URLSearchParams(form_data).toString()
        console.log(queryString)
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
        //e.preventDefault();
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
        console.log(data)
        $.ajax({
            type: 'post',
            url: '/field/show',
            data: objectToQueryString(data),
            success: function(data) {
                console.log(data)
                if(data['visible_always'] == 0 && !item.closest('li[data-id]').find('.js-editable').data('value')) 
                    item.closest('li[data-id]').addClass('hidden-field');
                else
                    item.closest('li[data-id]').removeClass('hidden-field');
                //$('.side-list__item.active').trigger('click')
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
                //$('.carrier-list__item.active').trigger('click')

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
                    //$('.carrier-list__item.active').trigger('click')

                }
            });
        }
    });

    $("body").on('click', '.js-section-hide', function(e){
        e.preventDefault()
        var item = $(this),
            section = item.data('section');
        var data = {};
        data['section'] = section;
        data['_token'] = $('input[name=_token]').val();
        $.ajax({
            type: 'post',
            url: '/field_sections/hide',
            data: objectToQueryString(data),
            success: function(data) {
                $('.carrier-list__item.active').trigger('click')
            }
        });
    });


    
})

function fieldColor (state) {
    console.log(state)
    if (!state.id) {
        return state.text;
    }
    var $state = $(
        '<span class="bg-status status-'+$(state.element).data('value')+'"></span>'
    );
    return $state;
};