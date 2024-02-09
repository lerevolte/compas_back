<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="shortcut icon" href="img/icon.ico" type="image/x-icon">
   <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}?v=<?=random_int(1, 20000)?>">
   <link rel="stylesheet" href="{{ asset('css/style1.css') }}?v=<?=random_int(1, 20000)?>">
   <title>Compas</title>
</head>

<body>
<div class="wrapper">
<!--header-->
<header class="header">
    <div class="_container">
        <div class="header__block">
            <div class="header__content">
                <a href="/" class="header__logo"><img src="/img/header/compas.svg" alt=""></a>
                <ul class="header__list">
                    
                    <li><a href="/prices" class="header__link">Тарифы</a></li>
                    <li><a href="/contacts" class="header__link">Контакты</a></li>
                </ul>
            </div>
            <div class="header__button">
                <a href="/authentication">Вход</a>
            </div>
            <div class="header__burger">
                <span></span>
            </div>
        </div>

    </div>
</header>
<main class="main">
  @yield('content')
</main>
<footer class="footer">
    <div class="_container">
        <div class="footer__block">
            <div class="footer__body">
                <ul class="footer__list">
                    <li><span>Карта сайта</span></li>
                    <li><a href="/" class="footer__link">Главная страница</a></li>
                    <li><a href="/prices" class="footer__link">Тарифы</a></li>
                    <li><a href="/authentication" class="footer__link">Вход</a></li>
                </ul>
            </div>
            <div class="footer__content">
                <!-- <a class="footer__button" href="#">
                    <img src="/img/footer/russia.png" alt=""> Россия
                </a> -->
                <div class="footer__info">
                    <div class="header__logo"><img src="/img/footer/logo.svg" alt=""></div>
                    <div class="footer__text">
                        <ul class="footer__list">
                            <li><span>Контактная информация</span></li>
                            <li>ООО «Экспер Маршрута»</li>
                            <li>E-mail: info@opt6.ru</li>
                            <li>Адрес: г. Москва, 2-й южнопортовый <br> проезд, 10 стр 96</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
 <script src="{{ asset('js/swiper-bundle.min.js') }}<?=random_int(1, 20000)?>"></script>
 <script src="{{ asset('js/script.js') }}<?=random_int(1, 20000)?>"></script>
 <script type="text/javascript">
     $(document).ready(function(){
      $('.js-check-input').on('input', function() {
          var needShow = true;
          $('.js-check-input').each(function(index) {
            if($(this).val().length < $(this).data('length'))
              needShow = false;
          });
          if(needShow) {
              $('.js-hidden-input').show();
          }
      });
     })
 </script>
</body>
</html>
