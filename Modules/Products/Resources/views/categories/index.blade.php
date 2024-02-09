@extends('layouts.main')
@section('title')
    Категории
@endsection
@section('h1')
    <h1 class="my-0 h1">Категории</h1>
    @php
    $browse_admin = $user->isAdmin();
    @endphp
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
                                <ul class="dropdown-menu start-0">
                                    @if($hidden_fields = $active_filter->hidden_fields())
                                        @foreach($hidden_fields as $field)
                                        <li><a class="dropdown-item js-filter-field-add" href="javascript:;" data-filter="{{ $active_filter->id }}" data-type="{{ $active_filter->data_type }}" data-field="{{ $field->field }}">{{ $field->display_name }}</a></li>
                                        @endforeach
                                    @endif
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

@section('content')
    <script type="text/javascript" src="{{ asset('js/crm.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <div class="t-body" data-model="categories">
        <div class="row">
            <div class="col-lg-3 border-end" style="border-right: none!important;">
                <div class="box h-100" style="overflow: visible;">
                    <div class="c-top px-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Каталог</h6>
                        <a href="#addCategory" class="link" data-fancybox="" data-touch="false">Создать</a>
                    </div>
                    <div class="c-body p-0">
                        <ul class="storages-list c-drag-list list-unstyled mb-0 ui-sortable">
                            <li>
                                <div class="position-relative">
                                    <a href="{{ route('products.categories') }}">
                                        Все категории
                                    </a>
                                </div>
                            </li>
                            @foreach($categories as $item)
                                <li>
                                    <div class="d-flex align-items-center position-relative">
                                        <a href="{{ route('categories.show', $item->id) }}">
                                            @if($item->children->count())
                                            <button class="treeview-toggler" data-toggle="collapse" data-target=".list-{{ $item->id }}" aria-expanded="false" ><i class="fa fa-caret-down"></i></button>
                                            @else
                                            <button class="treeview-toggler" ></button>
                                            @endif
                                            {{ $item->name }}
                                            <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                                <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Изменить категорию</a></li>
                                                <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="categories" href="#"><span class="text-danger">Удалить</span></a></li>
                                            </ul>
                                        </a>
                                    </div>
                                    @if($item->children->count())
                                    <ul class="collapse list-{{ $item->id }}">
                                        @foreach($item->children as $child)
                                        <li>
                                            <div class="d-flex align-items-center position-relative">
                                                
                                                <a class="pl-25" href="{{ route('categories.show', $child->id) }}">
                                                    @if($child->children->count())
                                                    <button class="treeview-toggler" data-toggle="collapse" data-target=".list-{{ $child->id }}" aria-expanded="false" ><i class="fa fa-caret-down"></i></button>
                                                    @else
                                                    <button class="treeview-toggler" ></button>
                                                    @endif
                                                    {{ $child->name }}
                                                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#">Изменить категорию</a></li>
                                                        <li><a class="dropdown-item js-delete-model" data-id="{{ $child->id }}" data-model="categories" href="#"><span class="text-danger">Удалить</span></a></li>
                                                    </ul>
                                                </a>
                                            </div>
                                            @if($child->children->count())
                                            <ul class="collapse list-{{ $child->id }}">
                                                @foreach($child->children as $subchild)
                                                <li>
                                                    <div class="position-relative">
                                                        
                                                        <a class="pl-35" href="{{ route('categories.show', $subchild->id) }}">
                                                            <button class="treeview-toggler" aria-expanded="false" ></button>
                                                            {{ $subchild->name }}
                                                        </a>
                                                    </div>
                                                    
                                                </li>
                                                @endforeach
                                            </ul>
                                            @endif
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                    
                                </li>
                                
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="box h-100" >
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
            </div>
        </div>
    </div>
    <div id="addCategory" class="fancy-modal">  
        <form class="form px-5">
            {{ csrf_field() }}
            <h5 class="section-title text-center mb-4">
                Добавить категорию
            </h5>
            
            <div class="mb-2">
                <div class="position-relative mb-3">
                    <label for="#" class="label text-dark mb-1">
                        Название
                    </label>
                    <input type="text" name="name" value="" class="form-control">
                </div>
            </div>
            <div class="mb-2">
                <div class="position-relative mb-3">
                    <label for="#" class="label text-dark mb-1">
                        Категория
                    </label>
                    <ul class="list-unstyled">
                        <li class="col-lg-12">
                            <div class="position-relative">
                                <select name="category_id" class="js-select-tree">
                                    <option value="0" data-parent="">Не выбрано</option>
                                    @foreach($categories as $item)
                                    <option value="{{ $item->id }}" data-parent="">{{ $item->name }}</option>
                                        @if($item->children->count())
                                            @foreach($item->children as $child)
                                            <option value="{{ $child->id }}" data-parent="{{ $item->id }}">{{ $child->name }}</option>
                                            @if($child->children->count())
                                                @foreach($child->children as $subchild)
                                                <option value="{{ $subchild->id }}" data-parent="{{ $child->id }}">{{ $subchild->name }}</option>
                                                @endforeach
                                            @endif
                                            @endforeach
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary rounded-1 js-add-model" data-model="categories" data-url-template="/categories/">Сохранить</button>
            </div>
        </form>
    </div>
    <script>
        $(document).ready(function(){
            if($('.js-select-tree').length){
                $('.js-select-tree').each(function(){
                    var $this = $(this),
                        $wrap = $this.closest('.position-relative');

                    if($this.find('option').length < 10) {
                        $this.select2tree({
                            width: 'auto',
                            dropdownParent: $wrap,
                            minimumResultsForSearch: -1
                        });
                    } else {
                        $this.select2tree({
                            width: 'auto',
                            dropdownParent: $wrap
                        });
                    }
                })
                
            }

            $(".js-select-tree").on("select2:open", function(e) {
              console.log("select2:open", e);
            });
            $(".js-select-tree").on("select2:close", function(e) {
              console.log("select2:close", e);
            });
            $(".js-select-tree").on("select2:select", function(e) {
              console.log("select2:select", e);
            });
            $(".js-select-tree").on("select2:unselect", function(e) {
              console.log("select2:unselect", e);
            });
        });
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
@endsection