@extends('layouts.main')
@section('title')
{{ $title }}
@endsection
@section('h1')
<h1 class="my-0 h1">{{ $title }}</h1>
@endsection
@php
$settings = get_settings();
$is_admin = Auth::user()->isAdmin();
$write_perm = $settings['pages']['perms']['write_addr'] != 'disabled';
@endphp
@section('content')

<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
<script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
<link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>
<div class="panel border-radius-top d-flex align-items-center border border-bottom-0">
    <div class="navbar navbar-light navbar-expand-lg">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarPanel" aria-controls="navbarPanel" aria-expanded="false" aria-label="Toggle navigation">
            <svg class="icon icon-menu-dots"><use xlink:href="#icon-menu-dots"></use></svg>
        </button>

        <div class="collapse navbar-collapse" id="navbarPanel">
            <ul class="navbar-nav">
                @if($addresses_empty_count)
                <li class="nav-item"><a href="/addr/" class="nav-link @if(!$type) active @endif">не выбрано</a></li>
                @endif
                @foreach($point_types as $val => $name)
                <li class="nav-item"><a href="/addr/{{ $val }}" class="nav-link @if($type == $val) active @endif">{{ $name }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
<div class="border-radius-bottom bg-white border">
    
    <div class="t-body " data-model="orders" data-submodel="{{ $type }}">
        <div class="row g-0">
            <div class="col-lg-3 border-end">
                <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                    <h6 class="h6 m-0">{{ $title }}</h6>
                    <a href="#addModal" class="link" data-fancybox data-touch="false">Добавить</a>
                </div>
                <div class="c-body p-0">
                    <ul class="c-drag-list list-unstyled mb-0 js-sort">
                        @foreach($addresses as $item)
                        <li class="side-list__item d-flex position-relative @if(isset($current->id) && $current->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="orders" data-url-template="/addr/show/" data-params="{{ !$type ? '?all=y' : ''}}">
                            <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                            <div class="btn btn-light w-100">
                                <span @if(!$item->store_name) class="text-gray" @endif >{{ $item->store_name ?? '(не заполнено)' }}</span>
                                <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                    <li><a class="dropdown-item js-create-task" data-id="{{ $item->id }}" data-model="orders" href="#">Создать задачу</a></li>
                                    <li><a class="dropdown-item js-copy-model" data-id="{{ $item->id }}" data-model="orders" href="#">Создать копию</a></li>
                                    <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="orders" href="#"><span class="text-danger">Удалить</span></a></li>
                                </ul>
                            </div>                                            
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="col-lg-9 carrier-content">
                
                <div class="js-editable active">
                    <input type="hidden" name="latitude" value="{{ isset($current) ? $current->latitude : '' }}">
                </div>
                <div class="js-editable active">
                    <input type="hidden" name="longitude" value="{{ isset($current) ? $current->longitude : '' }}">
                </div>

                <ul class="list-unstyled js-sort-t">
                @if(isset($sections))
                    @foreach($sections as $section)
                    <li>
                    <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
                        <div class="position-relative me-auto d-flex align-items-center">
                            <span class="btn btn-drag btn-drag-section btn-xs p-0 text-muted mx-2">
                                <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
                            </span>
                            <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                            <span class="js-edit-section-title mx-2" data-id="{{ $section->id }}"><img src="/images/ico_pen.svg"></span>
                        </div>
                        <a href="#" class="link js-edit-section" data-model="orders" data-submodel="{{ $type }}">Изменить</a>   
                        <div class="settings position-relative">
                            <a class="dropdown-toggle btn p-0 text-secondary ms-3" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                            </a> 

                            <ul class="dropdown-menu" aria-labelledby="a2" style="">
                                <li><a class="dropdown-item js-section-hide" href="#" data-section="{{ $section->id }}">Скрыть</a></li>
                                <li>
                                    <a class="dropdown-item js-section-delete" href="#" data-section="{{ $section->id }}"><span class="text-danger">Удалить</span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="c-body p-4">
                        <div class="row mb-2 justify-content-between">
                            <div class="col-lg-6">
                                <ul class="position-relative row list-unstyled c-list mb-2 js-sort-form" data-section="{{ $section->id }}">
                                    @if($section->visible_fields && count($section->visible_fields))
                                        @foreach($section->visible_fields as $k => $field)
                                            @php
                                                /*if($field->submodel == 'points')
                                                    continue;*/
                                                if(isset($settings['orders']['perms'][$field->field]) && $settings['orders']['perms'][$field->field]['read'] == 'disabled')
                                                    continue;
                                                $visible_field = false;
                                                if($field->type == 'text_group') {
                                                    $subfields = \App\Models\Field::getByGroup($field->id);
                                                    $subfield_names = array();
                                                    $subfield_values = array();
                                                    
                                                    foreach($subfields as $subfield) {
                                                        $subfield_names[] = $subfield->field;
                                                        $subfield_values[] = $current->{$subfield->field};
                                                        if($current->{$subfield->field}) {
                                                            $visible_field = true;
                                                        }
                                                    }
                                                }
                                                
                                            @endphp
                                            <li class=" @if($field->type != 'status')col-lg-12 @endif{{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings['orders']['perms'][$field->field]) && $settings['orders']['perms'][$field->field]['write'] == 'disabled') data-blocked="true" @endif @if($current->route_id && $field->field == 'date_delivery_status' || !$current->route_id && $field->field == 'point_status') data-disabled="true" @endif>
                                                <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                    @if($write_perm)
                                                    <span class="btn js-edit-position me-2 btn-drag btn-drag-field btn-xs p-0 text-muted">
                                                        <svg class="icon icon-linedot"><use xlink:href="#icon-linedot"></use></svg>
                                                    </span>
                                                    @endif
                                                    <div class="label">
                                                        {{ $field->display_name }}
                                                    </div>
                                                    @if($write_perm)
                                                    <div class="settings position-absolute" style="right:0">
                                                        <a class="dropdown-toggle btn p-0 text-secondary" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                                            <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                                        </a>

                                                        <ul class="dropdown-menu" >
                                                            <li><a class="dropdown-item js-field-update-btn" data-field="{{ $field->id }}" href="#updateField" data-fancybox data-touch="false">Настроить</a></li>
                                                            <li>
                                                                <div class="dropdown-item">
                                                                    <div class="form-check form-check-xs mb-0">
                                                                      <input class="form-check-input js-field-show" type="checkbox" value="{{ $field->visible_always == 1 ? 0 : 1 }}" id="flexCheckDefault{{ $field->field }}" data-model="orders" data-field="{{ $field->field }}" data-section="{{ $section->id }}" {{ $field->visible_always ? 'checked' : ''}}>
                                                                      <label class="form-check-label " for="flexCheckDefault{{ $field->field }}">
                                                                        Показывать всегда
                                                                      </label>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            <li><a class="dropdown-item js-field-hide" data-field="{{ $field->field }}" href="#">Скрыть</a></li>
                                                            <li><a class="dropdown-item js-field-destroy" data-field="{{ $field->id }}" href="#">Удалить</a></li>
                                                        </ul>
                                                    </div>
                                                    @endif
                                                </div>
                                                @if($field->type == 'text_group')
                                                    @include('fields.values.'.$field->type, ['subfields' => $subfields, 'subfield_names' => $subfield_names, 'subfield_values' => $subfield_values, 'current' => $current])
                                                @else
                                                    @include('fields.values.'.$field->type, ['field' => $field, 'current' => $current])
                                                @endif
                                            </li>
                                        @endforeach

                                    @else
                                    <li></li>
                                    @endif
                                </ul>
                                @if($is_admin)
                                <div class="pt-1">
                                    <div class="settings position-relative d-inline-block">
                                        <a class="dropdown-toggle link show me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="true">
                                            Добавить поле
                                        </a>
                                        <ul class="dropdown-menu start-0">
                                            @if($hidden_fields)
                                                @foreach($hidden_fields as $field)
                                                <li><a class="dropdown-item js-field-show" href="#" data-model="orders" data-submodel="{{ $type }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
                                                @endforeach
                                            @endif
                                            <li>
                                                <a class="dropdown-item" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false"><span class="text-secondary">Создать свое поле</span></a>
                                            </li>
                                        </ul>
                                        <a class="dropdown-toggle link me-2 js-add-field-section" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Создать поле
                                        </a> 
                                        <a class="dropdown-toggle link" href="#addSection" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Создать раздел
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @if($section->id == 5)
                            <div class="col-lg-6">
                                <div class="label mb-1 d-flex">
                                    Карта
                                </div>
                                <div class="position-relative">
                                    <div class="map-control-wrap">
                                        <a class="map-control map-control-maps map-control-tools" type="button" href="https://maps.yandex.ru/?text={{ isset($current) ? $current->latitude : '' }}+{{ isset($current) ? $current->longitude : '' }}" target="_blank">
                                            Смотреть в яндекс.картах
                                        </a>
                                        
                                    </div>
                                    
                                    <div id="map" style="height: 300px;">
                                        
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    </li>
                    @endforeach
                @endif
                </ul>
            </div>
        </div>
    </div>                    
</div>
@include('blocks.field_update')
@if($is_admin)
    @include('blocks.field_add', ['model' => 'orders', 'submodel' => $type])
    @include('blocks.section_add', ['model' => $type])
@endif
<div id="addModal" class="fancy-modal">  
    <form class="form px-5">
        {{ csrf_field() }}
        @if($type == 'warehouses')
        <input type="hidden" name="is_store" value="1">
        @elseif($type == 'supplies')
        <input type="hidden" name="is_supply" value="1">
        @elseif($type == 'tc')
        <input type="hidden" name="is_tc" value="1">
        @elseif($type == 'addresses')
        <input type="hidden" name="is_address" value="1">
        @else
            @if($type == 1)
            <input type="hidden" name="is_store" value="1">
            @elseif($type == 3)
            <input type="hidden" name="is_tc" value="1">
            @elseif($type == 2 || $type == 5)
            <input type="hidden" name="is_address" value="1">
            @endif
            <input type="hidden" name="{{ $point_field }}" value="{{ $type }}">
        @endif
        <h5 class="section-title text-center mb-4">
            Добавить адрес
        </h5>
        
        <div class="mb-2">
            <div class="position-relative mb-3">
                <label for="#" class="label text-dark mb-1">
                    Название
                </label>
                <input type="text" name="store_name" value="" class="form-control">
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary rounded-1 js-add-model" data-model="orders" data-type="{{ $type }}">Сохранить</button>
        </div>
    </form>
</div>
<style type="text/css">
    .js-editable {
        min-height: 0;
    }
    .label.d-flex {
        min-height: 24px;
        display: flex;
        align-items: end;
    }
    .ymaps-2-1-79-map-copyrights-promo {
        display: none!important;
    }
    .js-editable .settings {
        display: none;
    }
    div[data-field="comment"] {
        font-weight: normal;
    }
    .page-content {
        height: calc(100% - 120px);
    }
</style>
<script type="text/javascript">
    var myMap, init_sug = false;
    function map_init() {
        myMap = new ymaps.Map("map", {
            center: [55.76, 37.64],
            zoom: 9,
            controls: []
        });
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
        
    }
    function init_suggest(suggestView) {
        if(!suggestView) {
            suggestView = new ymaps.SuggestView('address', {results: 6});
            init_sug = true;
            suggestView.events.add('select', function(item) {
                var val = $('#address').val();

                var myGeocoder = ymaps.geocode(val);
            
                myGeocoder.then(function(res) {
                    var firstGeoObject = res.geoObjects.get(0), coords = firstGeoObject.geometry.getCoordinates();
                    if(defaultMark)
                        myMap.geoObjects.remove(defaultMark);
                    var defaultMark = new ymaps.Placemark(coords);
                    myMap.setCenter(coords);
                    myMap.geoObjects.add(defaultMark);
                    $('[name="latitude"]').val(coords[0]);
                    $('[name="longitude"]').val(coords[1]);
                    $('[name="latitude"]').closest('.js-editable').addClass('active');
                    $('[name="longitude"]').closest('.js-editable').addClass('active');
                });
            });
        }
    }
    function init() {
        var suggestView;
            $('body').on('input', '[name="address"]', function() {
                if (/[a-zа-яё]/i.test($(this).val())){
                    console.log('SuggestView1')
                    if(!init_sug)
                        init_suggest(suggestView);
                    $('.ymaps-2-1-79-search__suggest').removeClass('d-none');
                    console.log('ADDRESS')
                } else {
                    $('.ymaps-2-1-79-search__suggest').addClass('d-none');
                    console.log('coordsS')
                }
            });
            $('body').on('focus', '[name="address"]', function() {
                if (/[a-zа-яё]/i.test($(this).val())){
                    console.log('SuggestView')
                    if(!init_sug)
                        init_suggest(suggestView);
                    $('.ymaps-2-1-79-search__suggest').removeClass('d-none');
                    console.log('ADDRESS');

                } else {
                    $('.ymaps-2-1-79-search__suggest').addClass('d-none');
                    console.log('coordsS')
                }
            });
            $('body').on('keyup', '[name="address"]', function() {
                if($(this).val().length >= 3) {
                    var val = $(this).val();
                    myMap.geoObjects.removeAll();
                    var myGeocoder = ymaps.geocode(val, { kind: 'locality' });
                    myGeocoder.then(function(res) {
                        
                        var firstGeoObject = res.geoObjects.get(0), coords = firstGeoObject.geometry.getCoordinates();
                        var val = $('[name="address"]').val();
                        if(defaultMark)
                            myMap.geoObjects.remove(defaultMark);
                        var defaultMark = new ymaps.Placemark(coords);
                        
                        myMap.setCenter(coords);
                        myMap.geoObjects.add(defaultMark);
                        console.log(coords[0]);
                        $('[name="latitude"]').val(coords[0]);
                        $('[name="longitude"]').val(coords[1]);
                        $('[name="latitude"]').closest('.js-editable').addClass('active');
                        $('[name="longitude"]').closest('.js-editable').addClass('active');
                    });
                }
            
            });
    }
    $(document).ready(function(){
        ymaps.ready(map_init, init);
        
    });
</script>
<style type="text/css">
    .ymaps-2-1-79-search__suggest {
        display: none;
    }
</style>
@endsection
