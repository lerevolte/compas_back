@extends('layouts.main')

@section('content')
    <section class="home">
        <div class="_container">
            <div class="home__block">
                <div class="home__body">
                    <div class="home__title">Проверьте штрафы и<br> зарегестрируйтесь в 1 клик</div>
                    <form class="form">
                        <div class="form-row">
                            <div class="form-col-8">
                                <label class="form-label">Номер автомобиля</label>
                                <input class="form-control js-check-input" data-length="6" type="text" placeholder="">
                            </div>
                            <div class="form-col-4">
                                <label class="form-label">Регион</label>
                                <input class="form-control js-check-input" data-length="3" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="form-row" style="margin-bottom: 25px">
                            <div class="form-col-12">
                                <label class="form-label">Свидетельство о регистрации ТС</label>
                                <input class="form-control js-check-input" data-length="10" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="form-row js-hidden-input" style="display: none;">
                            <div class="form-col-12">
                                <label class="form-label">Электронная почта для входа</label>
                                <input class="form-control" type="text" placeholder="">
                            </div>
                        </div>
                        <div class="form-row js-hidden-input" style="display: none;">
                            <div class="form-col-12">
                                <label class="form-label">Пароль для входа</label>
                                <input class="form-control" type="password" placeholder="">
                            </div>
                        </div>
                        
                    </form>
                    <div class="home__buttons">
                        <div class="home__button-l"><a href="">Проверить штрафы</a></div>
                        <div class="home__button-r"><a href=""><img src="/img/header/YOUTUBE.svg" alt=""> <span>О
                                    сервисе</span> (1
                                мин 20 сек)</a></div>
                    </div>
                    <div class="form-hint">
                        Нажимая «Проверить штрафы» вы соглашаетесь с политикой обработки персональных данных и принимаете оферту
                    </div>
                </div>
                <div class="home__block-img">
                    <div class="home__img _ibg">
                        <img src="/img/header/buck.png" alt="">
                    </div>
                </div>
            </div>
            <div class="home__items">
                <div class="home__item home-item">
                    <div class="home-item__img"><img style="width: 47px;height: 47px;" src="/img/header/11.svg" alt="">
                    </div>
                    <div class="home-item__body"><span>Все по одной цене</span><br>
                        Одна цена для всех машин, всего 9 рублей.</div>
                </div>
                <div class="home__item home-item">
                    <div class="home-item__img"><img style="width: 53px;height: 30px;" src="/img/header/22.svg" alt="">
                    </div>
                    <div class="home-item__body"><span>Фотоматериалы нарушения</span><br>
                        Вместе с остальной информацией о штрафах, вам доступны фотоматериалы нарушения</div>
                </div>
                <div class="home__item home-item">
                    <div class="home-item__img "><img style="width: 43px;height: 41px;" src="/img/header/33.svg" alt="">
                    </div>
                    <div class="home-item__body"><span>Удобный интерфейс</span><br>
                        Интуитивно понятный интерфей позволяет начать работать с 1-го дня.</div>
                </div>
                <div class="home__item home-item">
                    <div class="home-item__img"><img style="width: 45px;height: 43px;" src="/img/header/44.svg" alt="">
                    </div>
                    <div class="home-item__body"><span>Удобная аналитика</span><br>
                        Аналитика и история маршрутов, статистика доставок</div>
                </div>
            </div>
    </section>
    <section class="plus">
        <div class="_container">
            <div class="bootstrap-wrapper plus__block">
                <div class="plus__title title">Плюсы использования сервиса «Компас» для анализа штрафов</div>
                <div class="row plus__items">
                    <div class="col-md-4">
                        <div class=" plus__item plus-item">
                            <div class="plus-item__img"><img src="/img/plus/1.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Неограниченый автопарк</div>
                                <div class="plus-item__text">Можно добавить неограниченное кол-во машин и водителей</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class=" plus__item plus-item">
                            <div class="plus-item__img"><img src="/img/plus/2.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Сокращение затрат до 50%</div>
                                <div class="plus-item__text">Максимально быстро узнаете о штрафах в автоматическом режиме, тем самым у вас есть время оплатить по скидке.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">    
                        <div class="plus__item plus-item">
                            <div class="plus-item__img"><img src="/img/plus/3.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Безопасность</div>
                                <div class="plus-item__text">Данные передаются в зашифрованном виде, они доступны только получателю.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="plus__item plus-item plus-item__big">
                            <div class="plus-item__img"><img src="/img/plus/4.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Уведомление о погашении</div>
                                <div class="plus-item__text">Мы оповестим вас о том, что штраф был погашен и соответствующая запись создана в ГИС ГМП</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="plus__item plus-item plus-item__big">
                            <div class="plus-item__img"><img src="/img/plus/5.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Квитанция об оплате</div>
                                <div class="plus-item__text">После совершения оплаты банковской картой на Вашу электронную почту придет квитанция об успешной оплате.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="plus__item plus-item plus-item__big">
                            <div class="plus-item__img"><img src="/img/plus/6.svg" alt=""></div>
                            <div class="plus-item__body">
                                <div class="plus-item__title">Все история сохраняется по каждой машине</div>
                                <div class="plus-item__text">Вся история штрафов сохраняется по машине и водителю, позволяет анализировать статистику по штрафам</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="program">
        <div class="_container">
            <div class="program__block white">
                <div class="program__body">
                    <div class="program__title">Новым пользователям<br>100 проверок бесплатно!</div>
                    <div class="program__text">При регистрации Вы получите 100 проверок на штрафы ГИБДД<br> бесплатно.</div>
                    <div class="home__button-l"><a href="">Попробуйте бесплатно</a></div>
                </div>
                <div class="program__img _ibg"><img src="/img/program/back.png" alt=""></div>
            </div>
        </div>
    </section>
    <section class="plustwo">
        <div class="_container">
            <div class="plustwo__block">
                <div class="plustwo__title title">
                    Еще немного плюсов для анализа штрафов в Компасе
                </div>
                <div class="plustwo__items">
                    <div class="plustwo__item">
                        Вся информация хранится в одном месте
                    </div>
                    <div class="plustwo__item">
                        Удаленный доступ к программе
                    </div>
                    <div class="plustwo__item">Безопасность и сохранность данных</div>
                    <div class="plustwo__item">Удобный мобильны интерфейс</div>
                    <div class="plustwo__item">Неограниченное кол-во машин и водителей</div>
                    <div class="plustwo__item">Различные уровни доступа к программе у каждого сотрудника</div>
                    <div class="plustwo__item">+20% заказов в одном маршруте</div>
                    <div class="plustwo__item">5 день на настройку</div>
                    <div class="plustwo__item">Ручное и автоматическое обновление штрафов</div>
                </div>
            </div>
        </div>
    </section>
@endsection