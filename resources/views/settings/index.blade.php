@extends('layouts.main')
@section('title')
Административные настройки
@endsection
@section('h1')
    <h1 class="my-0 h1">Административные настройки</h1>
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
                            <li class="side-list__item active">
                                <div class="position-relative active">
                                    <a href="javascript:;">Настройки карты</a>
                                </div>
                            </li>
                            <li class="side-list__item">
                                <div class="position-relative">
                                    <a href="/settings/zones">Зоны достижения адреса</a>
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
                        
                            

                            <div class="panel-body">
                                <ul class="position-relative row list-unstyled c-list mb-4 pt-4 px-4">
                                    <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Главный город
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <input id="address" name="map_city" type="text" class="form-control" value="{{ $account->map_city }}">
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="map_latitude" value="{{ $account->map_latitude }}">
                                                    </div>
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="map_longitude" value="{{ $account->map_longitude }}">
                                                    </div>
                                                </div>                       
                                            </div>
                                        </div>
                                    </li>
                                    <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Используемая карта
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <select name="map_provider" class="js-select form-control">
                                                        <option value="1" @if($account->map_provider == 1) selected @endif>Open Street</option>
                                                        <option value="2" @if($account->map_provider == 2) selected @endif>Яндекс Карты</option>
                                                    </select>
                                                </div>                       
                                            </div>
                                        </div>
                                    </li>
                                    <li class="col-lg-12">
                                        <div class="position-relative d-flex align-items-center mb-1">
                                            <div class="label">
                                                Ключ Яндекс.карт
                                            </div>
                                        </div>
                                        <div class="row g-2 flex-nowrap">
                                            <div class="col-lg-12">
                                                <div class="position-relative">
                                                    <input name="yandex_api_key" type="text" class="form-control" value="{{ $account->yandex_api_key }}">
                                                </div>                       
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            

                    </div>
                </div>
            </div>

        </div>
        <div id="map"></div>
    </div>
    <div class="js-save-panel-settings save-panel" style="display: none;left:0">
        <button type="submit" class="js-submit-settings blue-btn">Сохранить</button>
        <button class="gray-btn js-reset-fields-settings">Отменить</button>
    </div>
    </form>
    <script type="text/javascript">
        var myMap, init_sug = false;
        function map_init() {
            myMap = new ymaps.Map("map", {
                center: [55.76, 37.64],
                zoom: 9,
                controls: []
            });
            var suggestView;
            console.log('init')
            $('body').on('input', '[name="map_city"]', function() {
                if (/[a-zа-яё]/i.test($(this).val())){
                    console.log('SuggestView1')
                    if(!init_sug) {
                        console.log('init_suggest1')
                        init_suggest(suggestView);
                    }
                    $('.ymaps-2-1-79-search__suggest').removeClass('d-none');
                    console.log('ADDRESS')
                } else {
                    $('.ymaps-2-1-79-search__suggest').addClass('d-none');
                    console.log('coordsS')
                }
            });
            $('body').on('change', '.form-control', function(){
                $('.js-save-panel-settings').show();
            });
        }
        function init_suggest(suggestView) {
            console.log('init_suggest')
            if(!suggestView) {
                suggestView = new ymaps.SuggestView('address', {results: 6});
                init_sug = true;
                suggestView.events.add('select', function(item) {
                    var val = $('[name="map_city"]').val();

                    var myGeocoder = ymaps.geocode(val);
                
                    myGeocoder.then(function(res) {
                        var firstGeoObject = res.geoObjects.get(0), coords = firstGeoObject.geometry.getCoordinates();
                        console.log(firstGeoObject)
                        $('[name="map_latitude"]').val(coords[0]);
                        $('[name="map_longitude"]').val(coords[1]);
                        $('[name="map_latitude"]').closest('.js-editable').addClass('active');
                        $('[name="map_longitude"]').closest('.js-editable').addClass('active');
                        $('.js-save-panel-settings').show();
                    });
                });
            }
        }
            
        $(document).ready(function(){

            
            console.log('init мэп')
            ymaps.ready(map_init);
        });
    </script>

@endsection
    