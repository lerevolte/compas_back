@extends('layouts.main')
@section('title')
Настройка ролей
@endsection
@section('h1')
    <h1 class="my-0 h1">Настройка ролей</h1>
@endsection
@section('subnav')
    <div class="t-nav mt-4">
        <div class="btn-group mb-3" role="group" aria-label="Nav">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Пользователи</a>
            <a href="{{ route('settings.roles') }}" class="btn btn-outline-secondary active">Настройка ролей</a>
            @if(request()->user()->hasRole('admin'))
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Административные настройки</a>
            @endif
        </div>
    </div>
@endsection
@section('content')
    @php
    $items = \App\Models\Role::orderBy('sort')->get();
    @endphp
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>

    <div class="rounded-1 bg-white border t">
        <div class="t-body " data-model="roles">
            <div class="row g-0">
                <div class="col-lg-3 border-end">
                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Роли</h6>
                        <a href="/admin/roles/create" class="link" data-touch="false">Добавить</a>
                    </div>
                    <div class="c-body p-0">
                        <ul class="c-drag-list list-unstyled mb-0 js-sort">
                            @if(!isset($role->id))
                                <li class="side-list__item d-flex position-relative active" data-url-template="/roles/edit/">
                                    <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                    <div class="btn btn-light w-100">
                                        <span class="text-gray">(не заполнено)</span>
                                        <a class="dropdown-toggle" href="#" id="dd" role="button" data-toggle="dropdown" aria-expanded="false">
                                            <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                        </a>
                                    </div>                                            
                                </li>
                            @endif
                            @foreach($items as $item)
                            <li class="side-list__item d-flex position-relative @if(isset($role->id) && $role->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="roles" data-href="/roles/edit/{{$item->id}}" data-url-template="/roles/edit/">
                                <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                <div class="btn btn-light w-100">
                                    <span @if(!$item->display_name) class="text-gray" @endif >{{ $item->display_name ?? '(не заполнено)' }}</span>
                                    <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                        <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="roles" href="#"><span class="text-danger">Удалить</span></a></li>
                                    </ul>
                                </div>                                            
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    <div class="c-body ">
                        <form class="js-change-role form-edit-add" role="form"
                              action="@if(isset($role->id)){{ route('roles.update') }}@else{{ route('roles.store') }}@endif"
                              method="POST" enctype="multipart/form-data" autocomplete="off">
                              <input type="hidden" name="id" value="{{ $role->id }}">
                            <!-- CSRF TOKEN -->
                            {{ csrf_field() }}

                            <div class="panel-body">
                                <ul class="position-relative row list-unstyled c-list mb-2 pt-4 px-4">
                                    <!-- <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Код
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <input name="name" type="text" class="form-control" value="{{ $role->name }}">
                                                </div>                       
                                            </div>
                                        </div>
                                    </li> -->
                                    <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Название
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <input name="display_name" type="text" class="form-control" value="{{ $role->display_name }}">
                                                </div>                       
                                            </div>
                                        </div>
                                    </li>
                                    <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Является администратором
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <select name="is_admin" class="js-select">
                                                        <option value="0" @if(!$role->is_admin) selected @endif>Нет</option>
                                                        <option value="1" @if($role->is_admin) selected @endif>Да</option>
                                                    </select>
                                                </div>                       
                                            </div>
                                        </div>
                                    </li>
                                
                                </ul>
                                <h3 class="px-4 pb-3">Настройка прав категорий</h3>
                                <table class="table table-bordered table-hover mb-0 table-bordered-se ">
                                    <thead>
                                        <tr>
                                            <th>Сущность</th>
                                            <th>Чтение</th>
                                            <th>Добавление</th>
                                            <th>Изменение</th>
                                            <th>Удаление</th>
                                            <th>Экспорт</th>
                                            <th>Импорт</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $perm_heads = array(
                                            'read',
                                            'add',
                                            'write',
                                            'delete',
                                            'export',
                                            'import'
                                        );
                                        @endphp
                                        <!-- <tr class="parent">
                                            <td class="text-center">
                                                <span class="js-toggle-perm-childs" data-name="fields_logistic"><i class="fa fa-plus"></i></span>
                                                <b>Логистика</b>
                                            </td>
                                            <td class="text-center">
                                                <span class="js-perm-box">{{ \App\Models\Permission::getPermName($perm_table_parents['fields_logistic']['values']['read']) }}</span>
                                                <span style="display:none">
                                                    <select class="js-perm-select">
                                                        <option value="N" @if($perm_table_parents['fields_logistic']['values']['write'] == 'N') selected="selected" @endif>Нет доступа</option>
                                                        <option value="A">Свои</option>
                                                        <option value="O" @if($perm_table_parents['fields_logistic']['values']['read'] == 'O') selected="selected" @endif>Eсть доступ</option>
                                                    </select>
                                                </span>
                                            </td>
                                            <td></td>
                                            <td class="text-center">
                                                <span class="js-perm-box">Нет доступа</span>
                                                <span style="display:none">
                                                    <select class="js-perm-select">
                                                        <option value="N" @if($perm_table_parents['fields_logistic']['values']['write'] == 'N') selected="selected" @endif>{{ \App\Models\Permission::getPermName($perm_table_parents['fields_logistic']['values']['write']) }}</option>
                                                        <option value="A">Свои</option>
                                                        <option value="O" @if($perm_table_parents['fields_logistic']['values']['write'] == 'O') selected="selected" @endif>Eсть доступ</option>
                                                    </select>
                                                </span>
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr> -->
                                        @foreach($perm_table_fields as $table_name => $perm_row)
                                            <!-- <tr class="parent child-perm" data-parent="fields_logistic">
                                                <td class="text-center">
                                                    <span class="js-toggle-perm-childs" data-name="fields_{{ $table_name }}"><i class="fa fa-plus"></i></span>
                                                    <b>{{ $perm_row['name'] }}</b>
                                                </td>
                                                <td class="text-center">
                                                    <span class="js-perm-box">{{ \App\Models\Permission::getPermName($perm_table_parents['fields_'.$table_name]['values']['read']) }}</span>
                                                    <span style="display:none">
                                                        <select class="js-perm-select">
                                                            <option value="N" @if($perm_table_parents['fields_'.$table_name]['values']['read'] == 'N') selected="selected" @endif>Нет доступа</option>
                                                            <option value="A">Свои</option>
                                                            <option value="O" @if($perm_table_parents['fields_'.$table_name]['values']['read'] == 'O') selected="selected" @endif>Eсть доступ</option>
                                                        </select>
                                                    </span>
                                                </td>
                                                <td></td>
                                                <td class="text-center">
                                                    <span class="js-perm-box">{{ \App\Models\Permission::getPermName($perm_table_parents['fields_'.$table_name]['values']['write']) }}</span>
                                                    <span style="display:none">
                                                        <select class="js-perm-select">
                                                            <option value="N" @if($perm_table_parents['fields_'.$table_name]['values']['write'] == 'N') selected="selected" @endif>Нет доступа</option>
                                                            <option value="A">Свои</option>
                                                            <option value="O" @if($perm_table_parents['fields_'.$table_name]['values']['write'] == 'O') selected="selected" @endif>Eсть доступ</option>
                                                        </select>
                                                    </span>
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr> -->
                                            @foreach($perm_row['fields'] as $field_code => $field_perm)
                                            <!-- <tr class="child-perm" data-parent="fields_{{ $table_name }}">
                                                <td class="text-center"><b>{{ $field_perm['name'] }}</b></td>
                                                @foreach($field_perm['values'] as $type => $perm)
                                                    <td class="text-center">
                                                        <span class="js-perm-box">{{ \App\Models\Permission::getPermName($perm) }}</span>
                                                        <span style="display:none">
                                                            <select class="js-perm-select" name="perms_field[{{ $table_name }}][fields][{{ $field_code }}][{{ $type }}]">
                                                                <option value="N" @if(!$perm || $perm == 'N') selected="selected" @endif>Нет доступа</option>
                                                                <option value="A"  @if($perm == 'A') selected="selected" @endif>Свои</option>
                                                                <option value="O" @if($perm == 'O') selected="selected" @endif>Eсть доступ</option>
                                                            </select>
                                                        </span>
                                                    </td>
                                                    @if($type == 'read')
                                                    <td></td>
                                                    @endif
                                                @endforeach
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr> -->
                                            
                                            @endforeach
                                        @endforeach
                                        @foreach($perm_table as $table_name => $perm_row)
                                     
                                            @if(isset($perm_row['name']))
                                            <tr @if(isset($perm_row['childs'])) class="parent" @endif>
                                                <td class="ps-2">
                                                    @if(isset($perm_row['childs']))
                                                    <span class="js-toggle-perm-childs" data-name="{{ $table_name }}"><i class="fa fa-plus"></i></span>
                                                    @endif
                                                    <b>{{ $perm_row['name'] }}</b>
                                                </td>
                                                @foreach($perm_row['values'] as $type => $perm)
                                                <td class="text-center">
                                                    @php
                                                    $perm_name = \App\Models\Permission::getPermName($perm);
                                                    @endphp
                                                    <span class="js-perm-box {{ $perm_name == 'Нет доступа' ? 'text-gray-label':''}}">{{ $perm_name }}</span>
                                                    <span style="display:none">
                                                        <select class="js-perm-select" name="perms[{{ $table_name }}][values][{{ $type }}]">
                                                            <option value="N" @if(!$perm || $perm == 'N') selected="selected" @endif>Нет доступа</option>
                                                            <option value="A"  @if($perm == 'A') selected="selected" @endif>Свои</option>
                                                            <option value="O" @if($perm == 'O') selected="selected" @endif>Eсть доступ</option>
                                                        </select>
                                                    </span>
                                                </td>
                                                @endforeach
                                                @if(count($perm_row['values']) < 7)
                                                    @for($i=6;$i>count($perm_row['values']);$i--)
                                                    <td></td>
                                                    @endfor
                                                @endif
                                            </tr>
                                            @endif
                                            @if(isset($perm_row['childs']))
                                                @foreach($perm_row['childs'] as $child_name => $child)
                                                <tr class="child-perm" data-parent="{{ $table_name }}">
                                                    <td class="ps-4"><b>{{ $child['name'] }}</b></td>
                                                    @foreach($perm_heads as $type)
                                                        @if(isset($child['values'][$type]))
                                                        <td class="text-center">
                                                            @php
                                                            $perm_name = \App\Models\Permission::getPermName($child['values'][$type]);
                                                            @endphp
                                                            <span class="js-perm-box {{ $perm_name == 'Нет доступа' ? 'text-gray-label':''}}">{{ $perm_name }}</span>
                                                            <span style="display:none">
                                                                <select class="js-perm-select" name="perms[{{ $table_name }}][childs][{{ $child_name }}][{{ $type }}]">
                                                                    <option value="N" @if(!$child['values'][$type] || $child['values'][$type] == 'N') selected="selected" @endif>Нет доступа</option>
                                                                    <option value="A"  @if($child['values'][$type] == 'A') selected="selected" @endif>Свои</option>
                                                                    <option value="O" @if($child['values'][$type] == 'O') selected="selected" @endif>Eсть доступ</option>
                                                                </select>
                                                            </span>
                                                        </td>
                                                        @else
                                                        <td></td>
                                                        @endif
                                                    @endforeach
                                                    
                                                </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-none">
                                    <label for="permission">{{ __('voyager::generic.permissions') }}</label><br>
                                    <a href="#" class="permission-select-all">{{ __('voyager::generic.select_all') }}</a> / <a href="#"  class="permission-deselect-all">{{ __('voyager::generic.deselect_all') }}</a>
                                    <ul class="permissions checkbox">
                                        <?php
                                            $role_permissions = (isset($dataTypeContent)) ? $dataTypeContent->permissions->pluck('key')->toArray() : [];
                                        ?>
                                        @foreach(Voyager::model('Permission')->all()->groupBy('table_name') as $table => $permission)
                                            @php
                                                if($table)
                                                    continue;
                                            @endphp
                                            <li>
                                                <input type="checkbox" id="{{$table}}" class="permission-group">
                                                <label for="{{$table}}"><strong>{{\Illuminate\Support\Str::title(str_replace('_',' ', $table))}}</strong></label>
                                                <ul>
                                                    @foreach($permission as $perm)
                                                        @php
                                                            if(!$perm->visible)
                                                                continue;
                                                        @endphp
                                                        <li>
                                                            <input type="checkbox" id="permission-{{$perm->id}}" name="permissions[{{$perm->id}}]" class="the-permission" value="{{$perm->id}}" @if(in_array($perm->key, $role_permissions)) checked @endif>
                                                            <label for="permission-{{$perm->id}}">{{\Illuminate\Support\Str::title($perm->name)}}</label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                            </div><!-- panel-body -->
                            <!-- <div class="panel-footer mt-2">
                                <button type="submit" class="btn btn-primary">{{ __('voyager::generic.submit') }}</button>
                            </div> -->
                            
                        </form>

                    </div>
                </div>
            </div>

        </div>

    </div>
    <div class="js-save-panel-roles save-panel" style="display: none;left:0">
        <button type="submit" class="js-submit-roles blue-btn">Сохранить</button>
        <button class="gray-btn js-reset-fields-roles">Отменить</button>
    </div>
    <script>
        $(document).ready(function () {
            

            // $('.permission-group').on('change', function(){
            //     $(this).siblings('ul').find("input[type='checkbox']").prop('checked', this.checked);
            // });

            // $('.permission-select-all').on('click', function(){
            //     $('ul.permissions').find("input[type='checkbox']").prop('checked', true);
            //     return false;
            // });

            // $('.permission-deselect-all').on('click', function(){
            //     $('ul.permissions').find("input[type='checkbox']").prop('checked', false);
            //     return false;
            // });

            // function parentChecked(){
            //     $('.permission-group').each(function(){
            //         var allChecked = true;
            //         $(this).siblings('ul').find("input[type='checkbox']").each(function(){
            //             if(!this.checked) allChecked = false;
            //         });
            //         $(this).prop('checked', allChecked);
            //     });
            // }

            // parentChecked();

            // $('.the-permission').on('change', function(){
            //     parentChecked();
            // });
            $('body').on('click', '.js-perm-box', function(){
                $('.js-perm-box').each(function(){
                    $(this).show();
                    $(this).next('span').hide();
                })
                $(this).hide();
                $(this).next('span').show();
            })
            $('body').on('click', '.js-toggle-perm-childs', function() {
                $(this).find('i').toggleClass('fa-plus fa-minus');
                $('tr[data-parent="'+$(this).data('name')+'"]').toggle();
                
            })
            $(window).on('click', function(e){
                if(!e.target.closest('select') && !e.target.closest('.js-perm-box')) {
                    $('.js-perm-box').each(function(){
                        $(this).show();
                        $(this).next('span').hide();
                    })
                }
            });
            $('body').on('change', '.js-perm-select', function(){
                var tr = $(this).closest('tr'),
                    index = $(this).closest('td').index(),
                    val = $(this).val(),
                    val_text = $(this).find('option:selected').text();
                $(this).closest('span').prev('.js-perm-box').text(val_text);
                if(tr.hasClass('parent')) {
                    console.log('parent')
                    $('tr[data-parent="'+tr.find('.js-toggle-perm-childs').data('name')+'"]').each(function(){
                        $(this).find('td:eq('+index+')').find('select option[value="'+val+'"]').prop('selected', true).trigger('change');
                    });
                }
                $('.js-save-panel-roles').show();
            });
            $('body').on('keyup', 'input', function(){
                $('.js-save-panel-roles').show();
            });
            $('body').on('change', 'select', function(){
                $('.js-save-panel-roles').show();
            });
            
            $('body').on('click', '.js-submit-roles', function(){
                $('.js-change-role').submit();
                $(this).addClass('disabled');
            });

            $('body').on('click', '.js-reset-fields-roles', function(){
                $('.js-save-panel-roles').hide();
                updateContent();
            });
            
        });
    </script>
    <style type="text/css">
        span.js-perm-box {
            border-bottom: 1px dashed #999;
            color: #333;
            cursor: pointer;
            display: inline-block;
            padding: 0 1px;
        }
        .child-perm {
            display: none;
        }
    </style>

@endsection
    