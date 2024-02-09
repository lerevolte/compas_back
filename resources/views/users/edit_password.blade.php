@extends('layouts.main')
@section('title')
Изменить пароль
@endsection
@section('h1')
    <h1 class="my-0 h1">Изменить пароль</h1>
@endsection
@section('subnav')
@endsection
@section('content')
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>
    <form class="js-change-form" method="post" action="{{ route('users.update_password') }}" oninput="confirm_password.setCustomValidity(confirm_password.value != password.value ? 'Пароли не совпадают.' : '')">
        {{ csrf_field() }}
        <div class="rounded-1 bg-white border t">
            <div class="t-body " data-model="users">
                <div class="row g-0">
                    <div class="col-lg-3 border-end">
                        <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                            <h6 class="h6 m-0">Категории</h6>
                        </div>
                        <div class="c-body p-0">
                            <ul class="storages-list c-drag-list list-unstyled mb-0 ui-sortable">
                                <li class="side-list__item active" data-id="{{ $current->id }}">
                                    <div class="position-relative">
                                        <a href="{{ route('users.profile') }}">Личный профиль</a>
                                    </div>
                                </li>
                                <li>
                                    <div class="position-relative active">
                                        <a href="javascript:;">Пароль</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-9 carrier-content">
                        <ul class="list-unstyled">
                        @if($current)
                            <li>
                            <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section">
                                <div class="position-relative me-auto d-flex align-items-center">
                                    <h6 class="h6 my-0 me-auto">Изменить пароль</h6>
                                </div>
                                <div class="settings position-relative">
                                </div>
                            </div>
                            <div class="c-body p-4">
                                <div class="row mb-2 justify-content-between">
                                    <div class="col-lg-6">
                                        <ul class="position-relative row list-unstyled c-list js-sort-form">
                                            <li class="col-lg-12">
                                                <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                    <div class="label">
                                                        Новый пароль
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="row g-2 flex-nowrap">
                                                        <div class="col-lg-12">
                                                            <div class="position-relative">
                                                                <input name="password" type="password" class="form-control" value="" required minlength="8">
                                                            </div>                       
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="col-lg-12">
                                                <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                    <div class="label">
                                                        Подтверждение пароля
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="row g-2 flex-nowrap">
                                                        <div class="col-lg-12">
                                                            <div class="position-relative">
                                                                <input name="confirm_password" type="password" class="form-control" value="" required minlength="8">
                                                            </div>                       
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            </li>
                        @endif
                        </ul>
                    </div>
                </div>

            </div>

        </div>
        <div class="js-save-panel-password save-panel" style="display: none;left:0">
            <button type="submit" class="js-submit-password blue-btn">Сохранить</button>
            <button class="gray-btn js-reset-fields-roles">Отменить</button>
        </div>
    </form>
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
    <script type="text/javascript">
        $(document).ready(function(){
            $('body').on('change', '.form-control', function(){
                $('.js-save-panel-password').show();
            });
            
            
        });
    </script>
@endsection
    