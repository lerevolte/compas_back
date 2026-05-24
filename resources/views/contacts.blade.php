@extends('layouts.main')

@section('content')
<section class="showHeader">
    </section>
    <section class="contacts">
        <div class="_container">
            <div class="contacts__title title">Контакты</div>
            <div class="contacts__block">
                <div class="contacts__body">
                    <div class="contacts__text">
                        Телефон: <br>
                        <span>+7 (495) 118-44-22</span> <br> <br>
                        E-mail: <br>
                        <span>info@exrout.com</span>
                    </div>
                </div>
                <div class="contacts__img _ibg" style="background: url(/img/contacts/newback.svg) center -40px no-repeat;">
                </div>
            </div>
        </div>
    </section>
    <section class="info">
        <div class="_container">
            <div class="info__block">
                <div class="info__content">
                    <div class="info__body info-body">
                        <div class="info-body__img"><img src="/img/contacts/1.svg" alt=""></div>
                        <ul class="info-body__list">
                            <li><span>Контактная информация</span></li>
                            <li>ООО «ОПТ6»</li>
                            <li>Тел.: +7(495)777-83-79</li>
                            <li>E-mail: info@opt6.ru</li>
                            <li>Отдел закупок: zakupki@opt6.ru</li>
                            <li>Адрес: г. Москва, 2-й южнопортовый проезд, 10 стр 96</li>
                            <li>
                                <a href="" class="info-body__link"><img src="/img/contacts/icon.svg" alt="">Реквизиты
                                    компании</a>
                            </li>
                        </ul>
                    </div>
                    <div class="info__map">
                        <script type="text/javascript" charset="utf-8" async
                            src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Aced79e6b6347e36f7b81c9f97fb801bfb6835784b1fed9917a5c9b2fbb2e946c&amp;width=100%25&amp;height=250&amp;lang=ru_RU&amp;scroll=true"></script>
                    </div>
                </div>
                <div class="info__form-block">
                    <form action="" class="info__form info-form">
                        <div class="info-form__title">Возникли вопросы?</div>
                        <div class="info-form__tel">
                            <input type="tel" placeholder="Телефон">
                        </div>
                        <div class="info-form__email">
                            <input type="email" placeholder="E-mail">
                        </div>
                        <div class="info-form__text">
                            <textarea name="" id="" cols="30" rows="10" placeholder="Напишите свой вопрос"></textarea>
                        </div>
                        <div class="info-form__button">
                            <button type="submit">Задать вопрос</button>
                        </div>
                    </form>
                    <div class="info-form-success" style="display: none;">
                        <b>Мы с Вами свяжемся в ближайшее время</b>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection