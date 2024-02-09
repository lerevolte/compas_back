<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" dir="{{ __('voyager::generic.is_rtl') == 'true' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="none" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="admin login">
    <title>@yield('title', 'Admin - '.Voyager::setting("admin.title"))</title>
    <link rel="stylesheet" href="{{ voyager_asset('css/app.css') }}">
    @if (__('voyager::generic.is_rtl') == 'true')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.css">
        <link rel="stylesheet" href="{{ voyager_asset('css/rtl.css') }}">
    @endif
    <style>
        body {
            background-image:url('{{ Voyager::image( Voyager::setting("admin.bg_image"), voyager_asset("images/bg.jpg") ) }}');
            background-color: {{ Voyager::setting("admin.bg_color", "#FFFFFF" ) }};
        }
        body.login .login-sidebar {
            border-top:5px solid {{ config('voyager.primary_color','#22A7F0') }};
        }
        @media (max-width: 767px) {
            body.login .login-sidebar {
                border-top:0px !important;
                border-left:5px solid {{ config('voyager.primary_color','#22A7F0') }};
            }
        }
        body.login .form-group-default.focused{
            border-color:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .login-button, .bar:before, .bar:after{
            background:{{ config('voyager.primary_color','#22A7F0') }};
        }
        .remember-me-text{
            padding:0 5px;
        }
    </style>
    
    @yield('pre_css')
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/style_new.css') }}?v={{ random_int(1, 20000) }}"/>
</head>
<body >
    <main class="main" style="margin: 0 !important;">
        <section class="entrance">
            <div class="entrance__block">
                <div class="entrance__logo"><img src="/img/logo.svg" alt=""></div>
                <div class="entrance__block-form">
                    @yield('content')
                    
                </div>
            </div>
        </section>

        <script src="https://cdn.jsdelivr.net/npm/jquery@3.2.1/dist/jquery.min.js" type="text/javascript"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery.maskedinput@1.4.1/src/jquery.maskedinput.js" type="text/javascript"></script>
        <style type="text/css">
            .login-container>p {
                display: none;
            }
            .login-container label {
                color: #fff;
                font-size: 12px;
                padding-bottom: 5px;
            }
            .login-btn {
                background-color: #2f8cff;
            }
            .signin {
                
                font-size: 14px;
                color: #fff;
            }
            .controls {
                display: flex;
                align-content: center;
            }
            .controls input,.controls label {
                margin: 0;
                padding-top: 0;
                padding-bottom: 0;
            }
            .remember-me-text {
                display: flex;
                align-items: center;
            }
            .btn.disabled, .signingin.disabled{
                pointer-events: none;
                opacity: .7;
                position: relative;
                text-indent: -99999px;
                display: inline-block;
            }
            .btn.disabled::after, .signingin.disabled::after {
              content: "";
              position: absolute;
              width: 16px;
              height: 16px;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              margin: auto;
              border: 4px solid transparent;
              border-top-color: #ffffff;
              border-radius: 50%;
              animation: button-loading-spinner 1s ease infinite;
            }
            @keyframes button-loading-spinner {
              from {
                transform: rotate(0turn);
              }

              to {
                transform: rotate(1turn);
              }
            }
        </style>
    </main>

@yield('post_js')
</body>
</html>
