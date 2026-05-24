@extends('layouts.main')
@section('title')
Пользователи
@endsection
@section('h1')
    <h1 class="my-0 h1">Пользователи</h1>
@endsection
@section('subnav')
    <div class="t-nav mt-4">
        <div class="btn-group mb-3" role="group" aria-label="Nav">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary active">Пользователи</a>
            <a href="{{ route('settings.roles') }}" class="btn btn-outline-secondary">Настройка ролей</a>
            @if(request()->user()->hasRole('admin'))
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Административные настройки</a>
            @endif
            <a href="{{ route('balance') }}" class="btn btn-outline-secondary">Тариф</a>
        </div>
    </div>
@endsection
@section('content')
    @php
    $users = \App\Models\User::with('role')->orderBy('sort')->get();
    $trashed_items = \App\Models\User::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
    @endphp
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>

    <div class="rounded-1 bg-white border t">
        <div class="t-body " data-model="users">
            <div class="row g-0">
                <div class="col-lg-3 border-end">
                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Пользователи</h6>
                        <a href="{{ route('users.create') }}" class="link" data-touch="false">Добавить</a>
                    </div>
                    <div class="c-body p-0">
                        <ul class="storages-list c-drag-list list-unstyled mb-0 ui-sortable">
                        @foreach($roles as $item)
                            @if($item->users->count())
                            <li>
                                <div class="position-relative @if($dataTypeContent->hasRole($item->name)) active @endif" data-toggle="collapse" data-target=".list-{{ $item->id }}" @if($dataTypeContent->hasRole($item->name)) aria-expanded="true" @else aria-expanded="false" @endif>
                                    <a href="javascript:;" >
                                        @if($item->users->count())
                                        <button class="treeview-toggler" ><i class="fa fa-caret-down"></i></button>
                                        @else
                                        <button class="treeview-toggler" ></button>
                                        @endif
                                        {{ $item->display_name }}
                                    </a>
                                </div>
                                
                                <ul class="collapse list-{{ $item->id }} @if($dataTypeContent->hasRole($item->name)) show @endif">
                                    @foreach($item->users as $child)
                                    <li >
                                        <div class="position-relative align-items-center @if(isset($dataTypeContent->id) && $dataTypeContent->id == $child->id) active @endif">
                                            
                                            <a class="pl-25" href="{{ route('users.edit', $child->id) }}">
                                                <button class="treeview-toggler" ></button>
                                                <span @if(!$child->name) class="text-gray" @endif >{{ $child->name.' '.$child->last_name ?? '(не заполнено)' }}</span>
                                                <a class="dropdown-toggle" href="#" id="dd{{ $child->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                                </a>
                                                <ul class="dropdown-menu" aria-labelledby="dd{{ $child->id }}">
                                                    <li><a class="dropdown-item js-history" data-id="{{ $child->id }}" data-entity="users" href="#">История</a></li>
                                                    <li><a class="dropdown-item js-delete-model" data-id="{{ $child->id }}" data-model="users" href="#"><span class="text-danger">Удалить</span></a></li>
                                                </ul>
                                                
                                            </a>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                
                            </li>
                            @endif
                        @endforeach
                            @if($users_wo_role->count())
                                <li>
                                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                                        <h6 class="h6 m-0">Без роли</h6>
                                    </div>
                                </li>
                            @endif
                            @foreach($users_wo_role as $user)
                                <li>
                                    <div class="position-relative align-items-center @if(isset($dataTypeContent->id) && $dataTypeContent->id == $user->id) active @endif">
                                        <a href="{{ route('users.edit', $user->id) }}" >
                                            <button class="treeview-toggler" ></button>
                                            <span @if(!$user->name) class="text-gray" @endif >{{ $user->name.' '.$user->last_name ?? '(не заполнено)' }}</span>
                                            <a class="dropdown-toggle" href="#" id="dd{{ $user->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                                <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dd{{ $user->id }}">
                                                <li><a class="dropdown-item js-delete-model" data-id="{{ $user->id }}" data-model="users" href="#"><span class="text-danger">Удалить</span></a></li>
                                            </ul>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                            @if($trashed_items->count())
                                <!-- <li>
                                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                                        <h6 class="h6 m-0">Удаленные</h6>
                                    </div>
                                </li> -->
                            @endif
                            @foreach($trashed_items as $user)
                            <!-- <li>
                                <div class="position-relative align-items-center @if(isset($dataTypeContent->id) && $dataTypeContent->id == $user->id) active @endif">
                                    <a href="{{ route('users.edit', $user->id) }}" >
                                        <button class="treeview-toggler" ></button>
                                        <span @if(!$user->name) class="text-gray" @endif >{{ $user->name ?? '(не заполнено)' }}</span>
                                        <a class="dropdown-toggle" href="#" id="dd{{ $user->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                            <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd{{ $user->id }}">
                                            <li><a class="dropdown-item js-restore-model" data-id="{{ $user->id }}" data-model="users" href="#">Восстановить</a></li>
                                        </ul>
                                    </a>
                                </div>
                            </li> -->
                            @endforeach
                        </ul>
                        <ul class="c-drag-list list-unstyled mb-0 js-sort d-none">

                            
                            @if(!isset($dataTypeContent->id))
                                <li class="side-list__item d-flex position-relative active">
                                    <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                    <div class="btn btn-light w-100">
                                        <span class="text-gray">(не заполнено)</span>
                                        <a class="dropdown-toggle" href="#" id="dd" role="button" data-toggle="dropdown" aria-expanded="false">
                                            <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                        </a>
                                    </div>                                            
                                </li>
                            @endif
                            @foreach($users as $item)
                            <li class="side-list__item d-flex position-relative @if(isset($dataTypeContent->id) && $dataTypeContent->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="users" data-href="/admin/users/{{$item->id}}/edit">
                                <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                <div class="btn btn-light w-100">
                                    <span @if(!$item->name) class="text-gray" @endif >{{ $item->name.' '.$item->last_name ?? '(не заполнено)' }}</span>
                                    <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                        <li><a class="dropdown-item js-copy-model" data-id="{{ $item->id }}" data-model="users" href="#">Скопировать</a></li>
                                        <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="users" href="#"><span class="text-danger">Удалить</span></a></li>
                                    </ul>
                                </div>                                            
                            </li>
                            @endforeach
                            @foreach($trashed_items as $item)
                            <li class="side-list__item d-flex position-relative @if(isset($dataTypeContent->id) && $dataTypeContent->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="users" data-href="/admin/users/{{$item->id}}/edit">
                                <div class="btn btn-light w-100">
                                    <span class="opacity-3">{{ $item->name.' '.$item->last_name ?? '(не заполнено)' }}</span>
                                    <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu__actions" aria-labelledby="dd{{ $item->id }}">
                                        <li><a class="dropdown-item js-restore-model" data-id="{{ $item->id }}" data-model="users" href="#">Восстановить</a></li>
                                    </ul>
                                </div>                                           
                            </li>
                            @endforeach
                            
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    <div>
                        <form id="form-{{ $dataTypeContent->id ?? '' }}" class="form-edit-add" 
                              action="@if(!is_null($dataTypeContent->getKey())){{ route('users.update', $dataTypeContent->getKey()) }}@else{{ route('users.store') }}@endif"
                              method="POST" enctype="multipart/form-data" autocomplete="off">
                              @if (count($errors) > 0)
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                            <!-- PUT Method if we are editing -->
                            @if(isset($dataTypeContent->id))
                                {{ method_field("PUT") }}
                            @endif
                            {{ csrf_field() }}
                            @if($dataTypeContent)
                                <ul class="list-unstyled">
                                @if($sections)
                                    @foreach($sections as $section)
                                    <li>
                                    <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
                                        <div class="position-relative me-auto d-flex align-items-center">
                                            <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                                        </div>
                                        <div class="settings position-relative">
                                        </div>
                                    </div>
                                    <div class="c-body p-4">
                                        <div class="row mb-2 justify-content-between">
                                            <div class="col-lg-6">
                                                <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">

                                                @if($section->visible_fields && count($section->visible_fields))
                                                    @foreach($section->visible_fields as $k => $field)
                                                        @php
                                                            $visible_field = false;
                                                            $subfield_names = array();
                                                            $subfield_values = array();
                                                            if($field->type == 'text_group') {
                                                                $subfields = \App\Models\Field::getByGroup($field->id);
                                                                
                                                                
                                                                foreach($subfields as $subfield) {
                                                                    $subfield_names[] = $subfield->field;
                                                                    $subfield_values[] = $dataTypeContent->{$subfield->field};
                                                                    if($dataTypeContent->{$subfield->field}) {
                                                                        $visible_field = true;
                                                                    }
                                                                }
                                                            }
                                                            
                                                        @endphp
                                                        <li class="@if($field->type != 'status')col-lg-12 @endif {{ !$field->visible_always && !$dataTypeContent->{$field->field} && $field->type != 'text_group' || $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}">

                                                            <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                                <div class="label">
                                                                    {{ $field->display_name }}
                                                                </div>
                                                            </div>
                                                            @if($field->type == 'text_group')
                                                                <div class="js-editable" data-field="{{ implode(',', $subfield_names) }}" data-value="{{ implode(',', $subfield_values) }}" data-type="multiple_input">
                                                                        @include('fields.show.multipletext', ['field_data' => $field, 'field' => implode(',', $subfield_names), 'value' => implode(',', $subfield_values), 'model' => 'users' ])
                                                                </div>
                                                            @else
                                                                @if($field->type != 'status')
                                                                    <div class="js-editable @if($field->type == 'image') active @endif"  data-field="{{ $field->field }}" data-type="{{ $field->type }}" data-value="{{ $dataTypeContent->{$field->field} }}">
                                                                    @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $dataTypeContent->{$field->field} ])
                                                                    </div>
                                                                @else
                                                                    @include('fields.values.'.$field->type, ['field' => $field, 'current' => $dataTypeContent])
                                                                @endif
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                @else
                                                <li></li>
                                                @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    </li>
                                    @endforeach
                                @endif
                                <li>
                                    <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
                                        <div class="position-relative me-auto d-flex align-items-center">
                                            <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">Технические</h6>
                                        </div>
                                        <div class="settings position-relative">
                                        </div>
                                    </div>
                                    <div class="c-body p-4">
                                        <div class="row mb-2 justify-content-between">
                                            <div class="col-lg-6">
                                                <ul class="position-relative row list-unstyled c-list js-sort-form" >
                                                    <li class="col-lg-12">
                                                        <div class="label mb-1">{{ __('voyager::generic.password') }}</div>
                                                        @if(isset($dataTypeContent->password))
                                                            <small>{{ __('voyager::profile.password_hint') }}</small>
                                                        @endif
                                                        <input @if(!isset($dataTypeContent->id)) required @endif type="password" class="form-control" id="password" @if(!is_null($dataTypeContent->getKey())) name="PASSWORD" @else name="password" @endif  value="" autocomplete="new-password">
                                                    </li>

                                                    
                                                        <li class="col-lg-12 form-group d-none">
                                                            <div class="label mb-1">{{ __('voyager::profile.role_default') }}</div>
                                                            @php
                                                                $dataTypeRows = $dataType->{(isset($dataTypeContent->id) ? 'editRows' : 'addRows' )};

                                                                $row     = $dataTypeRows->where('field', 'user_belongsto_role_relationship')->first();
                                                                $options = $row->details;
                                                            @endphp
                                                            @include('voyager::formfields.relationship')
                                                        </li>
                                                        <li class="col-lg-12 roles-select">
                                                            <div class="label mb-1">Роли</div>
                                                            @php
                                                                $row     = $dataTypeRows->where('field', 'user_belongstomany_role_relationship')->first();
                                                                $options = $row->details;
                                                            @endphp
                                                            <div class="position-relative">
                                                            @include('voyager::formfields.relationship')
                                                            </div>
                                                        </li>
                                                    
                                                    @php
                                                    if (isset($dataTypeContent->locale)) {
                                                        $selected_locale = $dataTypeContent->locale;
                                                    } else {
                                                        $selected_locale = config('app.locale', 'en');
                                                    }

                                                    @endphp
                                                    <li class="col-lg-12 form-group d-none">
                                                        <div class="label mb-1">{{ __('voyager::generic.locale') }}</div>
                                                        <select class="form-control select2" id="locale" name="locale">
                                                            @foreach (Voyager::getLocales() as $locale)
                                                            <option value="{{ $locale }}"
                                                            {{ ($locale == $selected_locale ? 'selected' : '') }}>{{ $locale }}</option>
                                                            @endforeach
                                                        </select>
                                                    </li>

                                                    <li class="col-lg-12">
                                                        <div class="label mb-1">Идентификатор в CRM</div>
                                                        <input type="text" class="form-control" name="crm_id" placeholder=""
                                                               value="{{ old('crm_id', $dataTypeContent->crm_id ?? '') }}">
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </ul>
                            @endif

                            <button type="submit" class="btn btn-primary pull-right save d-none">
                                {{ __('voyager::generic.save') }}
                            </button>
                        </form>

                        <iframe id="form_target" name="form_target" style="display:none"></iframe>
                        <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post" enctype="multipart/form-data" style="width:0px;height:0;overflow:hidden">
                            {{ csrf_field() }}
                            <input name="image" id="upload_file" type="file" onchange="$('#my_form').submit();this.value='';">
                            <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
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
        .roles-select select {
            height: 100px;
        }
        .js-save-panel {
            display: none!important;
        }
    </style>
    <script type="text/javascript">
        $('body').on('keyup', 'input', function(){
            $('.js-save-panel-roles').show();
        });
        $('body').on('change', 'select', function(){
           $('.js-save-panel-roles').show();
        });
        $('body').on('click', '.js-submit-roles', function(){
            $('.form-edit-add').submit();
            $(this).addClass('disabled');
        });
        $('body').on('click', '.js-reset-fields-roles', function(){
            $('.js-save-panel-roles').hide();
            updateContent();
        });
        $('body').on('change', '.js-editable', function(){
            $(this).addClass('active')
        })
    </script>
@endsection
