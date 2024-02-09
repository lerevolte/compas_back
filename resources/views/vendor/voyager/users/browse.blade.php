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
    {{ csrf_field() }}
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
                                <div class="position-relative" data-toggle="collapse" data-target=".list-{{ $item->id }}" aria-expanded="false">
                                    <a href="javascript:;" >
                                        @if($item->users->count())
                                        <button class="treeview-toggler" data-toggle="collapse" data-target=".list-{{ $item->id }}" aria-expanded="false" ><i class="fa fa-caret-down"></i></button>
                                        @else
                                        <button class="treeview-toggler" ></button>
                                        @endif
                                        {{ $item->display_name }}
                                    </a>
                                </div>
                                
                                <ul class="collapse list-{{ $item->id }}">
                                    @foreach($item->users as $child)
                                    <li>
                                        <div class="position-relative align-items-center">
                                            
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
                                    <div class="position-relative align-items-center">
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
                                <div class="position-relative align-items-center">
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
                            @foreach($users as $item)
                            <li class=" d-flex position-relative @if(isset($dataTypeContent->id) && $dataTypeContent->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="users" data-href="/admin/users/{{$item->id}}/edit">
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
                            <li class="d-flex position-relative @if(isset($dataTypeContent->id) && $dataTypeContent->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="users" data-href="/admin/users/{{$item->id}}/edit">
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
        });
        $('body').on('click', '.js-reset-fields-roles', function(){
            $('.js-save-panel-roles').hide();
            updateContent();
        });
    </script>
@endsection
