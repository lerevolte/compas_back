@extends('layouts.main')

@section('content')
<section class="showHeader">
    </section>
    <section class="e404">
        <div class="_container">
            <div class="e404__content">
                <div class="e404__block _ibg">
                    <img src="/img/404.svg" alt="">
                </div>
                <div class="e404__text">
                    <div class="e404__title title">
                        Страница не найдена, ошибка 404
                    </div>
                    <a href="/">Главная страница</a>
                </div>
            </div>
        </div>
    </section>
@endsection