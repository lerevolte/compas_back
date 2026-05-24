@if(!isset(request()->ajax))
@extends('layouts.main')

@section('title')
Изменить заказ
@endsection

@endif
@section('content')
@php
$settings = get_settings();
$is_admin = $settings['is_admin'] ?? Auth::user()->isAdmin();
$write_perm = isset($settings['pages']['perms']['write_'.$slug]) && $settings['pages']['perms']['write_'.$slug] != 'disabled' || $is_admin;
@endphp
@section('h1')
<h1 class="my-0 h1">
    Создать {{ $entity->display_name_singular }}
</h1>
@endsection
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
    .ymaps-2-1-79-gototech {
        display: none!important;
    }
    .js-editable .settings {
        display: none;
    }
    div[data-field="comment"] {
        font-weight: normal;
    }
    .search-products {
        background: #fff;
        box-shadow: 0 0 4px 0 rgb(0 0 0 / 23%), 0 5px 6px 0 rgb(0 0 0 / 18%);
    }
    .search-product-item {
        cursor: pointer;
        min-width: 282px;
    }
    .search-product-item:hover {
        background: rgba(0, 0, 0, .2);
    }
    .products-sum {
        background: #f6f5f3;
        padding: 15px;
        float: right;
        width: 210px;
    }
    .products-sum__row {
        display: flex;
        justify-content: space-between;
    }
    .products-sum__label {
        font-size: 14px;
        color: #8f8f8f;
    }
    .products-sum__value {
        font-size: 14px;
        font-weight: 600;
        color: #000;
    }
</style>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
<script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
<link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>

<div class="rounded-1 bg-white border">
    <div class="panel d-flex align-items-center border-0 ">
        <div class="navbar navbar-light navbar-expand-lg">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarPanel" aria-controls="navbarPanel" aria-expanded="false" aria-label="Toggle navigation">
                <svg class="icon icon-menu-dots"><use xlink:href="#icon-menu-dots"></use></svg>
            </button>

            <div class="collapse navbar-collapse" id="navbarPanel">
                <ul class="navbar-nav">
                    <li class="nav-item"><a data-toggle="tab" href="#main" class="nav-link active">Информация</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="t-body " data-model="{{ $slug }}">
        <div class="row g-0">
            <div id="main" class="tab-content">
                <div class="col-lg-12 carrier-content">
                        <input type="hidden" name="create" value="Y">
                        {{ csrf_field() }}
                        <ul class="list-unstyled js-sort-t">
                        @if(isset($sections))
                            @foreach($sections as $section)
                            <li>
                            <div class="c-top pe-2 bg-light border-top border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
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
                                <a href="javascript:;" class="link js-edit-section" data-model="{{ $slug }}">Изменить</a>   
                                <div class="settings position-relative">
                                    <a class="dropdown-toggle btn p-0 text-secondary ms-3" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                    </a> 

                                    <ul class="dropdown-menu" aria-labelledby="a2" style="">
                                        <li><a class="dropdown-item js-section-hide" href="javascript:;" data-section="{{ $section->id }}">Скрыть</a></li>
                                        <li>
                                            <a class="dropdown-item js-section-delete" href="javascript:;" data-section="{{ $section->id }}"><span class="text-danger">Удалить</span></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="c-body p-4">
                                <div class="row mb-2 justify-content-between">
                                    <div class="col-lg-6">
                                        <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
                                            @if($section->visible_fields && count($section->visible_fields))
                                                @foreach($section->visible_fields as $k => $field)
                                                    @php
                                                        if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled')
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
                                                    <li class=" @if($field->type != 'status')col-lg-12 @endif{{ !$field->visible_always && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] == 'disabled') data-blocked="true" @endif>
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
                                                            <div class="settings position-absolute" style="right:0;">
                                                                <a class="dropdown-toggle btn p-0 text-secondary" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                                                    <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                                                </a>

                                                                <ul class="dropdown-menu" >
                                                                    <li><a class="dropdown-item js-field-update-btn" data-field="{{ $field->id }}" href="#updateField" data-fancybox data-touch="false">Настроить</a></li>
                                                                    <li>
                                                                        <div class="dropdown-item">
                                                                            <div class="form-check form-check-xs mb-0">
                                                                              <input class="form-check-input js-field-show" type="checkbox" value="{{ $field->visible_always == 1 ? 0 : 1 }}" id="flexCheckDefault{{ $field->field }}" data-model="{{ $slug }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}" {{ $field->visible_always ? 'checked' : ''}}>
                                                                              <label class="form-check-label " for="flexCheckDefault{{ $field->field }}">
                                                                                Показывать всегда
                                                                              </label>
                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                    <li><a class="dropdown-item js-field-hide" data-field="{{ $field->field }}" data-model="supplies" href="javascript:;">Скрыть</a></li>
                                                                    <li><a class="dropdown-item js-field-destroy" data-field="{{ $field->id }}" href="javascript:;">Удалить</a></li>
                                                                </ul>
                                                            </div>
                                                            @endif
                                                        </div>
                                                        
                                                        @if($field->type == 'text_group')
                                                            <div class="js-editable" data-field="{{ implode(',', $subfield_names) }}" data-value="{{ implode(',', $subfield_values) }}" data-type="multiple_input">
                                                                @if(request()->edit || request()->create)
                                                                    @include('fields.show.multipletext', ['field_data' => $field, 'field' => implode(',', $subfield_names), 'value' => implode(',', $subfield_values), 'model' => $slug ])
                                                                @else
                                                                    <div class="row g-2 flex-nowrap">
                                                                        @foreach($subfields as $subfield)
                                                                        <div class="col-4">
                                                                            <div class="label text-secondary">
                                                                                {{ $subfield->display_name }}
                                                                            </div>
                                                                            <div class="position-relative">
                                                                                {!! $current->{$subfield->field} ?? '<span class="empty-val">не заполнено</span>' !!}
                                                                            </div>
                                                                        </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            @if(request()->edit && $field->type != 'status' || request()->create && $field->type != 'status')
                                                                <div class="@if($field->field != 'number') js-editable @endif active"  data-field="{{ $field->field }}" data-type="{{ $field->type }}" data-value="{{ $current->{$field->field} }}">
                                                                @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $current->{$field->field} ])
                                                                </div>
                                                            @else
                                                                @include('fields.values.'.$field->type, ['field' => $field, 'current' => null])
                                                            @endif
                                                        @endif
                                                        @if($field->field == 'address')
                                                        <div class="label d-flex" style="margin-top: 12px;">
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
                                                <a class="dropdown-toggle link show me-2" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
                                                    Добавить
                                                </a>
                                                <ul class="dropdown-menu start-0">
                                                    @if($hidden_fields)
                                                        @foreach($hidden_fields as $field)
                                                        <li><a class="dropdown-item js-field-show" href="javascript:;" data-model="{{ $slug }}" data-submodel="{{ $type }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
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
</div>
<div class="js-save-panel-products save-panel" style="display: none;left:0">
    <button type="submit" class="js-submit-products blue-btn">Сохранить</button>
    <button class="gray-btn js-reset-fields-products">Отменить</button>
</div>
@if($write_perm)
    @include('blocks.field_update')
    @if($is_admin)
        @include('blocks.field_add', ['model' => $slug])
        @include('blocks.section_add', ['model' => $slug])
    @endif
@endif

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
        $('body').on('click', '[data-toggle="tab"]', function(){
            $('[data-toggle="tab"]').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').hide();
            $($(this).attr('href')).show();
        });
        
    });
</script>
@endsection
