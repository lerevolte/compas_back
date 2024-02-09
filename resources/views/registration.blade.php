@extends('layouts.main')

@section('content')
<main class="main" style="background: url(/img/reg-bg.svg) center no-repeat;">
    <section class="showHeader">
    </section>
    <section class="">
        <div class="_container">
            <div class="registration__title title">Создайте бесплатную учетную запись</div>
            <div class="registration__block">

                <div class="registration__block-form">
                    <form action="" class="registration__form">
                        <div class="registration__two">
                            <div class="registration__name" style="position: relative;">
                                <div class="registration__sub-input">Название портала</div>
                                <input type="email" placeholder="" style="padding-right: 90px">
                                <span class="domain-suffix">.compas.pro</span>
                            </div>
                            <div class="registration__name">
                                <div class="registration__sub-input">E-mail</div>
                                <input type="email" placeholder="">
                            </div>
                            <div class="registration__name">
                                <div class="registration__sub-input">Пароль</div>
                                <input type="password" placeholder="">
                            </div>
                            <div class="registration__name">
                                <div class="registration__sub-input">Подтверждение пароля</div>
                                <input type="password" placeholder="">
                            </div>
                            <div class="agreement">
                                <input id="Agreement" checked type="checkbox"><label for="Agreement"> Я понимаю и принимаю
                                    <a href="/privacy">условия</a> и <a href="/privacy">политику конфиденциальности</a> Compas.</label>
                            </div>
                            <div class="registration__button">
                                <button>Зарегистрирваться</button>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection