@extends('layouts.main')

@section('content')
<main class="main" style="background: url(/img/reg-bg.svg) center no-repeat;">
    <section class="showHeader">
    </section>
    <section class="entrance__container">
        <div class="_container">
            <div class="registration__title title">Вход в личный кабинет</div>
            <div class="registration__block">

                <div class="registration__block-form">
                    <form action="" class="registration__form">
                        <div class="registration__two entrance__two">
                            <div class="registration__name">
                                <div class="registration__sub-input">E-mail</div>
                                <input type="email" placeholder="">
                            </div>
                            <div class="registration__name">
                                <div class="registration__sub-input">Пароль</div>
                                <input type="password" placeholder="">
                            </div>
                            <div class="agreement entrance">
                                <input id="Agreement" checked type="checkbox"><label for="Agreement"> Запомнить
                                    пароль</label>
                            </div>
                            <div class="registration__button">
                                <button>Войти</button>
                            </div>
                            <div class="registration__password">
                                <a href="">Восстановление пароля</a>
                            </div>
                        </div>
                        <div class="registration__link">
                            Нет аккаунта, <a href="/registration">создайте бесплатный аккаунт.</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection