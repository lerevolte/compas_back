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
            <a href="{{ route('settings.users') }}" class="btn btn-outline-secondary active">Пользователи</a>
            <a href="{{ route('settings.roles') }}" class="btn btn-outline-secondary ">Роли</a>
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Административные настройки</a>
            <a href="{{ route('balance') }}" class="btn btn-outline-secondary">Тариф</a>
        </div>
    </div>
@endsection
@section('content')
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
                        <a href="/admin/users/create" class="link" data-touch="false">Добавить</a>
                    </div>
                    <div class="c-body p-0">
                        <ul class="c-drag-list list-unstyled mb-0 js-sort">
                            @foreach($items as $item)
                            <li class="side-list__item d-flex position-relative" data-id="{{ $item->id }}" data-model="users" data-href="/admin/users/{{$item->id}}/edit">
                                <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                <div class="btn btn-light w-100">
                                    <span @if(!$item->name) class="text-gray" @endif >{{ $item->name ?? '(не заполнено)' }}</span>
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
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    
                    
                </div>
            </div>
        </div>                    
    </div>

@endsection
