@extends('layouts.main')
@section('title')
{{ $title }}
@endsection
@section('h1')
<h1 class="my-0 h1">{{ $title }}</h1>
@endsection
@section('content')
@php
$settings = get_settings();
$add_perm = $settings['pages']['perms']['add_supplies'] != 'disabled';
$delete_perm = $settings['pages']['perms']['delete_supplies'] != 'disabled';
$write_perm = $settings['pages']['perms']['write_supplies'] != 'disabled';
$is_admin = Auth::user()->isAdmin();
$point_field = $settings['settings']['point_type'];

$point_types = array();
if($details = json_decode($point_field->details, true))
    $point_types = $details['options'] ?? array();
@endphp
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
<script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
<script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>


<div class="d-flex align-items-center justify-content-center @if(count($zones) > 0) d-none @endif" style="background-color:#F6F5F3;min-height: 300px;text-align: center;">
    <div><b>Нет зон</b><br><a href="#addZone" class="link" data-fancybox data-touch="false">Добавить</a></div>
</div>

<div class="rounded-1 bg-white border @if(count($zones) == 0) d-none @endif">
    <div class="t-body " data-model="map_zones">
        <div class="row g-0">
            <div class="col-lg-3 border-end">
                <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                    <a href="/settings" style="font-size: 20px;"><i class="fa fa-angle-left"></i></a>
                    <h6 class="h6 m-0 ms-2" style="flex-grow: 1">{{ $title }}</h6>
                    @if($add_perm)
                    <a href="#addZone" class="link" data-fancybox data-touch="false">Добавить</a>
                    @endif
                </div>
                <div class="c-body p-0">
                    <ul class="c-drag-list list-unstyled mb-0 js-sort">
                        @foreach($zones as $item)
                        <li class="side-list__item d-flex position-relative @if(isset($current->id) && $current->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="map_zones" data-url-template="/map_zones/">
                            <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                            <div class="btn btn-light w-100">
                                <span @if(!$item->name) class="text-gray" @endif >{{ $item->name ?? '(не заполнено)' }}</span>
                                @if($add_perm || $delete_perm)
                                <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                    @if($add_perm)
                                    <li><a class="dropdown-item js-copy-model" data-id="{{ $item->id }}" data-model="map_zones" href="#">Создать копию</a></li>
                                    @endif
                                    @if($delete_perm)
                                    <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="map_zones" href="#"><span class="text-danger">Удалить</span></a></li>
                                    @endif
                                </ul>
                                @endif
                            </div>                                            
                        </li>
                        @endforeach
                        <li class="side-list__item d-none position-relative @if(!count($zones)) active @endif" data-model="map_zones" data-url-template="/map_zones/">                                        
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
                        <a href="javascript:;" class="link js-edit-section" data-model="map_zones">Изменить</a> 
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
                            <div class="col-lg-6">
                                <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
                                    <div class="js-editable active d-none" data-field="map_data">
                                        <input id="map_data" name="map_data" type="text" class="form-control" value="{{ $current->map_data }}">
                                    </div> 
                                    @if($section->visible_fields && count($section->visible_fields))
                                        @foreach($section->visible_fields as $k => $field)
                                            @php
                                                
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
                                            <li class="@if($field->type != 'status')col-lg-12 @endif {{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}">
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
                                                                      <input class="form-check-input js-field-show" type="checkbox" value="{{ $field->visible_always == 1 ? 0 : 1 }}" id="flexCheckDefault{{ $field->field }}" data-model="map_zones" data-field="{{ $field->field }}" data-section="{{ $section->id }}" {{ $field->visible_always ? 'checked' : ''}}>
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
                                <div >
                                    <div class="settings position-relative d-inline-block">
                                        <a class="dropdown-toggle link show me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="true">
                                            Добавить поле
                                        </a>
                                        <ul class="dropdown-menu start-0">
                                            @if($hidden_fields)
                                                @foreach($hidden_fields as $field)
                                                <li><a class="dropdown-item js-field-show" href="#" data-model="map_zones" data-submodel="{{ $type }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
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
                            @if($section->id == 16)
                            <div class="col-lg-6">
                                <div class="label mb-1 d-flex">
                                    Карта
                                </div>
                                <div class="position-relative">
                                    <div class="map-control-wrap">
                                        <!-- <a class="map-control map-control-maps map-control-tools" type="button" href="https://maps.yandex.ru/?text={{ isset($current) ? $current->latitude : '' }}+{{ isset($current) ? $current->longitude : '' }}" target="_blank">
                                            Смотреть в яндекс.картах
                                        </a> -->
                                        
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
                    @if(!count($sections) && $is_admin)
                         <div class="c-body p-4">
                            <div class="settings position-relative d-inline-block">
                                <a class="dropdown-toggle link" href="#addSection" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Создать раздел
                                </a>
                            </div>
                        </div>
                    @endif
                @endif
                </ul>
            </div>
        </div>
    </div>                    
