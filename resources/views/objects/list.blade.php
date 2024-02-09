@extends('layouts.main')
@section('title')
    {{ $entity->display_name_plural }}
@endsection
@section('h1')
    <h1 class="my-0 h1">{{ $entity->display_name_plural }}</h1>
    @php
    $browse_admin = $user->isAdmin();
    @endphp

@endsection
@section('date_links')
@endsection
@section('search')
    <div class="ms-2 search d-none d-lg-block position-relative">
        <form class="js-search-form form">
            <div class="position-relative">
                <input name="order" type="text" class="form-control" placeholder="Фильтр + поиск" autocomplete="off" value="{{ $order ?? ''}}">
                <div class="search-results"></div>
            </div>
        </form>
        <div class="js-filter-form filter-form d-none">
            <div class="d-flex">
                <form class="filter-form-fields">
                    <div class="filter-content">
                        <ul class="position-relative row list-unstyled c-list js-sort-form-filter js-filter-fields ui-sortable" data-filter="{{ $active_filter->id }}"> 
                        @foreach($active_filter->fields() as $field)
                            @if($field->type == 'file' || $field->type == 'timestamp')
                                @continue
                            @endif
                            @if($field->type != 'status')
                                @include('fields.filter.'.$field->type, ['field_data' => $field, 'filter' => $active_filter ])
                            @else
                                @include('fields.values.'.$field->type, ['field' => $field, 'filter' => $active_filter])
                            @endif
                        @endforeach
                        </ul>
                        <div>
                            <div class="settings position-relative d-inline-block">
                                <a class="dropdown-toggle link show me-2 fs-14" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
                                    Выбрать поле
                                </a>
                                <div class="d-none">
                                    @if($hidden_fields = $active_filter->hidden_fields())
                                        @foreach($hidden_fields as $field)
                                        <li><a class="dropdown-item js-filter-field-add" href="javascript:;" data-filter="{{ $active_filter->id }}" data-type="{{ $active_filter->data_type }}" data-field="{{ $field->field }}">{{ $field->display_name }}</a></li>
                                        @endforeach
                                    @endif
                                </div>
                                <ul class="dropdown-menu start-0">
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center mt-3 js-search-panel">
                        <button type="submit" class="btn btn-primary me-2">Найти</button>
                        <button type="reset" class="btn btn-secondary">Сбросить</button>
                    </div>
                    <div class="d-flex justify-content-center mt-3 js-filter-save-panel d-none">
                        <button type="submit" class="btn btn-primary me-2 js-save-filter" data-type="{{ $active_filter->data_type }}">Сохранить</button>
                        <button type="reset" class="btn btn-secondary js-reset-filter">Отменить</button>
                    </div>
                </form>
                <div class="filter-form-side" style="flex: 1 1">
                    <div class="panel panel-bold d-flex align-items-center">Сохраненные</div>
                    <div class="filter-list">
                        @foreach($filters as $filter)
                        <div class="js-filter-item filter-item position-relative align-items-center @if($filter->id == $active_filter->id) active @endif" data-id="{{ $filter->id }}">
                            <a href="#">
                                <span class="filter-name">{{ $filter->name }}</span>
                            </a>
                            <a href="#" id="dd{{$filter->id}}" role="button" data-toggle="dropdown" aria-expanded="false">
                                <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="dd{{$filter->id}}">
                                <li><a class="dropdown-item js-filter-sort" data-direction="up" data-id="{{ $filter->id }}"><span>Вверх</span></a></li>
                                <li><a class="dropdown-item js-filter-sort" data-direction="down" data-id="{{ $filter->id }}"><span>Вниз</span></a></li>
                                <li><a class="dropdown-item js-filter-edit" data-id="{{ $filter->id }}"><span>Редактировать</span></a></li>
                                <li><a class="dropdown-item js-delete-filter" data-id="{{ $filter->id }}"><span class="text-danger">Удалить</span></a></li>
                            </ul>
                        </div>
                        @endforeach
                    </div>
                    <a href="javascript:;" class="add-filter js-add-filter" data-type="{{ $active_filter->data_type }}">+ Создать фильтр</a>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-primary ps-2 js-add-object ms-lg-auto">
        <svg class="icon icon-plus-light"><use xlink:href="#icon-plus-light"></use></svg>
        <span class="d-none d-sm-block ms-2">Добавить</span>
    </button>
    <!-- 
    <div class="btn-group ms-lg-auto btn-group-add">
        
        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#">Exel файл</a></li>
            <li><a class="dropdown-item" href="#">YML ссылка</a></li>
            <li><a class="dropdown-item" href="#">API Интеграция</a></li>
        </ul>
    </div> -->
