@if(!isset(request()->ajax))
@extends('layouts.main')
@section('title')
{{ $title }}
@endsection
@section('h1')
<h1 class="my-0 h1">{{ $title }}</h1>
@endsection
@endif
@section('content')
@php
$settings = get_settings();
$add_perm = $settings['pages']['perms']['add_orders'] != 'disabled';
$delete_perm = $settings['pages']['perms']['delete_orders'] != 'disabled';
$write_perm = $settings['pages']['perms']['write_orders'] != 'disabled';
$is_admin = Auth::user()->isAdmin();
$point_field = $settings['settings']['point_type'];

$point_types = array();
if($details = json_decode($point_field->details, true))
    $point_types = $details['options'] ?? array();
@endphp
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
<script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
@if(!isset(request()->ajax))
<div class="panel border-radius-top d-flex align-items-center border border-bottom-0">
    <div class="navbar navbar-light navbar-expand-lg">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarPanel" aria-controls="navbarPanel" aria-expanded="false" aria-label="Toggle navigation">
            <svg class="icon icon-menu-dots"><use xlink:href="#icon-menu-dots"></use></svg>
        </button>

        <div class="collapse navbar-collapse" id="navbarPanel">
            <ul class="navbar-nav">
                @if(isset($addresses_empty_count) && $addresses_empty_count)
                <li class="nav-item"><a href="/addresses/" class="nav-link @if(!$type) active @endif">не выбрано</a></li>
                @endif
                @foreach($point_types as $val => $name)
                <li class="nav-item"><a href="/addresses/{{ $val }}" class="nav-link @if($type == $val) active @endif">{{ $name }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif
<div class="d-flex align-items-center justify-content-center @if(count($addresses) > 0) d-none @endif" style="background-color:#F6F5F3;min-height: 300px;text-align: center;">
    <div><b>Нет адресов</b><br><a href="#addAddress" class="link" data-fancybox data-touch="false">Добавить</a></div>
</div>

<div class="rounded-1 bg-white border @if(count($addresses) == 0) d-none @endif">
    <div class="t-body " data-model="addresses">
        <div class="row g-0">
            <div class="col-lg-3 border-end">
                <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                    <h6 class="h6 m-0">{{ $title }}</h6>
                    @if($add_perm)
                    <a href="#addAddress" class="link" data-fancybox data-touch="false">Добавить</a>
                    @endif
                </div>
                <div class="c-body p-0">
                    <ul class="c-drag-list list-unstyled mb-0 js-sort">
                        @foreach($addresses as $item)
                        <li class="side-list__item d-flex position-relative @if(isset($current->id) && $current->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="addresses" data-url-template="/addresses/edit/">
                            <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                            <div class="btn btn-light w-100">
                                <span @if(!$item->name) class="text-gray" @endif >{{ $item->name ?? '(не заполнено)' }}</span>
                                @if($add_perm || $delete_perm)
                                <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                    @if($add_perm)
                                    <li><a class="dropdown-item js-copy-model" data-id="{{ $item->id }}" data-model="addresses" href="#">Создать копию</a></li>
                                    @endif
                                    @if($delete_perm)
                                    <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="addresses" href="#"><span class="text-danger">Удалить</span></a></li>
                                    @endif
                                </ul>
                                @endif
                            </div>                                            
                        </li>
                        @endforeach
                        <li class="side-list__item d-none position-relative @if(!count($addresses)) active @endif" data-model="addresses" data-url-template="/addresses/edit/">                                        
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9 carrier-content">
                <ul class="list-unstyled js-sort-t">
                @if(isset($sections))
                    @foreach($sections as $section)
                    <li>
                    <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
                        <div class="position-relative me-auto d-flex align-items-center">
                            @if($write_perm)
                            <span class="btn btn-drag btn-drag-section btn-xs p-0 text-muted mx-2">
                                <svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg>
                            </span>
                            @endif
                            <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                            @if($write_perm)
                            <span class="js-edit-section-title mx-2" data-id="{{ $section->id }}"><img src="/images/ico_pen.svg"></span>
                            @endif
                        </div>
                        @if($write_perm)
                        <a href="javascript:;" class="link js-edit-section" data-model="addresses" data-submodel="{{ $type }}">Изменить</a> 
                        @endif  
                        <div class="settings position-relative">
                            @if($write_perm)
                            <a class="dropdown-toggle btn p-0 text-secondary ms-3" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                                <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                            </a> 

                            <ul class="dropdown-menu" aria-labelledby="a2" style="">
                                <li><a class="dropdown-item js-section-hide" href="#" data-section="{{ $section->id }}">Скрыть</a></li>
                                <li>
                                    <a class="dropdown-item js-section-delete" href="#" data-section="{{ $section->id }}"><span class="text-danger">Удалить</span></a>
                                </li>
                            </ul>
                            @endif
                        </div>
                    </div>
                    <div class="c-body p-4">
                        <div class="row mb-2 justify-content-between">
                            <div class="js-editable active">
                                <input type="hidden" name="latitude" value="{{ isset($current) ? $current->latitude : '' }}">
                            </div>
                            <div class="js-editable active">
                                <input type="hidden" name="longitude" value="{{ isset($current) ? $current->longitude : '' }}">
                            </div>
                            <div class="col-lg-6">
                                <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
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
                                            <li class=" @if($field->field != 'payment_status' && $field->type != 'status')col-lg-12 @endif{{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings['orders']['perms'][$field->field]) && $settings['orders']['perms'][$field->field]['write'] == 'disabled') data-blocked="true" @endif @if($current->route_id && $field->field == 'date_delivery_status' || !$current->route_id && $field->field == 'point_status') data-disabled="true" @endif>
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
                                                                      <input class="form-check-input js-field-show" type="checkbox" value="{{ $field->visible_always == 1 ? 0 : 1 }}" id="flexCheckDefault{{ $field->field }}" data-model="addresses" data-field="{{ $field->field }}" data-section="{{ $section->id }}" {{ $field->visible_always ? 'checked' : ''}}>
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
                                                @if($field->field == 'address')
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
                                                @endif
                                            </li>
                                            
                                        @endforeach

                                    @else
                                    <li></li>
                                    @endif
                                </ul>
                                @if($is_admin)
                                <div >
                                    <div class="settings position-relative d-inline-block">
                                        <a class="dropdown-toggle link show me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="true">
                                            Добавить поле
                                        </a>
                                        <ul class="dropdown-menu start-0">
                                            @if($hidden_fields)
                                                @foreach($hidden_fields as $field)
                                                <li><a class="dropdown-item js-field-show" href="#" data-model="addresses" data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
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
<div id="addAddress" class="fancy-modal" style="overflow: visible;">  
    <form class="form px-5">
        {{ csrf_field() }}
        <h5 class="section-title text-center mb-4">
            Добавить задачу
        </h5>
        <ul class="list-unstyled c-list mb-2">
            <li>
                <div class="label text-dark mb-1">
                    Название
                </div>

                <input type="text" name="name" value="" class="form-control">
            </li>
        </ul>
        <div class="text-center">
            <button type="submit" class="btn btn-primary rounded-1 js-add-model" data-model="addresses" data-type="addresses">Добавить</button>
        </div>
    </form>
</div>
@include('blocks.field_update')
@if($is_admin)
    @include('blocks.field_add', ['model' => 'addresses'])
    @include('blocks.section_add', ['model' => $type])
@endif
<style type="text/css">
    @if(isset(request()->ajax))
    .project {
        padding-left: 0;
    }
    @endif
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
        height: calc(100% - 80px);
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
@endsection