</div>
<div id="addZone" class="fancy-modal" style="overflow: visible;">  
    <form class="form px-5">
        {{ csrf_field() }}
        <h5 class="section-title text-center mb-4">
            Добавить зону
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
            <button type="submit" class="btn btn-primary rounded-1 js-add-model" data-model="map_zones">Добавить</button>
        </div>
    </form>
</div>
@include('blocks.field_update')
@if($is_admin)
    @include('blocks.field_add', ['model' => 'map_zones'])
    @include('blocks.section_add', ['model' => 'map_zones'])
@endif
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
        height: calc(100% - 80px);
    }
</style>
<script type="text/javascript">
    var myMap;
    var myPolygon;


    function init() {
        myMap = new ymaps.Map("map", {
            center: [55.73, 37.75],
            zoom: 10
        }, {
            searchControlProvider: 'yandex#search'
        });

        // for(var z_id in zones)
        // {
        //     var zone = zones[z_id];
        //     var positions = [];

        //     if(zone.POSITIONS)
        //         positions.push(zone.POSITIONS);
        //     if(zone.POSITIONS_IN)
        //         positions.push(zone.POSITIONS_IN);

        //     var polygon = new ymaps.Polygon(positions, {id: zone.ID}, {
        //         fillColor: $('.point_status_rect').css('background-color') ? $('.point_status_rect').css('background-color') : '#000000',
        //         strokeColor: $('.point_status_rect').css('background-color') ? $('.point_status_rect').css('background-color') : '#000000',
        //         strokeOpacity: 0.6,
        //         fillOpacity: 0.3,
        //         strokeWidth: 3,
        //     });
        //     myMap.geoObjects.add(polygon);
        // }

        var color = $('.point_status_rect').css('background-color');

        console.log(color);
        @if($current->map_data)
        var map_data='{{ $current->map_data }}'.match(/[^,]+,[^,]+/g);
        var data = [];
        $.each(map_data, function(i, obj){
            data.push(obj.split(','));
        });
        console.log(data)
        myPolygon = new ymaps.Polygon([data], {}, {
            editorDrawingCursor: "crosshair",
            editorUseAutoPanInDrawing: false,
            editorMaxPoints: 1000,
            fillColor: color,//color,
            fillOpacity: 0.7,
            strokeColor: color,
            strokeWidth: 3
        });
        @else
        myPolygon = new ymaps.Polygon([], {}, {
            editorDrawingCursor: "crosshair",
            editorUseAutoPanInDrawing: false,
            editorMaxPoints: 1000,
            fillColor: color,//color,
            fillOpacity: 0.7,
            strokeColor: color,
            strokeWidth: 3
        });
        @endif
        myMap.geoObjects.add(myPolygon);

        var stateMonitor = new ymaps.Monitor(myPolygon.editor.state);
        stateMonitor.add("drawing", function (newValue) {
            //myPolygon.options.set("strokeColor", newValue ? color : '#0000FF');
        });
        myPolygon.editor.startDrawing();

        
        myPolygon.events.add(['geometrychange'], function (e) {
            var positions = myPolygon && myPolygon.geometry ? myPolygon.geometry.getCoordinates() : [];
            var positions_in = [];

            if(positions[0])
            {
                positions = positions[0];

                if(positions[1])
                    positions_in = positions_in[1];
            }
            else
                positions = [];
            $('[name="map_data"]').val(positions);
            $('.js-save-panel').show();
        });
        $(document).on('change', '[name="color"]', function()
        {
            myPolygon.options.set({
                strokeColor: $('.point_status_rect').css('background-color'),
                fillColor: $('.point_status_rect').css('background-color')
            });
            
        });

    }
    $(document).ready(function(){
        ymaps.ready(init);
        
    });
</script>
@endsection