@endsection
@section('subnav')
    @php
        cache()->flush();
    @endphp
@endsection
@section('content')
    {{ csrf_field() }}
    
    <link rel="stylesheet" type="text/css" href="/tenancy/assets/css/dataTables.checkboxes.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.6.1/css/select.dataTables.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/select/1.6.1/js/dataTables.select.min.js"></script>
    <script type="text/javascript" src="/tenancy/assets/js/dataTables.checkboxes.min.js?v=10"></script>
    <script type="text/javascript" src="/tenancy/assets/js/crm.js?v={{ random_int(1,20000) }}"></script>

    <div class="box">
        <div class="box-top border-0">
            <div class="row align-items-center justify-content-between">
               <div class="col-auto">
               </div>
               <div class="col-auto text-right">
                   <div class="d-flex align-items-center justify-content-end">
                       <button class="btn btn-link mr-3 save-state d-none">Сохранить изменения</button>
                       <div class="dropdown">
                            <button class="btn btn-filter dropdown-toggle" type="button" id="dropdownTableFilter1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                               <i class="fas fa-cog"></i>
                            </button>
                            <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions" href="javascript:;" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item " href="javascript:;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Отображение столбцов <i class="fa fa-chevron-right"></i></a>
                                <div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions">
                                    <a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Отображение столбцов</b></a>
                                    
                                    <ul class="table-vis list-unstyled mb-0">
                                        
                                    </ul>
                                </div>
                                <a class="dropdown-item " href="javascript:;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Фиксация столбцов <i class="fa fa-chevron-right"></i></a>
                                <div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions">
                                    <a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Фиксация столбцов</b></a>

                                    <ul class="table-fix list-unstyled mb-0">
                                        
                                    </ul>
                                </div>
                                <a class="dropdown-item " href="javascript:;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">Порядок столбцов <i class="fa fa-chevron-right"></i></a>
                                <div class="dropdown-menu dropdown-menu__actions dropdown-submenu__actions">
                                    <a class="dropdown-item dropdown-back" href="javascript:;"><i class="fa fa-chevron-left"></i><b>Порядок столбцов</b></a>

                                    <ul class="column-table-order list-unstyled mb-0">
                                        
                                    </ul>
                                </div>
                                @if($browse_admin)
                                <a class="dropdown-item js-set-common-settings" data-table="table_page_{{ $model }}" href="javascript:;" aria-expanded="true">Применить настройки для всех</a>
                                @endif
                            </div>
                            
                        </div>
                   </div>
               </div>
           </div>
        </div>
        <div class="box-body">
            <table class="fusion-table" id="table" data-model="{{ $model }}">
                <thead>
                    <tr>
                        <th class="text-center" data-name="checkbox">
                            
                        </th>
                        @php
                        $start_columns = array(
                        );
                        foreach($model_fields as $field) {
                            $start_columns[] = array(
                                'name' => $field->field,
                                'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                
                            );
                        }
                        @endphp
                        @foreach($start_columns as $col)
                        <th class="text-center" data-name="{{ $col['name'] }}">
                            {!! $col['display_name'] !!}
                        </th>
                        @endforeach
                        <th class="text-center" data-name="actions">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($objects as $object)
                    <tr data-id="{{ $object['id'] }}">
                        <td></td>
                        @php
                        $start_columns = array(
                        );
                        foreach($model_fields as $field) {
                            $start_columns[] = array(
                                'name' => $field->field,
                                'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                
                            );
                        }
                        $start_columns[] = array(
                            'name' => 'actions',
                            'display_name' => 'Действия',
                        );
                        @endphp
                        @foreach($start_columns as $col)
                        <td>{!! $object[$col['name']] !!}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="dataTables_wrapper toolbar-table d-flex justify-content-between align-items-center"></div>
        </div>
    </div>
    
    <div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">История изменений</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table bordered" id="history-table">
                <thead>
                    <tr>
                        <th class="text-center">Дата изменения</th>
                        <th class="text-center">Изменение</th>
                        <th class="text-center">Пользователь</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            
          </div>
        </div>
      </div>
    </div>
    <style type="text/css">
        .box, .form-control-status {
            display: none;
        }
        .select2-results__option {
            text-align: left;
        }
        #table_filter {
            margin-top: 0!important;
            margin-bottom: 10px;
            
        }
        
    </style>
    <script>
        Date.prototype.ddmmyyyy = function() {
          var mm = this.getMonth() + 1;
          var dd = this.getDate();

          return [(dd>9 ? '' : '0') + dd,
                  (mm>9 ? '' : '0') + mm,
                  this.getFullYear()
                 ].join('.');
        };

        $(document).ready(function() {
            var table_settings,
                table_columns = [],
                table_columns_v = [],
                table_columns_fix = [],
                run = false;
            $.ajax({
                  "url": "/state_load?user_id={{ Auth::user()->id }}&table=table_page_{{ $model }}",
                  "async": false,
                  "dataType": "json",
                  "success": function (json) {
                    table_settings = json;
                    var columns = table_settings['columns'];
                    
                    $.each(columns, function(index, el) {
                        if(el['name']) {
                            table_columns[el['name']] = el['width'];
                            table_columns_v.push(el['name']);
                            if(el['is_sticky'] == 'true')
                                table_columns_fix.push(el['name']);
                        }
                    });
                  }
                });
            var table = $('#table').DataTable({
                dom: 'Rfrtilp',
                paging: true,
                pagingType: 'numbers',
                searching: false,
                stateSave: true,
                stateDuration: 1,
                @if($table_settings)
                colReorder: {
                    order: {!! json_encode(\App\Models\Settings::fixTableOrder(count($model_fields)+1, $table_settings['ColReorder'])) !!}
                },
                order: [[{{ $table_settings['order'][0][0] }}, '{{ $table_settings['order'][0][1] }}']],
                @else
                colReorder: true,
                @endif
                columnDefs: [
                    {
                        targets: 0,
                        checkboxes: {
                           selectRow: true
                        }
                    }
                ],
                selected: true,
                select: {
                  style: 'multi'
                },
                autoWidth: false,
                lengthMenu: [ 25, 50, 75, 100 ],
                "stateSaveParams": function (settings, data) {
                    var columns = data['columns'], k;
                    $.each(columns, function(index, el) {
                        k = index+1;
                        data['columns'][index]['width'] = $('#table th:nth-child('+k+')').width();
                        data['columns'][index]['visible'] = data['columns'][index]['visible'];
                        data['columns'][index]['name'] = $('#table th:nth-child('+k+')').data('name');
                        data['columns'][index]['is_sticky'] = $('#table th:nth-child('+k+')').hasClass('sticky-head');
                    });
                },
                "stateSaveCallback": function (settings, data) {
                    if (run == true) {
                        $.ajax( {
                          "url": "/state_save",
                          "data": {'data': data, 'table': 'table_page_{{ $model }}','_token': $('input[name=_token]').val()},
                          "dataType": "json",
                          "type": "POST",
                          "success": function (res) {
                            console.log(res)
                          }
                        } );
                    };
                    run = false;
                },
                "stateLoadCallback": function (settings) {
                    if(table_settings) {
                        var o = table_settings;
                        return o;
                    } else 
                        return settings;
                    
                },
                
                columns: [
                    { "name": "checkbox", "data": "checkbox", "width": table_columns['checkbox']+'px' },
                    @foreach($model_fields as $field)
                    { "name": "{{ $field->field }}", "data": "{{ $field->field }}", "width": table_columns['{{ $field->field }}']+'px' },
                    @endforeach
                    { "name": "actions", "data": "actions", "width": table_columns['actions']+'px' },
                ],
                createdRow: function (row, data, dataIndex, cells) {
                    $(row).attr('data-id', $(row).find('[data-field="number"]').data('id'));
                },
                language: {
                    url: '/tenancy/assets/lang/Russian_orders.json'
                }
            });
            table.on('init', function(){
                $('[name="table_length"]').select2();
                $('.dataTables_info').appendTo('.toolbar-table');
                $('.dataTables_paginate').appendTo('.toolbar-table');
                $('.dataTables_length').appendTo('.toolbar-table');
                
                

                var colname,
                    header_text,
                    label_col,
                    input_col,
                    div_col;
                table.columns().every( function () {
                    colname = $(this.header()).data('name');
                    header_text = this.header().textContent;
                    if(colname == 'actions')
                        header_text = 'Действия';
                    if(colname !== undefined) {
                        input_col = $('<input type="checkbox">').attr('id', 'customCheck'+colname).attr('value', colname).addClass('custom-control-input column-toggle');
                        label_col = $('<label></label>').attr('for', 'customCheck'+colname).addClass('custom-control-label').text(header_text);
                        div_col = $('<div></div>').addClass('custom-control custom-checkbox');
                        div_col.append(input_col).append(label_col);
                        $('.table-vis').append($('<li></li>').append(div_col));
                        input_col = $('<input type="checkbox">').attr('id', 'customCheck-fix-'+colname).attr('value', colname).addClass('custom-control-input column-toggle-fix input-table-fix');
                        label_col = $('<label></label>').attr('for', 'customCheck-fix-'+colname).addClass('custom-control-label').text(header_text);
                        div_col = $('<div></div>').addClass('custom-control custom-checkbox');
                        div_col.append(input_col).append(label_col);
                        $('.table-fix').append($('<li></li>').append(div_col));

                        div_col = $('<div class="column-sort align-items-center mb-2" data-column="'+($(this.header()).data('column-index'))+'"><span class="me-2 btn-drag start-0 top-0 ui-sortable-handle" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>'+header_text+'</div>');
                        $('.column-table-order').append($('<li></li>').append(div_col));
                        $(".column-table-order").sortable({
                          group: 'no-drop',
                          handle: '.btn-drag',
                          update: function( event, ui ) {
                            var items = [];
                            var $item = ui.item;
                            $('.column-table-order .column-sort').each(function(index, el) {
                                items.push($(this).data('column'));
                                $(this).data('column', index);
                            });
                            table.colReorder.order(items);
                            select2init();
                          },

                        });
                       
                    }
                } );
                $('body').on('click', '.column-toggle', function (e) {
                    var index = $('.column-toggle[value="'+$(this).val()+'"]').closest('li').index();
                    if($(this).prop('checked')) {
                        $('.column-toggle-fix[value="'+$(this).val()+'"]').closest('li').show();
                        $('.column-table-order .column-sort[data-column="'+index+'"]').removeClass('hide');
                    } else {
                        $('.column-toggle-fix[value="'+$(this).val()+'"]').closest('li').hide();
                        $('.column-table-order .column-sort[data-column="'+index+'"]').addClass('hide');
                    }
                    $('.save-state').removeClass('d-none');
                    var column = table.column($(this).val()+':name');
                    column.visible(!column.visible());
                });
                $('.column-toggle').each(function(index, el) {
                    if((table_columns_v.includes($(this).attr('value')) || table_columns_v.length == 0))
                        $('.column-toggle').eq(index).prop('checked', true);
                    else {
                        var column = table.column($(this).attr('value')+':name');
                        column.visible(false);
                        $('.column-table-order .column-sort[data-column="'+index+'"]').addClass('hide');
                    }
                })
                $('body').on('click', '.column-toggle-fix', function (e) {
                    var width = 0, table = $(this).closest('.box').find('.dataTables_wrapper table');
                    $('.save-state').removeClass('d-none');
                    if($(this).prop('checked')) {
                        table.find('th[data-name="'+$(this).val()+'"]').addClass('sticky-head');
                        table.find('td div[data-field="'+$(this).val()+'"]').closest('td').addClass('sticky-td');
                        table.find('th.sticky-head').each(function(i, elem){
                            $(this).css({'left': width+'px'});
                            var indx = $(this).index();
                            table.find('tr').each(function(j, tr){
                                $(tr).find('td:eq('+indx+')').css({'left': width+'px'});
                            });
                            width = width + $(this).outerWidth();
                        });
                        table.find('tr').each(function(i, tr){
                            width = 0;
                            $(tr).find('td.sticky-td').each(function(j, td){
                                //$(td).css({'left': width+'px'});
                                width = width + $(td).outerWidth();
                            })
                        });
                    } else {
                        table.find('th[data-name="'+$(this).val()+'"]').removeClass('sticky-head');
                        table.find('th[data-name="'+$(this).val()+'"]').css({'left': 'auto'});
                        table.find('td div[data-field="'+$(this).val()+'"]').closest('td').removeClass('sticky-td');
                        table.find('td div[data-field="'+$(this).val()+'"]').closest('td').css({'left': 'auto'});
                        table.find('th.sticky-head').each(function(i, elem){
                            $(this).css({'left': width+'px'});
                            var indx = $(this).index();
                            table.find('tr').each(function(j, tr){
                                $(tr).find('td:eq('+indx+')').css({'left': width+'px'});
                            });
                            width = width + $(this).outerWidth();
                        });
                        table.find('tr').each(function(i, tr){
                            width = 0;
                            $(tr).find('td.sticky-td').each(function(j, td){
                                width = width + $(td).outerWidth();
                            })
                        });
                    }
                    
                    
                });
                $('.column-toggle-fix').each(function(index, el) {
                    if(!table_columns_v.includes($(this).attr('value')) && table_columns_v.length != 0) {
                        $('.column-toggle-fix').eq(index).closest('li').hide();
                    }
                    if((table_columns_fix.includes($(this).attr('value')))) {
                        $('.column-toggle-fix').eq(index).trigger('click');
                    }
                    $('.save-state').addClass('d-none');
                })
                
            })

            var run_reorder = true;
            table.on('column-reorder', function(e, diff, edit) {
                if(!run_reorder) {
                    $('.save-state').removeClass('d-none');
                }
            });
            table.on('column-resize.dt.mouseup', function(event, oSettings) {
                if(!run_reorder) {
                    $('.save-state').removeClass('d-none');
                }
            });

            setTimeout(function(){run_reorder = false;select2init();}, 1000);

            $('.save-state').on('click', function(){
                run = true;
                table.state.save(run);
                run = false;
                $('.save-state').addClass('d-none');
            });
            $("body").on('dblclick', 'thead tr th', function(){
                var column = table.column($(this).data('name')+':name').index();
                           
                if (!$(this).hasClass('sorting_asc'))
                    table.order([column, 'asc']).draw();
                else
                    table.order([column, 'desc']).draw();
            });

            table.on('draw',function(){
                @if(isset($_GET['id']))
                $('#table_filter input').val('{{ request()->id }}').trigger('keyup');
                @endif
                var th_width = 0, table = $('.dataTables_wrapper table');
                $('.column-toggle-fix:checked').each(function(index, el) {
                    var width = 0;
                    table.find('td div[data-field="'+$(this).val()+'"]').closest('td').addClass('sticky-td');
                    
                });
                
                table.find('th.sticky-head').each(function(i, elem){
                    $(this).css({'left': th_width+'px'});
                    var indx = $(this).index();
                    table.find('tr').each(function(j, tr){
                        $(tr).find('td:eq('+indx+')').css({'left': th_width+'px'});
                    });
                    th_width = th_width + $(this).outerWidth();
                });
                $('.box').show();
                ddajaxsidepanel.init({
                    targetselector: '[rel="ajaxpanel"]',
                    ajaxloadgif: '/img/squareloading.gif', //full path to "loading" gif relative to document. When in doubt use absolute URL to image.
                    fx: {dur:500, easing: 'easeInQuad'}, // dur: duration of slide effect (milliseconds), easing: 'ease_in_type_string'
                    openamount:'80%', // Width of panel when fully opened (Percentage value relative to page, or pixel value
                    openamount_minthreshold:'400px' //Minimum required width of panel (when fully opened)  before panel is shown. This prevents panel from being shown on small screens or devices.
                })
            });
            $('body').on('click', '.js-delete-model', function(e){
                e.preventDefault();
                var result = confirm('Уверены, что хотите удалить элемент?');
                if(result) {
                    var model = $(this).data('model');
                    var btn = $(this);
                    $.ajax({
                        type: 'post',
                        url: '/objects/'+model+'/destroy/'+btn.data('id'),
                        data: {
                            'id': btn.data('id'),
                            '_token': $('input[name=_token]').val(),
                            '_method': 'DELETE'
                        },
                        success: function(data) {
                            table.row(btn.closest("tr")).remove().draw();
                        }
                    });
                }
                
            });
            $('.js-add-object').click(function(e) {
                e.preventDefault();
                e.stopPropagation();

                $.ajax({
                    type: 'post',
                    url: '/objects/{{ $model }}/store',
                    async: false,
                    data: {
                        '_token': $('input[name=_token]').val(),
                    },
                    success: function(res) {
                        console.log('res')
                        console.log(res)
                        ddajaxsidepanel.showhidepanel('/objects/{{ $model }}/show/'+res.id+'?ajax=y&create=Y', 'show', 'iframe');
                        // var old_href = $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href');
                        // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href', '/orders/edit/'+res.id+'?ajax=y&create=Y');
                        // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').trigger('click');
                        // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').trigger('click');
                        // $('#orders-table tbody tr:last-child').find('[rel="ajaxpanel"]').attr('href', old_href);

                        // table.ajax.reload(function(){
                        //    select2init();
                        // });
                        
                    }
                });

            });
        });
    </script>
@endsection