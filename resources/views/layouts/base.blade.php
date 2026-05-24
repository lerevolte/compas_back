<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.min.css') }}?v={{ random_int(1, 20000) }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/select2/css/select2.min.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/coloris.min.css?v=') }}{{ random_int(1, 20000) }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('libs/fancybox/jquery.fancybox.min.css') }}"/>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/old.css?v=') }}{{ random_int(1, 20000) }}"/>
    <title>@yield('title')</title>
    <script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.inputmask.min.js') }}"></script>
    <script src="{{ asset('libs/fancybox/jquery.fancybox.min.js') }}"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="{{ asset('js/daterangepicker.js') }}?v={{ random_int(1, 20000) }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ asset('js/coloris.min.js?v=') }}<?=random_int(1, 20000)?>"></script>
</head>

<body>
  <div class="sidepanel-overlay"></div>
  @include('blocks.navbar')
  <header class="header" id="header">

    <div class="container">
      @yield('top_menu')
      
      <div class="header__wrapper">
        <h1 class="header__title">@yield('h1')</h1>
        @yield('search')
        <button class="header__btn btn btn_blue btn_with-icon js-add-object" type="button">
          <img src="{{ asset('img/icons/add.svg') }}" alt="Add icon">
          Создать запись
        </button>
      </div>
    </div><!-- /.container -->
  </header><!-- /.header -->
  <div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu__wrapper">
      <div class="mobile-menu__header">
        <h3 class="mobile-menu__title header__title">Меню</h3>
        <button class="mobile-menu__close btn-clear" data-mobile="close">
          <img src="{{ asset('img/icons/close-menu.svg') }}" alt="Close button">
        </button>
      </div>
      <nav class="mobile-menu__nav">
        <ul class="mobile-menu__list">
          <li class="mobile-menu__item">
            <a class="mobile-menu__link" href="#">Логистика</a>
          </li>
          <li class="mobile-menu__item">
            <button class="mobile-menu__link mobile-menu__link_sub" type="button" data-mobile="btn">Перевозчики</button>
            <div class="mobile-menu__wrapper mobile-menu__wrapper_sub" data-mobile="menu">
              <div class="mobile-menu__header">
                <h3 class="mobile-menu__title header__title">Перевозчики</h3>
                <button class="mobile-menu__close btn-clear" data-mobile="closeSub">
                  <img src="{{ asset('img/icons/close-menu.svg') }}" alt="Close button">
                </button>
              </div>
              <nav class="mobile-menu__nav">
                <ul class="mobile-menu__list">
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">Справочние</a>
                  </li>
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">База</a>
                  </li>
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">База</a>
                  </li>
                </ul>
              </nav>
            </div>
          </li>
          <li class="mobile-menu__item">
            <a class="mobile-menu__link" href="#">Заказы по дням</a>
          </li>
          <li class="mobile-menu__item">
            <button class="mobile-menu__link mobile-menu__link_sub" type="button" data-mobile="btn">Нераспределенные заказы</button>
            <div class="mobile-menu__wrapper mobile-menu__wrapper_sub" data-mobile="menu">
              <div class="mobile-menu__header">
                <h3 class="mobile-menu__title header__title">Нераспределенные заказы</h3>
                <button class="mobile-menu__close btn-clear" data-mobile="closeSub">
                  <img src="{{ asset('img/icons/close-menu.svg') }}" alt="Close button">
                </button>
              </div>
              <nav class="mobile-menu__nav">
                <ul class="mobile-menu__list">
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">Справочние</a>
                  </li>
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">База</a>
                  </li>
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">База</a>
                  </li>
                </ul>
              </nav>
            </div>
          </li>
          <li class="mobile-menu__item">
            <a class="mobile-menu__link" href="#">Задачи</a>
          </li>
          <li class="mobile-menu__item">
            <a class="mobile-menu__link" href="#">Настройки</a>
          </li>
          <li class="mobile-menu__item">
            <button class="mobile-menu__link mobile-menu__link_sub" type="button" data-mobile="btn">Денис Потемкин</button>
            <div class="mobile-menu__wrapper mobile-menu__wrapper_sub" data-mobile="menu">
              <div class="mobile-menu__header">
                <h3 class="mobile-menu__title header__title">Денис Потемкин</h3>
                <button class="mobile-menu__close btn-clear" data-mobile="closeSub">
                  <img src="{{ asset('img/icons/close-menu.svg') }}" alt="Close button">
                </button>
              </div>
              <nav class="mobile-menu__nav">
                <ul class="mobile-menu__list">
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link" href="#">Личные данные</a>
                  </li>
                  <li class="mobile-menu__item">
                    <a class="mobile-menu__link mobile-menu__link_exit" href="#">Выйти</a>
                  </li>
                </ul>
              </nav>
            </div>
          </li>
        </ul>
      </nav>
    </div>
  </div><!-- /.mobile-menu -->

  <main class="page">
    <div class="container">
      @yield('content')
    </div><!-- /.container -->
    <div class="table-actions table-actions_active">
      <div class="table-actions__item">
        <button class="table-actions__btn btn btn_grey btn_with-icon" id="editBtn">
          <img src="{{ asset('img/icons/edit.svg') }}" alt="Edit icon">
          Редактировать
        </button>
        <button class="table-actions__btn btn btn_grey btn_with-icon" id="cancelBtn" type="button">Отменить</button>
        <button class="table-actions__btn btn btn_grey btn_with-icon" id="deleteBtn" style="position: absolute;right: 25px;">
          <img src="{{ asset('img/icons/delete.svg') }}" alt="Delete icon">
          Удалить
        </button>
      </div>
      <div class="table-actions__item table-actions__item_edit">
        <button class="table-actions__btn btn btn_blue" id="saveBtn" type="button">Сохранить</button>
        <button class="table-actions__btn btn btn_grey btn_with-icon" id="cancelBtn" type="button">Отменить</button>
      </div>
    </div>
  </main>
  <!-- <script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('js/jquery-ui.min.js') }}"></script> -->
  
  <script type="module" src="{{ asset('js/app1.js') }}?v={{ random_int(1, 20000) }}"></script>

  @yield('scripts')
  <div style="height: 0; width: 0; position: absolute; visibility: hidden">

        <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <defs>
                <symbol id="icon-data" viewBox="0 0 44 32">
                <path d="M20.364 0h-8.727v8.727h8.727v-8.727zM8.727 11.636h-8.727v8.727h8.727v-8.727zM8.727 23.273h-8.727v8.727h8.727v-8.727zM20.364 23.273h-8.727v8.727h8.727v-8.727zM23.273 23.273h8.727v8.727h-8.727v-8.727zM11.636 11.636h8.727v8.727h-8.727v-8.727zM32 11.636h-8.727v8.727h8.727v-8.727zM34.909 11.636h8.727v8.727h-8.727v-8.727zM23.273 0h8.727v8.727h-8.727v-8.727zM43.636 0h-8.727v8.727h8.727v-8.727z"></path>
                </symbol>
                <symbol id="icon-date" viewBox="0 0 32 32">
                <path d="M6.4 21.333v6.4h-6.4v-6.4h6.4zM14.933 21.333v6.4h-6.4v-6.4h6.4zM23.467 21.333v6.4h-6.4v-6.4h6.4zM6.4 12.8v6.4h-6.4v-6.4h6.4zM14.933 12.8v6.4h-6.4v-6.4h6.4zM23.467 12.8v6.4h-6.4v-6.4h6.4zM32 12.8v6.4h-6.4v-6.4h6.4zM14.933 4.267v6.4h-6.4v-6.4h6.4zM23.467 4.267v6.4h-6.4v-6.4h6.4zM32 4.267v6.4h-6.4v-6.4h6.4z"></path>
                </symbol>
                <symbol id="icon-dd" viewBox="0 0 32 32">
                <path fill="#1253a2" style="fill: var(--color1, #1253a2)" d="M13.66 22.886l-10.801-10.806-2.219 2.202 13.020 13.008 17.7-17.738-2.178-2.192z"></path>
                </symbol>
                <symbol id="icon-exel" viewBox="0 0 32 32">
                <path fill="#207347" style="fill: var(--color2, #207347)" d="M18.615 0l-0.041 32-18.365-3.329-0.209-25.079 18.615-3.592zM19.381 3.721l-0.032 24.558 11.514-0.067c0.629-0.004 1.137-0.515 1.136-1.144l-0.015-22.16c-0-0.629-0.509-1.14-1.138-1.142l-11.465-0.046zM24.186 23.070h5.209v2.605h-5.209v-2.605zM24.186 18.977h5.209v2.605h-5.209v-2.605zM24.186 14.884h5.209v2.605h-5.209v-2.605zM24.186 10.791h5.209v2.605h-5.209v-2.605zM24.186 6.326h5.209v2.605h-5.209v-2.605zM19.353 23.070h3.345v2.605h-3.345v-2.605zM19.349 18.977h3.349v2.605h-3.349v-2.605zM19.369 14.884h3.329v2.605h-3.329v-2.605zM19.369 10.791h3.329v2.605h-3.329v-2.605zM14.326 9.86h-3.163l-1.674 4.465-1.488-4.093h-2.791l2.791 6.14-2.977 5.954h2.791l1.674-4.279 1.861 4.465h2.977l-3.163-6.326 3.163-6.326zM22.698 6.326v2.605h-3.329v-2.605h3.329z"></path>
                </symbol>
                <symbol id="icon-eye" viewBox="0 0 32 32">
                <path d="M31.587 14.523c-0.281-0.333-6.998-8.108-15.586-8.108s-15.305 7.775-15.586 8.105c-0.552 0.65-0.552 1.594 0 2.243 0.281 0.333 6.998 8.108 15.586 8.108s15.305-7.775 15.586-8.105c0.552-0.653 0.552-1.594 0-2.243zM18.623 11.085c0.578-0.313 1.381 0.046 1.799 0.807 0.415 0.761 0.284 1.633-0.294 1.95s-1.381-0.046-1.799-0.807c-0.415-0.761-0.284-1.633 0.294-1.95zM16.001 22.053c-5.999 0-11.047-4.608-12.794-6.41 1.179-1.218 3.866-3.713 7.334-5.205-0.676 1.029-1.074 2.26-1.074 3.582 0 3.608 2.926 6.534 6.534 6.534s6.534-2.926 6.534-6.534c0-1.323-0.395-2.554-1.071-3.582 3.465 1.492 6.152 3.987 7.331 5.205-1.747 1.806-6.795 6.41-12.794 6.41z"></path>
                </symbol>
                <symbol id="icon-logout" viewBox="0 0 32 32">
                <path fill="#fd8301" style="fill: var(--color3, #fd8301)" d="M23.33 19.714v-3.714h-9.244v-3.714h9.244v-3.714l5.547 5.571-5.547 5.571zM21.481 17.857v7.429h-9.244v5.571l-11.093-5.571v-24.143h20.338v9.286h-1.849v-7.429h-14.791l7.396 3.714v16.714h7.396v-5.571h1.849z"></path>
                </symbol>
                <!-- <symbol id="icon-menu" viewBox="0 0 32 32">
                <path fill="#bcbcbc" style="fill: var(--color4, #bcbcbc)" d="M28 26.667c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667s1.194-2.667 2.667-2.667h24zM28 13.333c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667s1.194-2.667 2.667-2.667h24zM28 0c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667s1.194-2.667 2.667-2.667h24z"></path>
                </symbol> -->
                <symbol id="icon-menu" viewBox="0 0 32 32">
                <path d="M5.333 29.333h-5.333v-5.333h5.333v5.333zM5.333 13.333h-5.333v5.333h5.333v-5.333zM5.333 2.667h-5.333v5.333h5.333v-5.333zM9.333 2.667v5.333h22.667v-5.333h-22.667zM9.333 18.667h22.667v-5.333h-22.667v5.333zM9.333 29.333h22.667v-5.333h-22.667v5.333z"></path>
                </symbol>
                <symbol id="icon-settings" viewBox="0 0 32 32">
                <path d="M17.584 0.102c0.829 0 1.5 0.671 1.5 1.5v0 1.904c1.068 0.28 2.077 0.699 3.007 1.244v0l1.345-1.345c0.586-0.586 1.536-0.586 2.121-0v0l2.181 2.181c0.586 0.586 0.586 1.536 0 2.121v0l-1.345 1.345c0.545 0.93 0.965 1.939 1.244 3.007v0l1.904-0c0.828 0 1.5 0.672 1.5 1.5v0 3.084c0 0.828-0.672 1.5-1.5 1.5v0l-1.904 0c-0.28 1.068-0.7 2.077-1.244 3.007v0l1.346 1.346c0.586 0.586 0.586 1.535 0 2.121v0l-2.181 2.181c-0.586 0.586-1.535 0.586-2.121 0v0l-1.346-1.346c-0.93 0.545-1.939 0.965-3.007 1.244v0 1.904c0 0.828-0.671 1.5-1.5 1.5v0h-3.084c-0.828 0-1.5-0.672-1.5-1.5v0-1.904c-1.068-0.28-2.077-0.699-3.007-1.244v0l-1.346 1.346c-0.586 0.586-1.535 0.586-2.121 0v0l-2.181-2.181c-0.586-0.586-0.586-1.536 0-2.121v0l1.346-1.345c-0.545-0.93-0.965-1.939-1.244-3.007v0h-1.904c-0.828 0-1.5-0.671-1.5-1.5v0-3.084c0-0.828 0.672-1.5 1.5-1.5v0h1.904c0.28-1.068 0.699-2.077 1.244-3.007v0l-1.346-1.345c-0.586-0.586-0.586-1.536 0-2.121v0l2.181-2.181c0.586-0.586 1.536-0.586 2.121-0v0l1.345 1.345c0.93-0.545 1.939-0.965 3.008-1.245v0-1.904c0-0.829 0.672-1.5 1.5-1.5v0zM16.045 9.598c-3.040 0-5.504 2.464-5.504 5.504s2.464 5.504 5.504 5.504c3.040 0 5.504-2.464 5.504-5.504s-2.464-5.504-5.504-5.504z"></path>
                </symbol>
                <symbol id="icon-line" viewBox="0 0 29 32">
                <path d="M0 2.667c0-1.473 1.194-2.667 2.667-2.667h24c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667zM0 16c0-1.473 1.194-2.667 2.667-2.667h24c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667zM2.667 26.667c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h24c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-24z"></path>
                </symbol>
                <symbol id="icon-dots" viewBox="0 0 7 32">
                <path d="M0 3.692c0-2.039 1.653-3.692 3.692-3.692s3.692 1.653 3.692 3.692c0 2.039-1.653 3.692-3.692 3.692s-3.692-1.653-3.692-3.692zM0 16c0-2.039 1.653-3.692 3.692-3.692s3.692 1.653 3.692 3.692c0 2.039-1.653 3.692-3.692 3.692s-3.692-1.653-3.692-3.692zM3.692 24.615c-2.039 0-3.692 1.653-3.692 3.692s1.653 3.692 3.692 3.692c2.039 0 3.692-1.653 3.692-3.692s-1.653-3.692-3.692-3.692z"></path>
                </symbol>
                <symbol id="icon-linedot" viewBox="0 0 29 32">
                <!-- <path d="M2.667 0c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h8c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-8zM18.667 0c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h8c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-8zM18.667 13.333c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h8c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-8zM16 29.333c0-1.473 1.194-2.667 2.667-2.667h8c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-8c-1.473 0-2.667-1.194-2.667-2.667zM0 16c0-1.473 1.194-2.667 2.667-2.667h8c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-8c-1.473 0-2.667-1.194-2.667-2.667zM2.667 26.667c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h8c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-8z"></path> -->
                <path d="M0 2.667c0-1.473 1.194-2.667 2.667-2.667h24c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667zM0 16c0-1.473 1.194-2.667 2.667-2.667h24c1.473 0 2.667 1.194 2.667 2.667s-1.194 2.667-2.667 2.667h-24c-1.473 0-2.667-1.194-2.667-2.667zM2.667 26.667c-1.473 0-2.667 1.194-2.667 2.667s1.194 2.667 2.667 2.667h24c1.473 0 2.667-1.194 2.667-2.667s-1.194-2.667-2.667-2.667h-24z"></path>
                </symbol>
                <symbol id="icon-plus-light" viewBox="0 0 32 32">
                <path d="M17.455 0h-2.909v14.545h-14.545v2.909h14.545v14.545h2.909v-14.545h14.545v-2.909h-14.545v-14.545z"></path>
                </symbol>
            </defs>
        </svg>
        
    </div>
</body>

</html>