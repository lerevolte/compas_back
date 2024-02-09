@extends('layouts.main')
@section('title')
Зоны достижения цели
@endsection
@section('h1')
    <h1 class="my-0 h1">Зоны достижения цели</h1>
@endsection
@section('subnav')
    <div class="t-nav mt-4">
        <div class="btn-group mb-3" role="group" aria-label="Nav">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Пользователи</a>
            <a href="{{ route('settings.roles') }}" class="btn btn-outline-secondary">Настройка ролей</a>
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary active">Административные настройки</a>
            <a href="{{ route('balance') }}" class="btn btn-outline-secondary">Тариф</a>
        </div>
    </div>
@endsection
@section('content')
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    @php
    $items = \App\Models\Role::orderBy('sort')->get();
    @endphp
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>
    <form class="js-change-role form-edit-add" role="form"
                              action="{{ route('settings.update', $account->id) }}"
                              method="POST" enctype="multipart/form-data" autocomplete="off">
        <!-- CSRF TOKEN -->
        <input type="hidden" name="_method" value="PUT">
                            {{ csrf_field() }}
    <div class="rounded-1 bg-white border t">
        <div class="t-body " data-model="roles">
            <div class="row g-0">
                <div class="col-lg-3 border-end">
                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Административные настройки</h6>
                    </div>
                    <div class="c-body p-0">
                        <ul class="storages-list c-drag-list list-unstyled mb-0 ui-sortable">
                            <li class="side-list__item ">
                                <div class="position-relative ">
                                    <a href="/settings">Настройки карты</a>
                                </div>
                            </li>
                            <li class="side-list__item active">
                                <div class="position-relative active">
                                    <a href="javascript:;">Зоны достижения адреса</a>
                                </div>
                            </li>
                            <li class="side-list__item">
                                <div class="position-relative">
                                    <a href="/map_zones">Настройка зон</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    <div class="c-body ">
                        
                            

                            <div class="row panel-body">
                                <div class="col-lg-6">
                                    <ul class="position-relative row list-unstyled c-list mb-4 pt-4 px-4">
                                        <li class="col-lg-12">
                                            <div class="position-relative d-flex align-items-center mb-1">
                                                <div class="label">
                                                    Радиус зоны точки при план-факт контроле, метров
                                                </div>
                                            </div>
                                            <div class="row g-2 flex-nowrap">
                                                <div class="col-lg-12">
                                                    <div class="position-relative">
                                                        <input name="map_zone_radius" type="text" class="form-control" value="{{ $account->map_zone_radius }}">
                                                    </div>                       
                                                </div>
                                            </div>
                                        </li>
                                        <!-- <li class="col-lg-12">
                                            <div class="position-relative d-flex align-items-center mb-1">
                                                <div class="label">
                                                    Считать обслуживание, если машина более 5-ти минут находится в радиусе, метров 
                                                </div>
                                            </div>
                                            <div class="row g-2 flex-nowrap">
                                                <div class="col-lg-12">
                                                    <div class="position-relative">
                                                        <input name="map_zone_car_radius" type="text" class="form-control" value="{{ $account->map_zone_car_radius }}">
                                                    </div>                       
                                                </div>
                                            </div>
                                        </li> -->
                                        <li class="col-lg-12">
                                            <div class="position-relative d-flex align-items-center mb-1">
                                                <div class="label">
                                                    Считать остановку, если машина более 5-ти минут находится в радиусе, метров 
                                                </div>
                                            </div>
                                            <div class="row g-2 flex-nowrap">
                                                <div class="col-lg-12">
                                                    <div class="position-relative">
                                                        <input name="map_stop_car_radius" type="text" class="form-control" value="{{ $account->map_stop_car_radius }}">
                                                    </div>                       
                                                </div>
                                            </div>
                                        </li>
                                        <!-- <li class="col-lg-12">
                                            <div class="position-relative d-flex align-items-center mb-1">
                                                <div class="label">
                                                    Время остановки, мин
                                                </div>
                                            </div>
                                            <div class="row g-2 flex-nowrap">
                                                <div class="col-lg-12">
                                                    <div class="position-relative">
                                                        <input name="map_stop_time" type="text" class="form-control" value="{{ $account->map_stop_time }}">
                                                    </div>                       
                                                </div>
                                            </div>
                                        </li> -->
                                    </ul>
                                </div>
                                <div class="col-lg-6 pt-4 px-4 mb-4">
                                    <div class="label mb-1 d-flex">
                                        Карта
                                    </div>
                                    <div class="position-relative">
                                        
                                        <div id="map" style="height: 300px;">
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                    </div>
                </div>
            </div>

        </div>

    </div>
    <div class="js-save-panel-settings save-panel" style="display: none;left:0">
        <button type="submit" class="js-submit-settings blue-btn">Сохранить</button>
        <button class="gray-btn js-reset-fields-settings">Отменить</button>
    </div>
    </form>
    <style type="text/css">
        #map {
            margin-bottom: 12px;
        }
    </style>
    <script type="text/javascript">
        var myMap, myCircle, myCircle2;
        function map_init() {
            myMap = new ymaps.Map("map", {
                center: [55.75, 37.645],
                zoom: 14,
                controls: []
            });
            //myMap.geoObjects.events.add('click', function (e) {

            

            // var AllParaPoint = [[55.772, 37.632], [55.76, 37.64]];

            // myMap.geoObjects.add(new ymaps.Polyline(AllParaPoint));

            //});
            @if($account->map_zone_radius)
            myCircle = new ymaps.Circle([
                [55.75, 37.64],
                {{ $account->map_zone_radius }}
            ], {
            }, {
                fillColor: "#1111ff77",
                // Цвет обводки.
                strokeColor: "#0000ff",
            });
            myMap.geoObjects.add(myCircle);
            @endif
            @if(isset($current->latitude) && $current->latitude)
            var defaultMark = new ymaps.Placemark([{{ $current->latitude }},{{ $current->longitude }}]);
                            
            myMap.setCenter([{{ $current->latitude }},{{ $current->longitude }}]);
            myMap.geoObjects.add(defaultMark);
            @elseif(isset($current->address) && $current->address)
            var myGeocoder = ymaps.geocode('{{ $current->address }}');
            myGeocoder.then(function(res) {
                var firstGeoObject = res.geoObjects.get(0), coords = firstGeoObject.geometry.getCoordinates();
                var defaultMark = new ymaps.Placemark(coords);
                myMap.setCenter(coords);
                myMap.geoObjects.add(defaultMark);
            });
            @endif

            @if($account->map_stop_car_radius)
            myCircle2 = new ymaps.Circle([
                [55.75, 37.655],
                {{ $account->map_stop_car_radius }}
            ], {
            }, {
                fillColor: "#ff111177",
                // Цвет обводки.
                strokeColor: "#ff0000",
            });

            myMap.geoObjects.add(myCircle2);
            @endif
            
        }
        $(document).ready(function(){
            $('body').on('change', '.form-control', function(){
                $('.js-save-panel-settings').show();
            });
            $('body').on('change', '[name="map_zone_radius"]', function(){
                myMap.geoObjects.remove(myCircle);
                myCircle = new ymaps.Circle([
                    [55.75, 37.64],
                    $(this).val()
                ], {
                }, {
                    fillColor: "#1111ff77",
                    // Цвет обводки.
                    strokeColor: "#0000ff",
                });
               
                myMap.geoObjects.add(myCircle);
                
            });
            $('body').on('change', '[name="map_stop_car_radius"]', function(){
                myMap.geoObjects.remove(myCircle2);
                myCircle2 = new ymaps.Circle([
                    [55.75, 37.655],
                    $(this).val()
                ], {
                }, {
                    fillColor: "#ff111177",
                    // Цвет обводки.
                    strokeColor: "#ff0000",
                });
                myMap.geoObjects.add(myCircle2);
                
            });
            ymaps.ready(map_init);
        });
    </script>
@endsection
    