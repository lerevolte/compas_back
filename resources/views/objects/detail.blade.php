@if(!isset(request()->ajax))
@extends('layouts.main')

@section('title')
Изменить
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
    @if($current->replic_num && $current->replic_num_split)
        <span class="replic-num replic-status-4">{{ $current->replic_num }}</span>-<span class="replic-num">{{ $current->replic_num_split }}</span>-{{ $current->store_name }}
    @elseif($current->replic_num)
        <span class="replic-num replic-status-4">{{ $current->replic_num }}</span>-{{ $current->store_name }}
    @elseif($current->replic_num_split)
        <span class="replic-num">{{ $current->replic_num_split }}</span>-{{ $current->store_name }}
    @elseif($current->store_name)
        {{ $current->store_name }}
    @else
        {{ $current->name }}
    @endif
    </h1>
    <div class="btn-group ms-lg-auto btn-group-add">
        <button type="button" class="btn btn-primary ps-2">
            <span class="ms-2">Создать на основании</span>
        </button>
        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="">
            <li><a class="dropdown-item js-add-relation-entity" data-id="{{ $current->id }}" data-parent="{{ $slug }}" data-model="orders" href="#">Задача</a></li>
             <li><a class="dropdown-item js-add-relation-entity" data-id="{{ $current->id }}" data-parent="{{ $slug }}" data-model="journal_records" href="#">Ремонт и тех. обслуживание</a></li>
            
        </ul>
    </div>
@endsection
@section('scripts')
@if(isset($settings['account']['yandex_api_key']))
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ $settings['account']['yandex_api_key'] }}&lang=ru_RU" type="text/javascript">
</script>
@else
<script src="https://api-maps.yandex.ru/2.1/?apikey=ef7607ff-665a-4e98-a65b-c73d97c69005&lang=ru_RU" type="text/javascript">
</script>
@endif
<script src="{{ asset('js/main.js?v=') }}<?=random_int(1, 20000)?>"></script>

@endsection
<style type="text/css">
    .search-products {
        background: #fff;
        border-radius: 5px;
    }
    .products-sum {
        float: right;
    }
    .products-sum__row {
        display: flex;
    }
    .js-sort-products td {
        vertical-align: middle;
    }
    @if(isset(request()->ajax))
    .project, .page {
        padding-left: 0;
        margin-left: 0;
    }
    .header {
        padding-top: 0;
        margin: 0;
    }
    @else
    .header {
        margin: 0 0 0 230px;
        padding-top: 0;
    }
    @endif
    .js-edit-section-title-input {
        border: none;

    }
    @if($order_statuses)
        @foreach($order_statuses as $status)
        .num-deals__{{ $status->id }} {
            color: {{ $status->color }};
        }
        @endforeach
    @endif
</style>
@if($slug == 'orders' || $slug == 'products' || $slug == 'journal_records' || $slug == 'cars' || $slug == 'drivers')
<div class="top-menu">
    @if($slug == 'drivers')
    <ul class="ps-0">
        <li>
            <a data-toggle="tab" href="#main" class="active">Общее</a>
        </li>
        <li>
            <a data-toggle="tab" href="#salaries">Зарплата</a>
        </li>
        <li>
            <a data-toggle="tab" href="#fund">Аварийный фонд</a>
        </li>
    </ul>
    @elseif($slug == 'cars')
    <ul class="ps-0">
        <li>
            <a data-toggle="tab" href="#main" class="active">Общее</a>
        </li>
        <li>
            <a data-toggle="tab" href="#journal">Ремонт и тех. обслуживания</a>
        </li>
        <li>
            <a data-toggle="tab" href="#products">Отслеживаемые товары</a>
        </li>
        <li>
            <a data-toggle="tab" href="#mileages">Пробег, км и моточасы</a>
        </li>
        <li>
            <a data-toggle="tab" href="#relations">Связанные документы</a>
        </li>
    </ul>
    @elseif($slug == 'orders')
    <ul class="ps-0">
        <li>
            <a data-toggle="tab" href="#main" class="active">Задача</a>
        </li>
        <li>
            <a data-toggle="tab" href="#products">Товары и услуги</a>
        </li>
    </ul>
    @elseif($slug == 'products')
    <ul class="ps-0">
        <li>
            <a data-toggle="tab" href="#main" class="active">Товар</a>
        </li>
        <li>
            <a data-toggle="tab" href="#remnants">Остатки товара</a>
        </li>
    </ul>
    @elseif($slug == 'journal_records')
    <ul class="ps-0">
        <li>
            <a data-toggle="tab" href="#main" class="active">Общее</a>
        </li>
        <li>
            <a data-toggle="tab" href="#products">Товар списания</a>
        </li>
        <li>
            <a data-toggle="tab" href="#expenses">Доп. расходы</a>
        </li>
    </ul>
    @endif
</div>
@endif
<div class="side-list__item active" data-id="{{ $current->id }}"></div>
<div class="t-body" data-model="{{ $slug }}" >
    <div class="row g-0">
        <div id="main" class="tab-content">
            <div class="row object-content">
                <div class="col-lg-5 ">
                    @if(request()->create)
                    <input type="hidden" name="create" value="Y">
                    @endif
                    {{ csrf_field() }}
                    
                    <ul class="list-unstyled js-sort-t" data-id="1" style="min-height: 40px;">
                    @if(isset($sections_1) && count($sections_1))
                        @foreach($sections_1 as $section)
                        <li class="object-section">
                            <div class="ps-2 pe-2 d-flex justify-content-end align-items-center object-section__toolbar toolbar-section border-top-0" data-id="{{ $section->id }}">
                                <div class="position-relative me-auto d-flex align-items-center ps-0">
                                    @if($write_perm)
                                    <span class="btn btn-drag btn-drag-section btn-xs p-0 text-muted mx-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11px" height="12px" viewBox="0 0 11 12" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <g  transform="translate(-257.000000, -95.000000)" fill="#A6B7D4">
                                                    <g transform="translate(254.000000, 85.000000)">
                                                        <path d="M4,10 L7,10 C7.55228475,10 8,10.4477153 8,11 C8,11.5522847 7.55228475,12 7,12 L4,12 C3.44771525,12 3,11.5522847 3,11 C3,10.4477153 3.44771525,10 4,10 Z M4,15 L7,15 C7.55228475,15 8,15.4477153 8,16 C8,16.5522847 7.55228475,17 7,17 L4,17 C3.44771525,17 3,16.5522847 3,16 C3,15.4477153 3.44771525,15 4,15 Z M4,20 L7,20 C7.55228475,20 8,20.4477153 8,21 C8,21.5522847 7.55228475,22 7,22 L4,22 C3.44771525,22 3,21.5522847 3,21 C3,20.4477153 3.44771525,20 4,20 Z M10,10 L13,10 C13.5522847,10 14,10.4477153 14,11 C14,11.5522847 13.5522847,12 13,12 L10,12 C9.44771525,12 9,11.5522847 9,11 C9,10.4477153 9.44771525,10 10,10 Z M10,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L10,17 C9.44771525,17 9,16.5522847 9,16 C9,15.4477153 9.44771525,15 10,15 Z M10,20 L13,20 C13.5522847,20 14,20.4477153 14,21 C14,21.5522847 13.5522847,22 13,22 L10,22 C9.44771525,22 9,21.5522847 9,21 C9,20.4477153 9.44771525,20 10,20 Z" />
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </span>
                                    @endif
                                    <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                                    @if($write_perm)
                                    <span class="js-edit-section-title mx-2" data-id="{{ $section->id }}"><img src="/images/ico_pen.svg"></span>
                                    @endif
                                </div>
                                <a href="javascript:;" class="link js-edit-section" data-model="{{ $slug }}" >Изменить</a>   
                                <div class="settings position-relative">
                                    <a class="dropdown-toggle btn p-0 ms-3" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                    </a> 

                                    <ul class="dropdown-menu" aria-labelledby="a2" style="">
                                        <!-- <li><a class="dropdown-item js-section-hide" href="javascript:;" data-section="{{ $section->id }}">Скрыть</a></li> -->
                                        <li>
                                            <a class="dropdown-item js-section-delete" href="javascript:;" data-section="{{ $section->id }}"><span class="text-danger">Удалить</span></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="c-body pt-3">
                                <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
                                    @if($section->visible_fields && count($section->visible_fields))
                                        @foreach($section->visible_fields as $k => $field)
                                            @php
                                                if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !$is_admin)
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
                                            <li class=" @if($field->type != 'status')col-lg-12 @endif{{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] == 'disabled' && !$is_admin) data-blocked="true" @endif @if($current->route_id && $field->field == 'date_delivery_status') data-disabled="true" @endif>
                                                @if($field->field == 'address')
                                                <div class="mb-2 card p-3 bg-light">
                                                @endif
                                                    <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                        @if($write_perm)
                                                        <span class="btn btn-sort js-edit-position btn-drag btn-drag-field btn-xs p-0 text-muted">
                                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11px" height="12px" viewBox="0 0 11 12" version="1.1">
                                                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                                    <g  transform="translate(-257.000000, -95.000000)" fill="#A6B7D4">
                                                                        <g transform="translate(254.000000, 85.000000)">
                                                                            <path d="M4,10 L7,10 C7.55228475,10 8,10.4477153 8,11 C8,11.5522847 7.55228475,12 7,12 L4,12 C3.44771525,12 3,11.5522847 3,11 C3,10.4477153 3.44771525,10 4,10 Z M4,15 L7,15 C7.55228475,15 8,15.4477153 8,16 C8,16.5522847 7.55228475,17 7,17 L4,17 C3.44771525,17 3,16.5522847 3,16 C3,15.4477153 3.44771525,15 4,15 Z M4,20 L7,20 C7.55228475,20 8,20.4477153 8,21 C8,21.5522847 7.55228475,22 7,22 L4,22 C3.44771525,22 3,21.5522847 3,21 C3,20.4477153 3.44771525,20 4,20 Z M10,10 L13,10 C13.5522847,10 14,10.4477153 14,11 C14,11.5522847 13.5522847,12 13,12 L10,12 C9.44771525,12 9,11.5522847 9,11 C9,10.4477153 9.44771525,10 10,10 Z M10,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L10,17 C9.44771525,17 9,16.5522847 9,16 C9,15.4477153 9.44771525,15 10,15 Z M10,20 L13,20 C13.5522847,20 14,20.4477153 14,21 C14,21.5522847 13.5522847,22 13,22 L10,22 C9.44771525,22 9,21.5522847 9,21 C9,20.4477153 9.44771525,20 10,20 Z" />
                                                                        </g>
                                                                    </g>
                                                                </g>
                                                            </svg>
                                                        </span>
                                                        @endif
                                                        <div class="label @if((isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] != 'disabled' || $is_admin) && !$field->only_read) text-black @endif">
                                                            {{ $field->display_name }}
                                                        </div>
                                                        @if($write_perm)
                                                        <div class="settings position-absolute" style="right:0;">
                                                            <a class="dropdown-toggle btn p-0 text-secondary" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                                                <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                                            </a>

                                                            <ul class="dropdown-menu" >
                                                                @if($field->type != 'relation' && !$field->only_read && $field->field != 'address')
                                                                <li><a class="dropdown-item js-field-update-btn" data-field="{{ $field->id }}" href="#updateField" data-fancybox data-touch="false">Настроить</a></li>
                                                                @endif
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
                                                                @if($field->type != 'relation' && !$field->only_read && $field->field != 'address')
                                                                <li><a class="dropdown-item js-field-destroy" data-field="{{ $field->id }}" href="javascript:;">Удалить</a></li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    
                                                    
                                                    @if($field->field == 'car_data_block')
                                                    <div class="card p-3 bg-light">
                                                        <div class="card-body">
                                                            <ul class="c-list list-unstyled d-flex flex-wrap pb-1">
                                                                @foreach($subfields as $subfield)
                                                                <li class="col-lg-12">
                                                                    <div class="label mb-1">
                                                                        {{ $subfield->display_name }}
                                                                    </div>
                                                                    @php
                                                                    $disabled_class = '';
                                                                    if($subfield->field == 'car_mark' && !$current->car_category || ($subfield->field == 'car_model' && !$current->car_mark))
                                                                        $disabled_class = 'disabled';
                                                                    @endphp
                                                                        @if(request()->edit)
                                                                            <div class="js-editable active {{ $disabled_class }}" data-field="{{ $subfield->field }}" data-type="{{ $subfield->type }}" data-value="{{ $current->{$subfield->field} }}" @if($field->field == 'car_mark' || $field->field == 'car_model') data-category="{{ $current->car_category }}" @endif @if($field->field == 'car_model') data-mark="{{ $current->car_mark }}" @endif>
                                                                            @include('fields.show.'.$subfield->type, ['field_data' => $subfield, 'value' => $current->{$subfield->field}, 'entity_id' => $current->id ])
                                                                            </div>
                                                                        @else
                                                                            
                                                                            @include('fields.values.'.$subfield->type, ['field' => $subfield, 'current' => $current])
                                                                        @endif

                                                                    
                                                                </li>
                                                                @endforeach
                                                                <li class="col-lg-12" style="margin-bottom:0">
                                                                    <div class="label mb-1">
                                                                        Коэффициент машины
                                                                    </div>
                                                                    <strong class="text-danger">{{ \App\Models\CarKoef::getKoef($current->car_model) }}</strong>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    @elseif($field->field == 'carrier_stat')
                                                    <div class="card p-3 bg-light w-100 flex-row">
                                                        <div class="col-6 ">
                                                            <ul class="c-list list-unstyled pt-1 mb-0">
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Доход
                                                                    </div>
                                                                    <div class="h4 mb-0 text-success">
                                                                        {{ number_format($current->stat_all_income, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Заложено на доставки
                                                                    </div>
                                                                    <div class="h4 mb-0">
                                                                        {{ number_format($current->stat_max_sum, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Себестоимость доставок
                                                                    </div>
                                                                    <div class="h4 mb-0 text-danger">
                                                                        {{ number_format($current->stat_sum, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li style="margin-bottom: 0;">
                                                                    <div class="label mb-1">
                                                                        Дополнительные расходы
                                                                    </div>
                                                                    <div class="h4 mb-0 text-danger">
                                                                        {{ number_format($current->stat_expenses, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-6">
                                                            <ul class="c-list list-unstyled d-flex flex-wrap pb-1">
                                                                
                                                                <li class="col-lg-12" style="margin-bottom: 0;">
                                                                    <div class="label mb-1">
                                                                        Статистика
                                                                    </div>
                                                                    {!! $current->statistic !!}
                                                                </li>
                                                                
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    @elseif($field->type == 'text_group')
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
                                                        @if((request()->edit || request()->create || $field->type == 'status') && !$field->only_read)
                                                            <div class="@if($field->field != 'number') js-editable @endif active"  data-field="{{ $field->field }}" data-type="{{ $field->type }}" data-value="{{ $current->{$field->field} }}">
                                                            @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $current->{$field->field} ])
                                                            </div>
                                                        @else
                                                            @include('fields.values.'.$field->type, ['field' => $field, 'current' => $current, 'value' => $current->{$field->field}])
                                                        @endif
                                                    @endif

                                                @if($field->field == 'address')
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="latitude" value="{{ isset($current) ? $current->latitude : '' }}">
                                                    </div>
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="longitude" value="{{ isset($current) ? $current->longitude : '' }}">
                                                    </div>
                                                    <div class="position-relative mt-2">
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
                                            </li>
                                        @endforeach

                                    @else
                                    <li></li>
                                    @endif
                                </ul>
                                
                                @if($is_admin)
                                <div >
                                    <div class="settings position-relative d-inline-block">
                                        <a class="dropdown-toggle link show me-2 fs-14" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
                                            Добавить
                                        </a>
                                        <ul class="dropdown-menu start-0">
                                            @if($hidden_fields)
                                                @foreach($hidden_fields as $field)
                                                <li><a class="dropdown-item js-field-show" href="javascript:;" data-model="{{ $slug }}"  data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
                                                @endforeach
                                            @endif
                                            <li>
                                                <a class="dropdown-item js-add-field-section" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false"><span class="text-secondary">Создать свое поле</span></a>
                                            </li>
                                        </ul>
                                        <a class="dropdown-toggle link me-2 js-add-field-section fs-14" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Создать поле
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    @endif
                    </ul>
                    <div >
                        <div class="settings position-relative d-inline-block">
                            <a class="dropdown-toggle dashboard-link link fs-14 js-add-section" href="#addSection" data-column="1" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Создать раздел
                            </a>
                        </div>
                    </div>
                    
                </div>
                <div class="col-lg-7">
                    <ul class="list-unstyled js-sort-t" data-id="2" style="min-height: 40px;">
                    @if(isset($sections_2) && count($sections_2))
                        @foreach($sections_2 as $section)
                        <li class="object-section">
                            <div class="ps-2 pe-2 d-flex justify-content-end align-items-center object-section__toolbar toolbar-section border-top-0" data-id="{{ $section->id }}">
                                <div class="position-relative me-auto d-flex align-items-center ps-0">
                                    @if($write_perm)
                                    <span class="btn btn-drag btn-drag-section btn-xs p-0 text-muted mx-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11px" height="12px" viewBox="0 0 11 12" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <g  transform="translate(-257.000000, -95.000000)" fill="#A6B7D4">
                                                    <g transform="translate(254.000000, 85.000000)">
                                                        <path d="M4,10 L7,10 C7.55228475,10 8,10.4477153 8,11 C8,11.5522847 7.55228475,12 7,12 L4,12 C3.44771525,12 3,11.5522847 3,11 C3,10.4477153 3.44771525,10 4,10 Z M4,15 L7,15 C7.55228475,15 8,15.4477153 8,16 C8,16.5522847 7.55228475,17 7,17 L4,17 C3.44771525,17 3,16.5522847 3,16 C3,15.4477153 3.44771525,15 4,15 Z M4,20 L7,20 C7.55228475,20 8,20.4477153 8,21 C8,21.5522847 7.55228475,22 7,22 L4,22 C3.44771525,22 3,21.5522847 3,21 C3,20.4477153 3.44771525,20 4,20 Z M10,10 L13,10 C13.5522847,10 14,10.4477153 14,11 C14,11.5522847 13.5522847,12 13,12 L10,12 C9.44771525,12 9,11.5522847 9,11 C9,10.4477153 9.44771525,10 10,10 Z M10,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L10,17 C9.44771525,17 9,16.5522847 9,16 C9,15.4477153 9.44771525,15 10,15 Z M10,20 L13,20 C13.5522847,20 14,20.4477153 14,21 C14,21.5522847 13.5522847,22 13,22 L10,22 C9.44771525,22 9,21.5522847 9,21 C9,20.4477153 9.44771525,20 10,20 Z" />
                                                    </g>
                                                </g>
                                            </g>
                                        </svg>
                                    </span>
                                    @endif
                                    <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                                    @if($write_perm)
                                    <span class="js-edit-section-title mx-2" data-id="{{ $section->id }}"><img src="/images/ico_pen.svg"></span>
                                    @endif
                                </div>
                                <a href="javascript:;" class="link js-edit-section" data-model="{{ $slug }}" >Изменить</a>   
                                <div class="settings position-relative">
                                    <a class="dropdown-toggle btn p-0 ms-3" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                    </a> 

                                    <ul class="dropdown-menu" aria-labelledby="a2" style="">
                                        <!-- <li><a class="dropdown-item js-section-hide" href="javascript:;" data-section="{{ $section->id }}">Скрыть</a></li> -->
                                        <li>
                                            <a class="dropdown-item js-section-delete" href="javascript:;" data-section="{{ $section->id }}"><span class="text-danger">Удалить</span></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="c-body pt-3">
                                <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
                                    @if($section->visible_fields && count($section->visible_fields))
                                        @foreach($section->visible_fields as $k => $field)
                                            @php
                                                if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !$is_admin)
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
                                            <li class=" @if($field->type != 'status')col-lg-12 @endif{{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || !$field->visible_always && $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] == 'disabled' && !$is_admin) data-blocked="true" @endif @if($current->route_id && $field->field == 'date_delivery_status' || !$current->route_id && $field->field == 'point_status') data-disabled="true" @endif>
                                                @if($field->field == 'address')
                                                <div class="mb-2 card p-3 bg-light">
                                                @endif
                                                    <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                        @if($write_perm)
                                                        <span class="btn btn-sort js-edit-position btn-drag btn-drag-field btn-xs p-0 text-muted">
                                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11px" height="12px" viewBox="0 0 11 12" version="1.1">
                                                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                                    <g  transform="translate(-257.000000, -95.000000)" fill="#A6B7D4">
                                                                        <g transform="translate(254.000000, 85.000000)">
                                                                            <path d="M4,10 L7,10 C7.55228475,10 8,10.4477153 8,11 C8,11.5522847 7.55228475,12 7,12 L4,12 C3.44771525,12 3,11.5522847 3,11 C3,10.4477153 3.44771525,10 4,10 Z M4,15 L7,15 C7.55228475,15 8,15.4477153 8,16 C8,16.5522847 7.55228475,17 7,17 L4,17 C3.44771525,17 3,16.5522847 3,16 C3,15.4477153 3.44771525,15 4,15 Z M4,20 L7,20 C7.55228475,20 8,20.4477153 8,21 C8,21.5522847 7.55228475,22 7,22 L4,22 C3.44771525,22 3,21.5522847 3,21 C3,20.4477153 3.44771525,20 4,20 Z M10,10 L13,10 C13.5522847,10 14,10.4477153 14,11 C14,11.5522847 13.5522847,12 13,12 L10,12 C9.44771525,12 9,11.5522847 9,11 C9,10.4477153 9.44771525,10 10,10 Z M10,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L10,17 C9.44771525,17 9,16.5522847 9,16 C9,15.4477153 9.44771525,15 10,15 Z M10,20 L13,20 C13.5522847,20 14,20.4477153 14,21 C14,21.5522847 13.5522847,22 13,22 L10,22 C9.44771525,22 9,21.5522847 9,21 C9,20.4477153 9.44771525,20 10,20 Z" />
                                                                        </g>
                                                                    </g>
                                                                </g>
                                                            </svg>
                                                        </span>
                                                        @endif
                                                        <div class="label @if($write_perm) text-black @endif">
                                                            {{ $field->display_name }}
                                                        </div>
                                                        @if($write_perm)
                                                        <div class="settings position-absolute" style="right:0;">
                                                            <a class="dropdown-toggle btn p-0 text-secondary" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                                                <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                                            </a>

                                                            <ul class="dropdown-menu" >
                                                                @if($field->type != 'relation' && !$field->only_read && $field->field != 'address')
                                                                <li><a class="dropdown-item js-field-update-btn" data-field="{{ $field->id }}" href="#updateField" data-fancybox data-touch="false">Настроить</a></li>
                                                                @endif
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
                                                                @if($field->type != 'relation' && !$field->only_read && $field->field != 'address')
                                                                <li><a class="dropdown-item js-field-destroy" data-field="{{ $field->id }}" href="javascript:;">Удалить</a></li>
                                                                @endif
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
                                                    @elseif($field->field == 'car_data_block')
                                                        <div class="card p-3 bg-light">
                                                            <div class="card-body">
                                                                <ul class="c-list list-unstyled d-flex flex-wrap pb-1">
                                                                    @foreach($subfields as $subfield)
                                                                    <li class="col-lg-12">
                                                                        <div class="label mb-1">
                                                                            {{ $subfield->display_name }}
                                                                        </div>
                                                                        @php
                                                                        $disabled_class = '';
                                                                        if($subfield->field == 'car_mark' && !$current->car_category || ($subfield->field == 'car_model' && !$current->car_mark))
                                                                            $disabled_class = 'disabled';
                                                                        @endphp
                                                                            @if(request()->edit)
                                                                                <div class="js-editable active {{ $disabled_class }}" data-field="{{ $subfield->field }}" data-type="{{ $subfield->type }}" data-value="{{ $current->{$subfield->field} }}" @if($field->field == 'car_mark' || $field->field == 'car_model') data-category="{{ $current->car_category }}" @endif @if($field->field == 'car_model') data-mark="{{ $current->car_mark }}" @endif>
                                                                                @include('fields.show.'.$subfield->type, ['field_data' => $subfield, 'value' => $current->{$subfield->field}, 'entity_id' => $current->id ])
                                                                                </div>
                                                                            @else
                                                                                
                                                                                @include('fields.values.'.$subfield->type, ['field' => $subfield, 'current' => $current])
                                                                            @endif

                                                                        
                                                                    </li>
                                                                    @endforeach
                                                                    <li class="col-lg-12" style="margin-bottom:0">
                                                                        <div class="label mb-1">
                                                                            Коэффициент машины
                                                                        </div>
                                                                        <strong class="text-danger">{{ \App\Models\CarKoef::getKoef($current->car_model) }}</strong>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    @elseif($field->field == 'carrier_stat')
                                                    <div class="card p-3 bg-light w-100 flex-row">
                                                        <div class="col-6 ">
                                                            <ul class="c-list list-unstyled pt-1 mb-0">
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Доход
                                                                    </div>
                                                                    <div class="h4 mb-0 text-success">
                                                                        {{ number_format($current->stat_all_income, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Заложено на доставки
                                                                    </div>
                                                                    <div class="h4 mb-0">
                                                                        {{ number_format($current->stat_max_sum, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="label mb-1">
                                                                        Себестоимость доставок
                                                                    </div>
                                                                    <div class="h4 mb-0 text-danger">
                                                                        {{ number_format($current->stat_sum, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                                <li style="margin-bottom: 0;">
                                                                    <div class="label mb-1">
                                                                        Дополнительные расходы
                                                                    </div>
                                                                    <div class="h4 mb-0 text-danger">
                                                                        {{ number_format($current->stat_expenses, 0, ',', ' ') }}
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="col-6">
                                                            <ul class="c-list list-unstyled d-flex flex-wrap pb-1">
                                                                
                                                                <li class="col-lg-12" style="margin-bottom: 0;">
                                                                    <div class="label mb-1">
                                                                        Статистика
                                                                    </div>
                                                                    {!! $current->statistic !!}
                                                                </li>
                                                                
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    @else
                                                        @if((request()->edit || request()->create || $field->type == 'status') && !$field->only_read)
                                                            <div class="@if($field->field != 'number') js-editable @endif active"  data-field="{{ $field->field }}" data-type="{{ $field->type }}" data-value="{{ $current->{$field->field} }}">
                                                            @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $current->{$field->field} ])
                                                            </div>
                                                        @else
                                                            @include('fields.values.'.$field->type, ['field' => $field, 'current' => $current, 'value' => $current->{$field->field}])
                                                        @endif
                                                    @endif

                                                @if($field->field == 'address')
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="latitude" value="{{ isset($current) ? $current->latitude : '' }}">
                                                    </div>
                                                    <div class="js-editable d-none">
                                                        <input type="hidden" name="longitude" value="{{ isset($current) ? $current->longitude : '' }}">
                                                    </div>
                                                    <div class="position-relative mt-2">
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
                                            </li>
                                        @endforeach

                                    @else
                                    <li></li>
                                    @endif
                                </ul>
                                
                                @if($is_admin)
                                <div >
                                    <div class="settings position-relative d-inline-block">
                                        <a class="dropdown-toggle link show me-2 fs-14" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
                                            Добавить
                                        </a>
                                        <ul class="dropdown-menu start-0">
                                            @if($hidden_fields)
                                                @foreach($hidden_fields as $field)
                                                <li><a class="dropdown-item js-field-show" href="javascript:;" data-model="{{ $slug }}"  data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
                                                @endforeach
                                            @endif
                                            <li>
                                                <a class="dropdown-item" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false"><span class="text-secondary">Создать свое поле</span></a>
                                            </li>
                                        </ul>
                                        <a class="dropdown-toggle link me-2 js-add-field-section fs-14" href="#addField" data-section="{{ $section->id }}" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Создать поле
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    @endif
                        
                    </ul>
                    <div >
                        <div class="settings position-relative d-inline-block">
                            <a class="dropdown-toggle dashboard-link link fs-14 js-add-section" href="#addSection" data-column="2" data-fancybox data-touch="false" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Создать раздел
                            </a>
                        </div>
                    </div>
                    <div class="dashboard-title">История изменений</div>
                    <div class="timeline">
                        @foreach($history_days as $history_day => $history_items)
                            @if($history_day == date('d.m.Y'))
                            <div class="timeline-title">
                                <div class="timeline-title_container">
                                    <div class="timeline-title_container_label today">Сегодня</div>
                                </div>
                            </div>
                            @else
                            <div class="timeline-title">
                                <div class="timeline-title_container">
                                    <div class="timeline-title_container_label">{{ $history_day }}</div>
                                </div>
                            </div>
                            @endif
                            @foreach($history_items as $history_item)
                            <div class="timeline-card">
                                <div class="timeline-card_icon_container">
                                    @if(strstr($history_item->text, "Перенос в машину"))
                                    <img src="/img/car_add.svg">
                                    @elseif(strstr($history_item->text, "Удаление из машины"))
                                    <img src="/img/car_remove.svg">
                                    @else
                                    <img src="/img/edit.svg">
                                    @endif
                                    <!-- <div class="timeline-card_icon ">
                                        <i class="fa fa-pen"></i>
                                    </div> -->
                                </div>
                                <div class="timeline-card_top">
                                    <div class="timeline-card_top_info">
                                        <div class="timeline-card_top_info_left">
                                            <span class="timeline-card_title" title="">
                                                @if(strstr($history_item->text, "Перенос в машину"))
                                                Перенос в машину
                                                @elseif(strstr($history_item->text, "Удаление из машины"))
                                                Удаление из машины
                                                @else
                                                Изменение поля: 
                                                @endif
                                            </span>
                                        </div>
                                        <div class="timeline-card_top_info_right">
                                            <div class="timeline-card_time">{{ \Carbon\Carbon::parse($history_item->created_at)->format('H:i:s') }}</div>
                                        </div>
                                    </div>
                                    <div class="timeline-card_user">
                                        @if(isset($users[$history_item->user_id]))
                                            @php
                                            $value = json_decode($users[$history_item->user_id]->avatar,true);
                                            if(is_array($value))
                                                $value = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->first();
                                            @endphp
                                            @if(isset($value->path))
                                                <a class="timeline-card_user_icon" href="{{ route('users.edit', $history_item->user_id) }}" target="_blank" title="{{ isset($users[$history_item->user_id]) ? $users[$history_item->user_id]->name : '' }}" style="background-image:url({{ \Thumbnail::src('https://compas.pro'.Storage::disk()->url($value->path))->heighten(200)->url() }})">
                                                </a>
                                            @else
                                                @php
                                                $name = $last_name = '';
                                                if($users[$history_item->user_id]->name)
                                                    $name = mb_substr($users[$history_item->user_id]->name,0,1);
                                                if($users[$history_item->user_id]->last_name)
                                                    $last_name = mb_substr($users[$history_item->user_id]->last_name,0,1);
                                                @endphp
                                                <a class="timeline-card_user_icon" href="{{ route('users.edit', $history_item->user_id) }}" target="_blank" title="{{ isset($users[$history_item->user_id]) ? $users[$history_item->user_id]->name : '' }}" style="background: {{ $users[$history_item->user_id]->getColor() }};">
                                                    {{ ucfirst($name).ucfirst($last_name) }}
                                                </a>
                                            @endif
                                        @else
                                        <a class="timeline-card_user_icon" href="javascript:;" target="_blank" title="">
                                            В
                                        </a>
                                        @endif

                                    </div>
                                </div>
                                <div class="timeline-card_body">
                                    <div class="timeline-card_body_container">
                                        <div class="timeline-card_container_block">
                                            <span class="crm-timeline-block-line-of-texts">
                                                @if(strstr($history_item->text, ":"))
                                                    @php
                                                    $text = explode(': ', $history_item->text);
                                                    @endphp
                                                    <span>
                                                        <span title="" class="timeline-card_text-block_gray" data-id="title">
                                                        @if(strstr($text[0], 'Удаление из машины'))
                                                        {{ str_replace('Удаление из машины', 'Название', $text[0]) }}: 
                                                        @elseif(strstr($text[0], 'Перенос в машину'))
                                                        {{ str_replace('Перенос в машину', 'Название', $text[0]) }}: 
                                                        @else
                                                        {{ $text[0] }}: 
                                                        @endif
                                                        </span>
                                                    </span>
                                                    <span class="timeline-card_text-block">
                                                        {{ $text[1] }}
                                                    </span>
                                                @else
                                                    <span class="timeline-card_text-block">
                                                        {{ $history_item->text }}
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endforeach
                        
                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($slug == 'orders')
        <div id="products" class="tab-content" style="display:none">
            <div class="col-lg-12">
                <div class="c-top pe-2 bg-light border-top border-bottom d-flex justify-content-end align-items-center toolbar-section">
                    <div class="position-relative me-auto d-flex align-items-center">
                        <h6 class="h6 my-0 me-auto">Состав заказа</h6>
                    </div>
                </div>
                <div class="c-body p-4">
                    <table class="products-order-table w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="px-3"><div class="label">Название</div></th>
                                <th class="px-3"><div class="label">Цена</div></th>
                                <th class="px-3"><div class="label">Кол-во</div></th>
                                <th class="px-3"><div class="label">Вес, кг</div></th>
                                <th class="px-3"><div class="label">Итого</div></th>
                                <th><div class="label"></div></th>
                            </tr>
                        </thead>
                        <tbody class="js-sort-products">
                            @foreach($products as $product)
                            <tr>
                                <td class="pb-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 pb-3">
                                    <input name="id" type="hidden" value="{{ isset($product['id']) ? $product['id'] : '' }}">
                                    <input name="name" type="text" class="js-search-name form-control" value="{{ $product['name'] }}">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="price" type="text" class="form-control" value="{{ $product['price'] }}">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="count" type="text" class="form-control" value="{{ $product['count'] }}">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="weight" type="text" class="form-control" value="{{ $product['weight'] }}">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="sum" type="text" class="form-control" value="{{ $product['sum'] }}">
                                </td>
                                <td class="pb-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endforeach
                            @if(!count($products))
                            <tr>
                                <td class="pb-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 pb-3">
                                    <input name="id" type="hidden" value="{{ isset($product['id']) ? $product['id'] : '' }}">
                                    <input name="name" type="text" class="js-search-name form-control" value="">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="price" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="count" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="weight" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 pb-3">
                                    <input name="sum" type="text" class="form-control" value="">
                                </td>
                                <td class="pb-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    <a class="link fs-14 js-add-productrow" href="javascript:;" >
                        Добавить поле
                    </a>
                    <div class="products-sum">
                        <div class="products-sum__row">
                            <div class="products-sum__label">Сумма:</div><div class="products-sum__value"><span class="js-products-sum">{{ $sum }}</span> руб.</div>
                        </div>
                        <div class="products-sum__row">
                            <div class="products-sum__label">Кол-во:</div><div class="products-sum__value"><span class="js-products-count">{{ $count }}</span> шт.</div>
                        </div>
                        <div class="products-sum__row">
                            <div class="products-sum__label">Общий вес:</div><div class="products-sum__value"><span class="js-products-weight">{{ $weight }}</span> кг.</div>
                        </div>
                    </div>
                    <div class="js-search-results search-products d-none"></div>
                </div>
            </div>
        </div>
        @elseif($slug == 'products')
        <div id="remnants" class="tab-content" style="display:none">
            <div class="table__wrapper">
                <table class="table__inner js-entity-table" data-model="remnants">
                    <thead class="table-header">
                        <tr class="table-header__row">
                            @php
                            $start_columns = array(
                            );
                            $start_columns['select'] = array(
                                'name' => 'select',
                                'display_name' => 'Выделение строки',
                                'fix' => (isset($table_settings['remnants']) && in_array('select', $table_settings['remnants']['fix']) ? 1 : 0),
                                'hidden' => (isset($table_settings['remnants']) && !in_array('select', $table_settings['remnants']['visible']) ? 1 : 0),
                                'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])].'px' : ''),
                                'write_perm' => 1

                            );
                            $start_columns['actions'] = array(
                                'name' => 'actions',
                                'display_name' => 'Действие',
                                'fix' => (isset($table_settings['remnants']) && in_array('actions', $table_settings['remnants']['fix']) ? 1 : 0),
                                'hidden' => (isset($table_settings['remnants']) && !in_array('actions', $table_settings['remnants']['visible']) ? 1 : 0),
                                'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])].'px' : ''),
                                'write_perm' => 1
                            );
                            foreach($remnant_fields as $field) {
                                $start_columns[$field->field] = array(
                                    'name' => $field->field,
                                    'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                    'fix' => (isset($table_settings['remnants']) && in_array($field->field, $table_settings['remnants']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['remnants']) && !in_array($field->field, $table_settings['remnants']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search($field->field, $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search($field->field, $table_settings['remnants']['reorder'])].'px' : ''),
                                    'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                );
                            }
                            if(isset($table_settings['remnants']))
                              $start_columns = array_merge(array_flip($table_settings['remnants']['reorder']), $start_columns);
                            @endphp
                            @foreach($start_columns as $col)
                              @if($col['name'] == 'select')
                                <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                style="width: {{ $col['width'] }};"
                                @else
                                style="width: 40px;"
                                @endif>
                                  <div class="table-header__inner">
                                    <div class="form-checkbox">
                                      <label class="form-checkbox__label" for="mainCheckbox">
                                        <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                        <span class="form-checkbox__switcher"></span>
                                      </label>
                                    </div>
                                    <button class="table-header__filter-btn btn-clear" type="button">
                                      <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                    </button>
                                  </div>
                                  <span class="table-header__label">Выделение</span>
                                </th>
                              @else
                                <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                @if($col['width'])
                                style="width: {{ $col['width'] }};"
                                @else
                                style="width: 130px;"
                                @endif>
                                  <div class="table-header__inner">
                                    {!! $col['display_name'] !!}
                                    <button class="table-header__filter-btn btn-clear" type="button">
                                      <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                    </button>

                                  </div>
                                </th>
                              @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($current->remnants as $object)
                        <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">          
                        @php
                            $start_columns = array(
                            );
                            foreach($remnant_fields as $field) {
                                $start_columns['select'] = array(
                                    'name' => 'select',
                                    'fix' => (isset($table_settings['remnants']) && in_array('select', $table_settings['remnants']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['remnants']) && !in_array('select', $table_settings['remnants']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])].'px' : ''),

                                );
                                $start_columns['actions'] = array(
                                    'name' => 'actions',
                                    'display_name' => 'Действие',
                                    'fix' => (isset($table_settings['remnants']) && in_array('actions', $table_settings['remnants']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['remnants']) && !in_array('actions', $table_settings['remnants']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])].'px' : ''),
                                );
                                $start_columns[$field->field] = array(
                                    'name' => $field->field,
                                    'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                    'fix' => (isset($table_settings['remnants']) && in_array($field->field, $table_settings['remnants']['fix']) ? 1 : 0),
                                    'type' => $field->type,
                                    'only_read' => $field->only_read,
                                    'hidden' => (isset($table_settings['remnants']) && !in_array($field->field, $table_settings['remnants']['visible']) ? 1 : 0),
                                    'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                    'is_date' => ($field->type == 'date'),
                                );
                            }
                            if(isset($table_settings['remnants'])) {
                              
                              $start_columns = array_merge(array_flip($table_settings['remnants']['reorder']), $start_columns);
                            }
                            @endphp
                            @foreach($start_columns as $col)
                            @if($col['name'] == 'select')
                            <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                              <div class="form-checkbox">
                                <label class="form-checkbox__label">
                                  <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                  <span class="form-checkbox__switcher"></span>
                                </label>
                              </div>
                            </td>
                            @elseif($col['name'] == 'actions')
                            <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                              <div class="table-options__inner">
                                <div class="dropdown" data-dropdown>
                                  <button class="table-options__btn btn-clear" data-dropdown="btn">
                                    <svg width="3" height="13" fill="none">
                                      <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                    </svg>
                                  </button>
                                  <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                    <ul class="dropdown__list">
                                      <li class="dropdown__item">
                                        <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="remnants" type="button">Редактировать</button>
                                        <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="remnants" type="button" data-delete>Удалить</button>
                                      </li>
                                    </ul>
                                  </div>
                                </div>
                              </div>
                            </td>
                            @else
                            <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                @if($col['is_files'])
                                {!! $object[$col['name']] !!}
                                @else
                                <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                  {!! $object[$col['name']] !!}
                                </div>
                                @endif
                            </td>
                            @endif
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @elseif($slug == 'journal_records')
        <div id="products" class="tab-content" style="display:none">
            <div class="col-lg-12">
                <div class="c-top pe-2 bg-light border-top border-bottom d-flex justify-content-end align-items-center toolbar-section">
                    <div class="position-relative me-auto d-flex align-items-center">
                        <h6 class="h6 my-0 me-auto">Товар списания</h6>
                    </div>
                </div>
                <div class="c-body p-4">
                    <table class="products-order-table w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="px-3"><div class="label">Наименование единицы</div></th>
                                <th class="px-3"><div class="label">Цена</div></th>
                                <th class="px-3"><div class="label">Кол-во</div></th>
                                <th class="px-3"><div class="label">Название товара</div></th>
                                <th class="px-3"><div class="label">Итого</div></th>
                                <th><div class="label"></div></th>
                            </tr>
                        </thead>
                        <tbody class="js-sort-products">
                            @foreach($products as $product)
                            <tr>
                                <td class="pb-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 py-3">
                                    <input name="id" type="hidden" value="{{ isset($product['id']) ? $product['id'] : '' }}">
                                    <input name="name" type="text" class="js-search-name form-control" value="{{ $product['name'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="price" type="text" class="form-control" value="{{ $product['price'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="count" type="text" class="form-control" value="{{ $product['count'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="product" type="text" class="form-control" value="{{ isset($product['product']) ? $product['product'] : '' }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="sum" type="text" class="form-control" value="{{ $product['sum'] }}">
                                </td>
                                <td class="py-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endforeach
                            @if(!count($products))
                            <tr>
                                <td class="pb-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 py-3">
                                    <input name="id" type="hidden" value="">
                                    <input name="name" type="text" class="js-search-name form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="price" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="count" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="product" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="sum" type="text" class="form-control" value="">
                                </td>
                                <td class="py-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    <a class="link fs-14 js-add-productrow" href="javascript:;" >
                        Добавить
                    </a>
                    <div class="products-sum">
                        <div class="products-sum__row">
                            <div class="products-sum__label">Сумма:</div><div class="products-sum__value"><span class="js-products-sum">{{ $sum }}</span> руб.</div>
                        </div>
                        <div class="products-sum__row">
                            <div class="products-sum__label">Кол-во:</div><div class="products-sum__value"><span class="js-products-count">{{ $count }}</span> шт.</div>
                        </div>
                        <div class="products-sum__row">
                            <div class="products-sum__label">Общий вес:</div><div class="products-sum__value"><span class="js-products-weight">{{ $weight }}</span> кг.</div>
                        </div>
                    </div>
                    <div class="js-search-results search-products d-none"></div>
                </div>
            </div>
        </div>
        <div id="expenses" class="tab-content" style="display:none">
            <div class="col-lg-12">
                <div class="c-top pe-2 bg-light border-top border-bottom d-flex justify-content-end align-items-center toolbar-section">
                    <div class="position-relative me-auto d-flex align-items-center">
                        <h6 class="h6 my-0 me-auto">Доп. расходы</h6>
                    </div>
                </div>
                <div class="c-body p-4">
                    <table class="expenses-order-table w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="px-3"><div class="label">Название</div></th>
                                <th class="px-3"><div class="label">Описание</div></th>
                                <th class="px-3"><div class="label">Цена</div></th>
                                <th class="px-3"><div class="label">Кол-во</div></th>
                                <th class="px-3"><div class="label">Итого</div></th>
                                <th><div class="label"></div></th>
                            </tr>
                        </thead>
                        <tbody class="js-sort-products">
                            @foreach($expenses as $expense)
                            <tr>
                                <td class="py-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 py-3">
                                    <input name="name" type="text" class="form-control" value="{{ $expense['name'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="desc" type="text" class="form-control" value="{{ $expense['desc'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="price" type="text" class="form-control" value="{{ $expense['price'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="count" type="text" class="form-control" value="{{ $expense['count'] }}">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="sum" type="text" class="form-control" value="{{ $expense['sum'] }}">
                                </td>
                                <td class="py-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endforeach
                            @if(!count($expenses))
                            <tr>
                                <td class="py-3"><svg class="btn-drag icon icon-line"><use xlink:href="#icon-line"></use></svg></td>
                                <td class="px-3 py-3">
                                    <input name="name" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="desc" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="price" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="count" type="text" class="form-control" value="">
                                </td>
                                <td class="px-3 py-3">
                                    <input name="sum" type="text" class="form-control" value="">
                                </td>
                                <td class="py-3">
                                    <span class="js-delete-productrow"><i class="fa fa-close"></i></span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    <a class="link fs-14 js-add-expenserow" href="javascript:;" >
                        Добавить
                    </a>
                    <div class="products-sum">
                        <div class="products-sum__row">
                            <div class="products-sum__label">Сумма:</div><div class="products-sum__value"><span class="js-products-sum">{{ $sum_expenses }}</span> руб.</div>
                        </div>
                        <div class="products-sum__row">
                            <div class="products-sum__label">Кол-во:</div><div class="products-sum__value"><span class="js-products-count">{{ $count_expenses }}</span> шт.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif($slug == 'drivers')
        <div id="salaries" class="tab-content" style="display:none">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table">
                        <div class="table-top">
                          <button class="table__change-btn btn-clear" type="button">Сохранить изменения</button>
                          <div class="dropdown" data-dropdown>
                            <button class="table__settings btn-clear" type="button" data-dropdown="btn">
                              <svg width="15" height="15" fill="none">
                                <path fill-rule="evenodd" d="M14.25 5.979a.75.75 0 0 1 .75.75v1.542a.75.75 0 0 1-.75.75h-.952a5.954 5.954 0 0 1-.622 1.504l.672.673a.75.75 0 0 1 0 1.06l-1.09 1.09a.75.75 0 0 1-1.06 0l-.674-.672a5.957 5.957 0 0 1-1.503.622v.952a.75.75 0 0 1-.75.75H6.729a.75.75 0 0 1-.75-.75v-.952a5.953 5.953 0 0 1-1.504-.622l-.672.673a.75.75 0 0 1-1.061 0l-1.09-1.09a.75.75 0 0 1 0-1.061l.672-.673a5.953 5.953 0 0 1-.622-1.504H.75a.75.75 0 0 1-.75-.75V6.73a.75.75 0 0 1 .75-.75h.952c.14-.534.35-1.038.622-1.503l-.673-.673a.75.75 0 0 1 0-1.06l1.09-1.091a.75.75 0 0 1 1.061 0l.673.672a5.98 5.98 0 0 1 1.504-.622V.75a.75.75 0 0 1 .75-.75H8.27a.75.75 0 0 1 .75.75v.952c.534.14 1.038.35 1.503.622l.673-.672a.75.75 0 0 1 1.061 0l1.09 1.09a.75.75 0 0 1 0 1.06l-.672.673c.272.465.482.97.622 1.504h.952ZM7.5 10.252a2.752 2.752 0 1 0 0-5.504 2.752 2.752 0 0 0 0 5.504Z" clip-rule="evenodd" />
                              </svg>
                            </button>
                            <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
                              <ul class="dropdown__list">
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Отображение столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="displayMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Фиксирование столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="fixMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Порядок столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="orderMenu" data-dragName="orderMenu" data-drag="area"></ul>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div class="table__wrapper">
                          <table class="table__inner js-entity-table" data-model="salaries">
                            <thead class="table-header">
                              <tr class="table-header__row">
                                @php
                                $start_columns = array(
                                );
                                $start_columns['select'] = array(
                                    'name' => 'select',
                                    'display_name' => 'Выделение строки',
                                    'fix' => (isset($table_settings['salaries']) && in_array('select', $table_settings['salaries']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['salaries']) && !in_array('select', $table_settings['salaries']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['salaries']) && $table_settings['salaries']['sizes'][array_search('select', $table_settings['salaries']['reorder'])] ? $table_settings['salaries']['sizes'][array_search('select', $table_settings['salaries']['reorder'])].'px' : ''),
                                    'write_perm' => 1

                                );
                                $start_columns['actions'] = array(
                                    'name' => 'actions',
                                    'display_name' => 'Действие',
                                    'fix' => (isset($table_settings['salaries']) && in_array('actions', $table_settings['salaries']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['salaries']) && !in_array('actions', $table_settings['salaries']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['salaries']) && $table_settings['salaries']['sizes'][array_search('actions', $table_settings['salaries']['reorder'])] ? $table_settings['salaries']['sizes'][array_search('actions', $table_settings['salaries']['reorder'])].'px' : ''),
                                    'write_perm' => 1
                                );
                                foreach($salary_fields as $field) {
                                    $start_columns[$field->field] = array(
                                        'name' => $field->field,
                                        'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                        'fix' => (isset($table_settings['salaries']) && in_array($field->field, $table_settings['salaries']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['salaries']) && !in_array($field->field, $table_settings['salaries']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['salaries']) && $table_settings['salaries']['sizes'][array_search($field->field, $table_settings['salaries']['reorder'])] ? $table_settings['salaries']['sizes'][array_search($field->field, $table_settings['salaries']['reorder'])].'px' : ''),
                                        'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                    );
                                }
                                if(isset($table_settings['salaries']))
                                  $start_columns = array_merge(array_flip($table_settings['salaries']['reorder']), $start_columns);
                                @endphp
                                @foreach($start_columns as $col)
                                  @if($col['name'] == 'select')
                                    <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 40px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        <div class="form-checkbox">
                                          <label class="form-checkbox__label" for="mainCheckbox">
                                            <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                            <span class="form-checkbox__switcher"></span>
                                          </label>
                                        </div>
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>
                                      </div>
                                      <span class="table-header__label">Выделение</span>
                                    </th>
                                  @else
                                    <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                    @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 130px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        {!! $col['display_name'] !!}
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>

                                      </div>
                                    </th>
                                  @endif
                                @endforeach
                              </tr>
                            </thead>
                            <tbody class="table-body">
                                @foreach($current->salaries as $object)
                                <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">
                                    @php
                                    $start_columns = array(
                                    );
                                    foreach($salary_fields as $field) {
                                        $start_columns['select'] = array(
                                            'name' => 'select',
                                            'fix' => (isset($table_settings['salaries']) && in_array('select', $table_settings['salaries']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['salaries']) && !in_array('select', $table_settings['salaries']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['salaries']) && $table_settings['salaries']['sizes'][array_search('select', $table_settings['salaries']['reorder'])] ? $table_settings['salaries']['sizes'][array_search('select', $table_settings['salaries']['reorder'])].'px' : ''),

                                        );
                                        $start_columns['actions'] = array(
                                            'name' => 'actions',
                                            'display_name' => 'Действие',
                                            'fix' => (isset($table_settings['salaries']) && in_array('actions', $table_settings['salaries']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['salaries']) && !in_array('actions', $table_settings['salaries']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['salaries']) && $table_settings['salaries']['sizes'][array_search('actions', $table_settings['salaries']['reorder'])] ? $table_settings['salaries']['sizes'][array_search('actions', $table_settings['salaries']['reorder'])].'px' : ''),
                                        );
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['salaries']) && in_array($field->field, $table_settings['salaries']['fix']) ? 1 : 0),
                                            'type' => $field->type,
                                            'only_read' => $field->only_read,
                                            'hidden' => (isset($table_settings['salaries']) && !in_array($field->field, $table_settings['salaries']['visible']) ? 1 : 0),
                                            'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                            'is_date' => ($field->type == 'date'),
                                        );
                                    }
                                    if(isset($table_settings['salaries'])) {
                                      
                                      $start_columns = array_merge(array_flip($table_settings['salaries']['reorder']), $start_columns);
                                    }
                                    @endphp
                                    @foreach($start_columns as $col)
                                    @if($col['name'] == 'select')
                                    <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="form-checkbox">
                                        <label class="form-checkbox__label">
                                          <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                          <span class="form-checkbox__switcher"></span>
                                        </label>
                                      </div>
                                    </td>
                                    @elseif($col['name'] == 'actions')
                                    <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="table-options__inner">
                                        <div class="dropdown" data-dropdown>
                                          <button class="table-options__btn btn-clear" data-dropdown="btn">
                                            <svg width="3" height="13" fill="none">
                                              <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                            </svg>
                                          </button>
                                          <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                            <ul class="dropdown__list">
                                              <li class="dropdown__item">
                                                <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="salaries" type="button">Редактировать</button>
                                                <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="salaries" type="button" data-delete>Удалить</button>
                                              </li>
                                            </ul>
                                          </div>
                                        </div>
                                      </div>
                                    </td>
                                    @else
                                    <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                        @if($col['is_files'])
                                        {!! $object[$col['name']] !!}
                                        @else
                                        <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                          {!! $object[$col['name']] !!}
                                        </div>
                                        @endif
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                          </table><!-- /.table__inner -->
                        </div><!-- /.table__wrapper -->
                        <div class="table-footer">
                          <div class="table-footer__title">
                            Отмечено: <span class="table-footer__counter" id="checkboxCounter">0</span>
                          </div>
                          <div class="pagination">
                            <span class="pagination__title table-footer__title">Страница:</span>
                            <ul class="pagination__list"></ul>
                          </div>
                          <div class="table-footer__show">
                            <span class="table-footer__title sm-hidden">На странице:</span>
                            <div class="dropdown" data-dropdown>
                              <button class="select btn-clear" data-dropdown="btn" id="paginationSelect">
                                <span class="select-current">25</span>
                                <svg width="12" height="7" fill="none">
                                  <path fill-rule="evenodd" d="M11.77 1.2 6.15 6.91S6.04 7 5.86 7s-.3-.09-.3-.09L.1 1.21S-.14.7.22.3s.9-.23.9-.23l4.72 4.85L10.6.08s.5-.22.94.18c.45.4.23.94.23.94Z" clip-rule="evenodd" />
                                </svg>
                              </button>
                              <div class="dropdown__menu dropdown__menu_select" data-dropdown="menu">
                                <ul class="dropdown__list">
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="25">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="50">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="100">
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div><!-- /.table-footer -->
                    </div><!-- /.table -->
                </div>
            </div>
        </div>
        <div id="fund" class="tab-content" style="display:none">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table">
                        <div class="table-top">
                          <button class="table__change-btn btn-clear" type="button">Сохранить изменения</button>
                          <div class="dropdown" data-dropdown>
                            <button class="table__settings btn-clear" type="button" data-dropdown="btn">
                              <svg width="15" height="15" fill="none">
                                <path fill-rule="evenodd" d="M14.25 5.979a.75.75 0 0 1 .75.75v1.542a.75.75 0 0 1-.75.75h-.952a5.954 5.954 0 0 1-.622 1.504l.672.673a.75.75 0 0 1 0 1.06l-1.09 1.09a.75.75 0 0 1-1.06 0l-.674-.672a5.957 5.957 0 0 1-1.503.622v.952a.75.75 0 0 1-.75.75H6.729a.75.75 0 0 1-.75-.75v-.952a5.953 5.953 0 0 1-1.504-.622l-.672.673a.75.75 0 0 1-1.061 0l-1.09-1.09a.75.75 0 0 1 0-1.061l.672-.673a5.953 5.953 0 0 1-.622-1.504H.75a.75.75 0 0 1-.75-.75V6.73a.75.75 0 0 1 .75-.75h.952c.14-.534.35-1.038.622-1.503l-.673-.673a.75.75 0 0 1 0-1.06l1.09-1.091a.75.75 0 0 1 1.061 0l.673.672a5.98 5.98 0 0 1 1.504-.622V.75a.75.75 0 0 1 .75-.75H8.27a.75.75 0 0 1 .75.75v.952c.534.14 1.038.35 1.503.622l.673-.672a.75.75 0 0 1 1.061 0l1.09 1.09a.75.75 0 0 1 0 1.06l-.672.673c.272.465.482.97.622 1.504h.952ZM7.5 10.252a2.752 2.752 0 1 0 0-5.504 2.752 2.752 0 0 0 0 5.504Z" clip-rule="evenodd" />
                              </svg>
                            </button>
                            <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
                              <ul class="dropdown__list">
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Отображение столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="displayMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Фиксирование столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="fixMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Порядок столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="orderMenu" data-dragName="orderMenu" data-drag="area"></ul>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div class="table__wrapper">
                          <table class="table__inner js-entity-table" data-model="emergency_fund_records">
                            <thead class="table-header">
                              <tr class="table-header__row">
                                @php
                                $start_columns = array(
                                );
                                $start_columns['select'] = array(
                                    'name' => 'select',
                                    'display_name' => 'Выделение строки',
                                    'fix' => (isset($table_settings['emergency_fund_records']) && in_array('select', $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array('select', $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['emergency_fund_records']) && $table_settings['emergency_fund_records']['sizes'][array_search('select', $table_settings['emergency_fund_records']['reorder'])] ? $table_settings['emergency_fund_records']['sizes'][array_search('select', $table_settings['emergency_fund_records']['reorder'])].'px' : ''),
                                    'write_perm' => 1

                                );
                                $start_columns['actions'] = array(
                                    'name' => 'actions',
                                    'display_name' => 'Действие',
                                    'fix' => (isset($table_settings['emergency_fund_records']) && in_array('actions', $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array('actions', $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['emergency_fund_records']) && $table_settings['emergency_fund_records']['sizes'][array_search('actions', $table_settings['emergency_fund_records']['reorder'])] ? $table_settings['emergency_fund_records']['sizes'][array_search('actions', $table_settings['emergency_fund_records']['reorder'])].'px' : ''),
                                    'write_perm' => 1
                                );
                                foreach($fund_fields as $field) {
                                    $start_columns[$field->field] = array(
                                        'name' => $field->field,
                                        'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                        'fix' => (isset($table_settings['emergency_fund_records']) && in_array($field->field, $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array($field->field, $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['emergency_fund_records']) && $table_settings['emergency_fund_records']['sizes'][array_search($field->field, $table_settings['emergency_fund_records']['reorder'])] ? $table_settings['emergency_fund_records']['sizes'][array_search($field->field, $table_settings['emergency_fund_records']['reorder'])].'px' : ''),
                                        'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                    );
                                }
                                if(isset($table_settings['emergency_fund_records']))
                                  $start_columns = array_merge(array_flip($table_settings['emergency_fund_records']['reorder']), $start_columns);
                                @endphp
                                @foreach($start_columns as $col)
                                  @if($col['name'] == 'select')
                                    <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 40px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        <div class="form-checkbox">
                                          <label class="form-checkbox__label" for="mainCheckbox">
                                            <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                            <span class="form-checkbox__switcher"></span>
                                          </label>
                                        </div>
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>
                                      </div>
                                      <span class="table-header__label">Выделение</span>
                                    </th>
                                  @else
                                    <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                    @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 130px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        {!! $col['display_name'] !!}
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>

                                      </div>
                                    </th>
                                  @endif
                                @endforeach
                              </tr>
                            </thead>
                            <tbody class="table-body">
                                @foreach($current->emergency_fund_records as $object)
                                <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">
                                    @php
                                    $start_columns = array(
                                    );
                                    foreach($fund_fields as $field) {
                                        $start_columns['select'] = array(
                                            'name' => 'select',
                                            'fix' => (isset($table_settings['emergency_fund_records']) && in_array('select', $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array('select', $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['emergency_fund_records']) && $table_settings['emergency_fund_records']['sizes'][array_search('select', $table_settings['emergency_fund_records']['reorder'])] ? $table_settings['emergency_fund_records']['sizes'][array_search('select', $table_settings['emergency_fund_records']['reorder'])].'px' : ''),

                                        );
                                        $start_columns['actions'] = array(
                                            'name' => 'actions',
                                            'display_name' => 'Действие',
                                            'fix' => (isset($table_settings['emergency_fund_records']) && in_array('actions', $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array('actions', $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['emergency_fund_records']) && $table_settings['emergency_fund_records']['sizes'][array_search('actions', $table_settings['emergency_fund_records']['reorder'])] ? $table_settings['emergency_fund_records']['sizes'][array_search('actions', $table_settings['emergency_fund_records']['reorder'])].'px' : ''),
                                        );
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['emergency_fund_records']) && in_array($field->field, $table_settings['emergency_fund_records']['fix']) ? 1 : 0),
                                            'type' => $field->type,
                                            'only_read' => $field->only_read,
                                            'hidden' => (isset($table_settings['emergency_fund_records']) && !in_array($field->field, $table_settings['emergency_fund_records']['visible']) ? 1 : 0),
                                            'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                            'is_date' => ($field->type == 'date'),
                                        );
                                    }
                                    if(isset($table_settings['emergency_fund_records'])) {
                                      
                                      $start_columns = array_merge(array_flip($table_settings['emergency_fund_records']['reorder']), $start_columns);
                                    }
                                    @endphp
                                    @foreach($start_columns as $col)
                                    @if($col['name'] == 'select')
                                    <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="form-checkbox">
                                        <label class="form-checkbox__label">
                                          <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                          <span class="form-checkbox__switcher"></span>
                                        </label>
                                      </div>
                                    </td>
                                    @elseif($col['name'] == 'actions')
                                    <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="table-options__inner">
                                        <div class="dropdown" data-dropdown>
                                          <button class="table-options__btn btn-clear" data-dropdown="btn">
                                            <svg width="3" height="13" fill="none">
                                              <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                            </svg>
                                          </button>
                                          <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                            <ul class="dropdown__list">
                                              <li class="dropdown__item">
                                                <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="emergency_fund_records" type="button">Редактировать</button>
                                                <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="emergency_fund_records" type="button" data-delete>Удалить</button>
                                              </li>
                                            </ul>
                                          </div>
                                        </div>
                                      </div>
                                    </td>
                                    @else
                                    <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                        @if($col['is_files'])
                                        {!! $object[$col['name']] !!}
                                        @else
                                        <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                          {!! $object[$col['name']] !!}
                                        </div>
                                        @endif
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                          </table><!-- /.table__inner -->
                        </div><!-- /.table__wrapper -->
                        <div class="table-footer">
                          <div class="table-footer__title">
                            Отмечено: <span class="table-footer__counter" id="checkboxCounter">0</span>
                          </div>
                          <div class="pagination">
                            <span class="pagination__title table-footer__title">Страница:</span>
                            <ul class="pagination__list"></ul>
                          </div>
                          <div class="table-footer__show">
                            <span class="table-footer__title sm-hidden">На странице:</span>
                            <div class="dropdown" data-dropdown>
                              <button class="select btn-clear" data-dropdown="btn" id="paginationSelect">
                                <span class="select-current">25</span>
                                <svg width="12" height="7" fill="none">
                                  <path fill-rule="evenodd" d="M11.77 1.2 6.15 6.91S6.04 7 5.86 7s-.3-.09-.3-.09L.1 1.21S-.14.7.22.3s.9-.23.9-.23l4.72 4.85L10.6.08s.5-.22.94.18c.45.4.23.94.23.94Z" clip-rule="evenodd" />
                                </svg>
                              </button>
                              <div class="dropdown__menu dropdown__menu_select" data-dropdown="menu">
                                <ul class="dropdown__list">
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="25">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="50">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="100">
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div><!-- /.table-footer -->
                    </div><!-- /.table -->
                </div>
            </div>
        </div>
        @elseif($slug == 'cars')
        <div id="journal" class="tab-content" style="display:none">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table">
                        <div class="table-top">
                          <button class="table__change-btn btn-clear" type="button">Сохранить изменения</button>
                          <div class="dropdown" data-dropdown>
                            <button class="table__settings btn-clear" type="button" data-dropdown="btn">
                              <svg width="15" height="15" fill="none">
                                <path fill-rule="evenodd" d="M14.25 5.979a.75.75 0 0 1 .75.75v1.542a.75.75 0 0 1-.75.75h-.952a5.954 5.954 0 0 1-.622 1.504l.672.673a.75.75 0 0 1 0 1.06l-1.09 1.09a.75.75 0 0 1-1.06 0l-.674-.672a5.957 5.957 0 0 1-1.503.622v.952a.75.75 0 0 1-.75.75H6.729a.75.75 0 0 1-.75-.75v-.952a5.953 5.953 0 0 1-1.504-.622l-.672.673a.75.75 0 0 1-1.061 0l-1.09-1.09a.75.75 0 0 1 0-1.061l.672-.673a5.953 5.953 0 0 1-.622-1.504H.75a.75.75 0 0 1-.75-.75V6.73a.75.75 0 0 1 .75-.75h.952c.14-.534.35-1.038.622-1.503l-.673-.673a.75.75 0 0 1 0-1.06l1.09-1.091a.75.75 0 0 1 1.061 0l.673.672a5.98 5.98 0 0 1 1.504-.622V.75a.75.75 0 0 1 .75-.75H8.27a.75.75 0 0 1 .75.75v.952c.534.14 1.038.35 1.503.622l.673-.672a.75.75 0 0 1 1.061 0l1.09 1.09a.75.75 0 0 1 0 1.06l-.672.673c.272.465.482.97.622 1.504h.952ZM7.5 10.252a2.752 2.752 0 1 0 0-5.504 2.752 2.752 0 0 0 0 5.504Z" clip-rule="evenodd" />
                              </svg>
                            </button>
                            <div class="dropdown__menu dropdown__menu_right" data-dropdown="menu">
                              <ul class="dropdown__list">
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Отображение столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="displayMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Фиксирование столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="fixMenu"></ul>
                                </li>
                                <li class="dropdown__item">
                                  <button class="dropdown__link" type="button" data-dropdown="subBtn">
                                    Порядок столбцов
                                    <img class="dropdown__arrow" src="{{ asset('img/icons/arrow-right.svg') }}" alt="Open menu icon">
                                  </button>
                                  <ul class="dropdown__submenu table__menu" data-dropdown="submenu" id="orderMenu" data-dragName="orderMenu" data-drag="area"></ul>
                                </li>
                              </ul>
                            </div>
                          </div>
                        </div>
                        <div class="table__wrapper">
                          <table class="table__inner js-entity-table" data-model="journal_records">
                            <thead class="table-header">
                              <tr class="table-header__row">
                                @php
                                $start_columns = array(
                                );
                                $start_columns['select'] = array(
                                    'name' => 'select',
                                    'display_name' => 'Выделение строки',
                                    'fix' => (isset($table_settings['journal_records']) && in_array('select', $table_settings['journal_records']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['journal_records']) && !in_array('select', $table_settings['journal_records']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['journal_records']) && $table_settings['journal_records']['sizes'][array_search('select', $table_settings['journal_records']['reorder'])] ? $table_settings['journal_records']['sizes'][array_search('select', $table_settings['journal_records']['reorder'])].'px' : ''),
                                    'write_perm' => 1

                                );
                                $start_columns['actions'] = array(
                                    'name' => 'actions',
                                    'display_name' => 'Действие',
                                    'fix' => (isset($table_settings['journal_records']) && in_array('actions', $table_settings['journal_records']['fix']) ? 1 : 0),
                                    'hidden' => (isset($table_settings['journal_records']) && !in_array('actions', $table_settings['journal_records']['visible']) ? 1 : 0),
                                    'width' => (isset($table_settings['journal_records']) && $table_settings['journal_records']['sizes'][array_search('actions', $table_settings['journal_records']['reorder'])] ? $table_settings['journal_records']['sizes'][array_search('actions', $table_settings['journal_records']['reorder'])].'px' : ''),
                                    'write_perm' => 1
                                );
                                foreach($record_fields as $field) {
                                    $start_columns[$field->field] = array(
                                        'name' => $field->field,
                                        'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                        'fix' => (isset($table_settings['journal_records']) && in_array($field->field, $table_settings['journal_records']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['journal_records']) && !in_array($field->field, $table_settings['journal_records']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['journal_records']) && $table_settings['journal_records']['sizes'][array_search($field->field, $table_settings['journal_records']['reorder'])] ? $table_settings['journal_records']['sizes'][array_search($field->field, $table_settings['journal_records']['reorder'])].'px' : ''),
                                        'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                    );
                                }
                                if(isset($table_settings['journal_records']))
                                  $start_columns = array_merge(array_flip($table_settings['journal_records']['reorder']), $start_columns);
                                @endphp
                                @foreach($start_columns as $col)
                                  @if($col['name'] == 'select')
                                    <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 40px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        <div class="form-checkbox">
                                          <label class="form-checkbox__label" for="mainCheckbox">
                                            <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                            <span class="form-checkbox__switcher"></span>
                                          </label>
                                        </div>
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>
                                      </div>
                                      <span class="table-header__label">Выделение</span>
                                    </th>
                                  @else
                                    <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                    @if($col['width'])
                                    style="width: {{ $col['width'] }};"
                                    @else
                                    style="width: 130px;"
                                    @endif>
                                      <div class="table-header__inner">
                                        {!! $col['display_name'] !!}
                                        <button class="table-header__filter-btn btn-clear" type="button">
                                          <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                        </button>

                                      </div>
                                    </th>
                                  @endif
                                @endforeach
                              </tr>
                            </thead>
                            <tbody class="table-body">
                                @foreach($current->journal_records as $object)
                                <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">
                                    @php
                                    $start_columns = array(
                                    );
                                    foreach($record_fields as $field) {
                                        $start_columns['select'] = array(
                                            'name' => 'select',
                                            'fix' => (isset($table_settings['journal_records']) && in_array('select', $table_settings['journal_records']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['journal_records']) && !in_array('select', $table_settings['journal_records']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['journal_records']) && $table_settings['journal_records']['sizes'][array_search('select', $table_settings['journal_records']['reorder'])] ? $table_settings['journal_records']['sizes'][array_search('select', $table_settings['journal_records']['reorder'])].'px' : ''),

                                        );
                                        $start_columns['actions'] = array(
                                            'name' => 'actions',
                                            'display_name' => 'Действие',
                                            'fix' => (isset($table_settings['journal_records']) && in_array('actions', $table_settings['journal_records']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['journal_records']) && !in_array('actions', $table_settings['journal_records']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['journal_records']) && $table_settings['journal_records']['sizes'][array_search('actions', $table_settings['journal_records']['reorder'])] ? $table_settings['journal_records']['sizes'][array_search('actions', $table_settings['journal_records']['reorder'])].'px' : ''),
                                        );
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['journal_records']) && in_array($field->field, $table_settings['journal_records']['fix']) ? 1 : 0),
                                            'type' => $field->type,
                                            'only_read' => $field->only_read,
                                            'hidden' => (isset($table_settings['journal_records']) && !in_array($field->field, $table_settings['journal_records']['visible']) ? 1 : 0),
                                            'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                            'is_date' => ($field->type == 'date'),
                                        );
                                    }
                                    if(isset($table_settings['journal_records'])) {
                                      
                                      $start_columns = array_merge(array_flip($table_settings['journal_records']['reorder']), $start_columns);
                                    }
                                    @endphp
                                    @foreach($start_columns as $col)
                                    @if($col['name'] == 'select')
                                    <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="form-checkbox">
                                        <label class="form-checkbox__label">
                                          <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                          <span class="form-checkbox__switcher"></span>
                                        </label>
                                      </div>
                                    </td>
                                    @elseif($col['name'] == 'actions')
                                    <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="table-options__inner">
                                        <div class="dropdown" data-dropdown>
                                          <button class="table-options__btn btn-clear" data-dropdown="btn">
                                            <svg width="3" height="13" fill="none">
                                              <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                            </svg>
                                          </button>
                                          <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                            <ul class="dropdown__list">
                                              <li class="dropdown__item">
                                                <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="journal_records" type="button">Редактировать</button>
                                                <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="journal_records" type="button" data-delete>Удалить</button>
                                              </li>
                                            </ul>
                                          </div>
                                        </div>
                                      </div>
                                    </td>
                                    @else
                                    <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                        @if($col['is_files'])
                                        {!! $object[$col['name']] !!}
                                        @else
                                        <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                          {!! $object[$col['name']] !!}
                                        </div>
                                        @endif
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                          </table><!-- /.table__inner -->
                        </div><!-- /.table__wrapper -->
                        <div class="table-footer">
                          <div class="table-footer__title">
                            Отмечено: <span class="table-footer__counter" id="checkboxCounter">0</span>
                          </div>
                          <div class="pagination">
                            <span class="pagination__title table-footer__title">Страница:</span>
                            <ul class="pagination__list"></ul>
                          </div>
                          <div class="table-footer__show">
                            <span class="table-footer__title sm-hidden">На странице:</span>
                            <div class="dropdown" data-dropdown>
                              <button class="select btn-clear" data-dropdown="btn" id="paginationSelect">
                                <span class="select-current">25</span>
                                <svg width="12" height="7" fill="none">
                                  <path fill-rule="evenodd" d="M11.77 1.2 6.15 6.91S6.04 7 5.86 7s-.3-.09-.3-.09L.1 1.21S-.14.7.22.3s.9-.23.9-.23l4.72 4.85L10.6.08s.5-.22.94.18c.45.4.23.94.23.94Z" clip-rule="evenodd" />
                                </svg>
                              </button>
                              <div class="dropdown__menu dropdown__menu_select" data-dropdown="menu">
                                <ul class="dropdown__list">
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="25">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="50">
                                  </li>
                                  <li class="dropdown__item">
                                    <input class="dropdown__link select__input" type="button" value="100">
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div><!-- /.table-footer -->
                    </div><!-- /.table -->
                </div>
            </div>
        </div>
        <div id="products" class="tab-content" style="display:none">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table__wrapper">
                        <table class="table__inner js-entity-table" data-model="remnants">
                            <thead class="table-header">
                                <tr class="table-header__row">
                                    @php
                                    $start_columns = array(
                                    );
                                    $start_columns['select'] = array(
                                        'name' => 'select',
                                        'display_name' => 'Выделение строки',
                                        'fix' => (isset($table_settings['remnants']) && in_array('select', $table_settings['remnants']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['remnants']) && !in_array('select', $table_settings['remnants']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])].'px' : ''),
                                        'write_perm' => 1

                                    );
                                    $start_columns['actions'] = array(
                                        'name' => 'actions',
                                        'display_name' => 'Действие',
                                        'fix' => (isset($table_settings['remnants']) && in_array('actions', $table_settings['remnants']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['remnants']) && !in_array('actions', $table_settings['remnants']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])].'px' : ''),
                                        'write_perm' => 1
                                    );
                                    foreach($remnant_fields as $field) {
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['remnants']) && in_array($field->field, $table_settings['remnants']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['remnants']) && !in_array($field->field, $table_settings['remnants']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search($field->field, $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search($field->field, $table_settings['remnants']['reorder'])].'px' : ''),
                                            'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                        );
                                    }
                                    if(isset($table_settings['remnants']))
                                      $start_columns = array_merge(array_flip($table_settings['remnants']['reorder']), $start_columns);
                                    @endphp
                                    @foreach($start_columns as $col)
                                      @if($col['name'] == 'select')
                                        <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                        style="width: {{ $col['width'] }};"
                                        @else
                                        style="width: 40px;"
                                        @endif>
                                          <div class="table-header__inner">
                                            <div class="form-checkbox">
                                              <label class="form-checkbox__label" for="mainCheckbox">
                                                <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                                <span class="form-checkbox__switcher"></span>
                                              </label>
                                            </div>
                                            <button class="table-header__filter-btn btn-clear" type="button">
                                              <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                            </button>
                                          </div>
                                          <span class="table-header__label">Выделение</span>
                                        </th>
                                      @else
                                        <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                        @if($col['width'])
                                        style="width: {{ $col['width'] }};"
                                        @else
                                        style="width: 130px;"
                                        @endif>
                                          <div class="table-header__inner">
                                            {!! $col['display_name'] !!}
                                            <button class="table-header__filter-btn btn-clear" type="button">
                                              <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                            </button>

                                          </div>
                                        </th>
                                      @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($remnants as $object)
                                <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">          
                                @php
                                    $start_columns = array(
                                    );
                                    $start_columns['select'] = array(
                                        'name' => 'select',
                                        'fix' => (isset($table_settings['remnants']) && in_array('select', $table_settings['remnants']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['remnants']) && !in_array('select', $table_settings['remnants']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('select', $table_settings['remnants']['reorder'])].'px' : ''),

                                    );
                                    $start_columns['actions'] = array(
                                        'name' => 'actions',
                                        'display_name' => 'Действие',
                                        'fix' => (isset($table_settings['remnants']) && in_array('actions', $table_settings['remnants']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['remnants']) && !in_array('actions', $table_settings['remnants']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['remnants']) && $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])] ? $table_settings['remnants']['sizes'][array_search('actions', $table_settings['remnants']['reorder'])].'px' : ''),
                                    );
                                    foreach($remnant_fields as $field) {
                                        
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['remnants']) && in_array($field->field, $table_settings['remnants']['fix']) ? 1 : 0),
                                            'type' => $field->type,
                                            'only_read' => $field->only_read,
                                            'hidden' => (isset($table_settings['remnants']) && !in_array($field->field, $table_settings['remnants']['visible']) ? 1 : 0),
                                            'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                            'is_date' => ($field->type == 'date'),
                                        );
                                    }
                                    if(isset($table_settings['remnants'])) {
                                      
                                      $start_columns = array_merge(array_flip($table_settings['remnants']['reorder']), $start_columns);
                                    }
                                    @endphp
                                    @foreach($start_columns as $col)
                                    @if($col['name'] == 'select')
                                    <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="form-checkbox">
                                        <label class="form-checkbox__label">
                                          <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                          <span class="form-checkbox__switcher"></span>
                                        </label>
                                      </div>
                                    </td>
                                    @elseif($col['name'] == 'actions')
                                    <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="table-options__inner">
                                        <div class="dropdown" data-dropdown>
                                          <button class="table-options__btn btn-clear" data-dropdown="btn">
                                            <svg width="3" height="13" fill="none">
                                              <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                            </svg>
                                          </button>
                                          <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                            <ul class="dropdown__list">
                                              <li class="dropdown__item">
                                                <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="remnants" type="button">Редактировать</button>
                                                <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="remnants" type="button" data-delete>Удалить</button>
                                              </li>
                                            </ul>
                                          </div>
                                        </div>
                                      </div>
                                    </td>
                                    @else
                                    <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                        @if($col['is_files'])
                                        {!! $object[$col['name']] !!}
                                        @else
                                        <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                          {!! $object[$col['name']] !!}
                                        </div>
                                        @endif
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div id="mileages" class="tab-content" style="display:none">
            <div class="row">
                <div class="col-lg-12">
                    <div class="table__wrapper">
                        <table class="table__inner js-entity-table" data-model="mileages">
                            <thead class="table-header">
                                <tr class="table-header__row">
                                    @php
                                    $start_columns = array(
                                    );
                                    $start_columns['select'] = array(
                                        'name' => 'select',
                                        'display_name' => 'Выделение строки',
                                        'fix' => (isset($table_settings['mileages']) && in_array('select', $table_settings['mileages']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['mileages']) && !in_array('select', $table_settings['mileages']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['mileages']) && $table_settings['mileages']['sizes'][array_search('select', $table_settings['mileages']['reorder'])] ? $table_settings['mileages']['sizes'][array_search('select', $table_settings['mileages']['reorder'])].'px' : ''),
                                        'write_perm' => 1

                                    );
                                    $start_columns['actions'] = array(
                                        'name' => 'actions',
                                        'display_name' => 'Действие',
                                        'fix' => (isset($table_settings['mileages']) && in_array('actions', $table_settings['mileages']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['mileages']) && !in_array('actions', $table_settings['mileages']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['mileages']) && $table_settings['mileages']['sizes'][array_search('actions', $table_settings['mileages']['reorder'])] ? $table_settings['mileages']['sizes'][array_search('actions', $table_settings['mileages']['reorder'])].'px' : ''),
                                        'write_perm' => 1
                                    );
                                    foreach($remnant_fields as $field) {
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['mileages']) && in_array($field->field, $table_settings['mileages']['fix']) ? 1 : 0),
                                            'hidden' => (isset($table_settings['mileages']) && !in_array($field->field, $table_settings['mileages']['visible']) ? 1 : 0),
                                            'width' => (isset($table_settings['mileages']) && $table_settings['mileages']['sizes'][array_search($field->field, $table_settings['mileages']['reorder'])] ? $table_settings['mileages']['sizes'][array_search($field->field, $table_settings['mileages']['reorder'])].'px' : ''),
                                            'write_perm' => !$field->only_read && (isset($perms['write'][$field->field]) && $perms['write'][$field->field] != 'disabled' || $is_admin)
                                        );
                                    }
                                    if(isset($table_settings['mileages']))
                                      $start_columns = array_merge(array_flip($table_settings['mileages']['reorder']), $start_columns);
                                    @endphp
                                    @foreach($start_columns as $col)
                                      @if($col['name'] == 'select')
                                        <th class="table-header__item @if($col['fix']) sticky-start @endif " data-name="select" draggable="true"  @if($col['width'])
                                        style="width: {{ $col['width'] }};"
                                        @else
                                        style="width: 40px;"
                                        @endif>
                                          <div class="table-header__inner">
                                            <div class="form-checkbox">
                                              <label class="form-checkbox__label" for="mainCheckbox">
                                                <input class="form-checkbox__input" type="checkbox" id="mainCheckbox">
                                                <span class="form-checkbox__switcher"></span>
                                              </label>
                                            </div>
                                            <button class="table-header__filter-btn btn-clear" type="button">
                                              <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                            </button>
                                          </div>
                                          <span class="table-header__label">Выделение</span>
                                        </th>
                                      @else
                                        <th class="table-header__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if(!$col['write_perm']) text-gray @endif" data-name="{{ $col['name'] }}" draggable="true" data-width="{{ $col['width'] }}"
                                        @if($col['width'])
                                        style="width: {{ $col['width'] }};"
                                        @else
                                        style="width: 130px;"
                                        @endif>
                                          <div class="table-header__inner">
                                            {!! $col['display_name'] !!}
                                            <button class="table-header__filter-btn btn-clear" type="button">
                                              <img class="dropdown__icon" src="{{ asset('img/icons/dropdown.svg') }}" alt="Dropdown icon">
                                            </button>

                                          </div>
                                        </th>
                                      @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mileages as $object)
                                <tr id="item-{{ $object['id'] }}" class="table-body__row" data-id="{{ $object['id'] }}">          
                                @php
                                    $start_columns = array(
                                    );
                                    $start_columns['select'] = array(
                                        'name' => 'select',
                                        'fix' => (isset($table_settings['mileages']) && in_array('select', $table_settings['mileages']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['mileages']) && !in_array('select', $table_settings['mileages']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['mileages']) && $table_settings['mileages']['sizes'][array_search('select', $table_settings['mileages']['reorder'])] ? $table_settings['mileages']['sizes'][array_search('select', $table_settings['mileages']['reorder'])].'px' : ''),

                                    );
                                    $start_columns['actions'] = array(
                                        'name' => 'actions',
                                        'display_name' => 'Действие',
                                        'fix' => (isset($table_settings['mileages']) && in_array('actions', $table_settings['mileages']['fix']) ? 1 : 0),
                                        'hidden' => (isset($table_settings['mileages']) && !in_array('actions', $table_settings['mileages']['visible']) ? 1 : 0),
                                        'width' => (isset($table_settings['mileages']) && $table_settings['mileages']['sizes'][array_search('actions', $table_settings['mileages']['reorder'])] ? $table_settings['mileages']['sizes'][array_search('actions', $table_settings['mileages']['reorder'])].'px' : ''),
                                    );
                                    foreach($remnant_fields as $field) {
                                        
                                        $start_columns[$field->field] = array(
                                            'name' => $field->field,
                                            'display_name' => ($field->display_parent_name ? $field->display_parent_name.'<br> ':'').$field->display_name,
                                            'fix' => (isset($table_settings['mileages']) && in_array($field->field, $table_settings['mileages']['fix']) ? 1 : 0),
                                            'type' => $field->type,
                                            'only_read' => $field->only_read,
                                            'hidden' => (isset($table_settings['mileages']) && !in_array($field->field, $table_settings['mileages']['visible']) ? 1 : 0),
                                            'is_files' => ($field->type == 'file' || $field->type == 'image'),
                                            'is_date' => ($field->type == 'date'),
                                        );
                                    }
                                    if(isset($table_settings['mileages'])) {
                                      
                                      $start_columns = array_merge(array_flip($table_settings['mileages']['reorder']), $start_columns);
                                    }
                                    @endphp
                                    @foreach($start_columns as $col)
                                    @if($col['name'] == 'select')
                                    <td class="table-body__item @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="form-checkbox">
                                        <label class="form-checkbox__label">
                                          <input class="form-checkbox__input" type="checkbox" data-checkbox>
                                          <span class="form-checkbox__switcher"></span>
                                        </label>
                                      </div>
                                    </td>
                                    @elseif($col['name'] == 'actions')
                                    <td class="table-body__item table-options @if($col['fix']) sticky-start @endif" data-field="{{ $col['name'] }}">
                                      <div class="table-options__inner">
                                        <div class="dropdown" data-dropdown>
                                          <button class="table-options__btn btn-clear" data-dropdown="btn">
                                            <svg width="3" height="13" fill="none">
                                              <path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd" />
                                            </svg>
                                          </button>
                                          <div class="dropdown__menu dropdown__menu_align dropdown__menu_sm-lh" data-dropdown="menu">
                                            <ul class="dropdown__list">
                                              <li class="dropdown__item">
                                                <button class="dropdown__link js-edit-model" data-id="{{ $object['id'] }}" data-model="mileages" type="button">Редактировать</button>
                                                <button class="dropdown__link dropdown__link_red js-delete-model" data-id="{{ $object['id'] }}" data-model="mileages" type="button" data-delete>Удалить</button>
                                              </li>
                                            </ul>
                                          </div>
                                        </div>
                                      </div>
                                    </td>
                                    @else
                                    <td class="table-body__item @if($col['hidden']) hidden @endif @if($col['fix']) sticky-start @endif @if($col['is_date']) date-field @endif field-{{ $col['type'] }}" data-field="{{ $col['name'] }}">
                                        @if($col['is_files'])
                                        {!! $object[$col['name']] !!}
                                        @else
                                        <div class="table-body__inner @if(!$col['only_read']) table-edit__field @endif" data-f="{{ $col['name'] }}">
                                          {!! $object[$col['name']] !!}
                                        </div>
                                        @endif
                                    </td>
                                    @endif
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div id="relations" class="tab-content" style="display:none">
            <ul class="relations-tree">
            @php
            

            \Modules\Journal\Entities\EntityRelation::fixTree();
            //$nodes = \Modules\Journal\Entities\EntityRelation::descendantsAndSelf(2)->toTree();
            $node = \Modules\Journal\Entities\EntityRelation::where('entity_id', $current->id)->first();
            //$node = \Modules\Journal\Entities\EntityRelation::find(2);

            function display_with_children($category, $level) { 
                echo '<li class="'.(count($category->children) < 1 ? 'empty' : '').'"><div class="relation-item"><span class="relation-item__name">'.$category->entity_name.':</span><span class="relation-item__date"> '.$category->created_at.'</span>'; 
                echo '<div class="relation-item__field">';
                echo '<span class="gray">Название:</span> ОПТ6, Длиномер, Н044СУ790';
                echo '</div>';
                echo '<div class="relation-item__field">';
                echo '<span class="gray">ID:</span> '.$category->entity_id;
                echo '</div>';
                echo '</div>';
                if ($children = $category->children) {
                    echo '<ul>';
                    foreach($children as $child) 
                        display_with_children($child, $level+1); 
                    echo '</ul>';
                }
                echo '</li>';
            }
            if($node)
                display_with_children($node, 0);
            @endphp
            </ul>
            <style type="text/css">
                p.relations-tree,
                ul.relations-tree,
                ul.relations-tree ul {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }

                ul.relations-tree ul {
                    margin-left: 1.0em;
                }

                .relations-tree-intro,
                ul.relations-tree li {
                    position: relative;

                    margin-left: 0;
                    padding-left: 1em;
                    margin-top: 0;
                    margin-bottom: 0;

                    border-left: thin solid #e8e8e8;
                }

                ul.relations-tree li:last-child {
                    border-left: none;
                }

                ul.relations-tree li:before {
                    position: absolute;
                    top: 52px;
                    left: 0;
                    width: 50px;
                    height: 120px;
                    vertical-align: top;
                    border-bottom: thin solid #e8e8e8;
                    content: "";
                    display: inline-block;
                    /*
                    position: absolute;
                    top: 0;
                    left: 0;

                    width: 0.5em; 
                    height: 0.5em; 
                    vertical-align: top;
                    border-bottom: thin solid #e8e8e8;
                    content: "";
                    display: inline-block;
                    */
                }

                ul.relations-tree li:last-child:before {
                    border-left: thin solid #e8e8e8;
                }
                li.empty:before {
                    display: none!important;
                }
                .relation-item {
                    padding: 15px;
                    border-radius: 10px;
                    box-shadow: 0 5px 20px 0 rgba(0, 0, 0, 0.07);
                    border: solid 1px #eeeff1;
                    background-color: #fff;
                    height: 105px;
                    margin-bottom: 15px;
                }
                .relation-item__name {
                    display: inline-block;
                    font-size: 14px;
                    font-weight: 600;
                    font-stretch: normal;
                    font-style: normal;
                    line-height: normal;
                    letter-spacing: normal;
                    color: #161616;
                    margin-bottom: 8px;
                }
                .relation-item__date {
                    font-size: 14px;
                    font-weight: normal;
                    font-stretch: normal;
                    font-style: normal;
                    line-height: normal;
                    letter-spacing: normal;
                    color: #8f8f8f;
                }
                .relation-item__field {
                    font-size: 14px;
                    font-weight: normal;
                    font-stretch: normal;
                    font-style: normal;
                    line-height: normal;
                    letter-spacing: normal;
                    color: #161616;
                    margin-bottom: 8px;
                }
                .relation-item__field .gray {
                    color: #8f8f8f;
                }
            </style>
        </div>
        @endif
    </div>
</div>

<div class="js-save-panel-products save-panel" style="display: none;left:0">
    <button type="submit" class="js-submit-products blue-btn">Сохранить</button>
    <button class="gray-btn js-reset-fields-products">Отменить</button>
</div>
@if(isset($settings['pages']['perms']['write_'.$slug]) && $settings['pages']['perms']['write_'.$slug] != 'disabled' || $is_admin)
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
                console.log('input')
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
    function updateContentProductsOrder() {
        let url = new URL(location.href);
        let params = new URLSearchParams(url.search.slice(1));

        $.ajax({
            type: 'get',
            url: url,
            success: function(e) {
                var t = $($.parseHTML(e)),
                    n = t.find("#products").html();
                $('#products').html(n);
            }
        });
        
    }
    function calculateProductsSum(table) {
        var sum = count = weight = 0,
            tr;
            console.log('calc')
        table.find('[name="sum"]').each(function(){
            tr = $(this).closest('tr');
            $(this).val(parseInt(tr.find('[name="price"]').val())*parseInt(tr.find('[name="count"]').val()))
            if(isNaN($(this).val()))
                $(this).val('');
            sum+=parseInt($(this).val());
        });
        table.find('[name="count"]').each(function(){
            count+=parseInt($(this).val());
        });
        table.find('[name="weight"]').each(function(){
            weight+=parseInt($(this).val())*parseInt($(this).closest('tr').find('[name="count"]').val());
        });
        if(isNaN(sum))
            sum = 0;
        if(isNaN(count))
            count = 0;
        if(isNaN(weight))
            weight = 0;

        table.closest('.tab-content').find('.js-products-sum').text(sum);
        table.closest('.tab-content').find('.js-products-count').text(count);
        table.closest('.tab-content').find('.js-products-weight').text(weight);
    }
    $(document).ready(function(){
        ymaps.ready(map_init, init);
        $('body').on('click', '.js-add-expenserow', function(e) {
            e.preventDefault();
            var new_expense = $('.expenses-order-table tbody tr:last-child').clone();
            new_expense.find('input').each(function(){
                $(this).val('');
            })
            $('.expenses-order-table tbody').append(new_expense)
        });
        $('body').on('click', '.js-add-productrow', function(e) {
            e.preventDefault();
            var new_product = $('.products-order-table tbody tr:last-child').clone();
            new_product.find('input').each(function(){
                $(this).val('');
            })
            $('.products-order-table tbody').append(new_product)
        });
        $('body').on('keyup', '.js-search-name', function(e) {
            var q = $(this).val();
            if(q.length > 3) {
                console.log(q)
                $.ajax({
                    type: 'get',
                    async: false,
                    url: '/products/search',
                    data: { q: q },
                    success: function(data) {
                        $('.js-search-results').html(data)
                    }
                });
            } 
        });
        $('body').on('change', '.js-sort-products input', function(){
            var table = $(this).closest('table');
            console.log('change')
            calculateProductsSum(table);
            $('.js-save-panel-products').show();
        });
        $('body').on('click', '.js-reset-fields-products', function(){
            updateContentProductsOrder();
            $('.js-save-panel-products').hide();
        });
        let focus_input;
        $('body').on('focus', '.js-search-name', function(e) {
            var top = $(this).offset().top,
                left = $(this).offset().left;
            focus_input = $(this);
            $('.js-search-results').removeClass('d-none');
            $('.js-search-results').html('');
            $('.js-search-results').css({top: top+40, left: left, position:'absolute'});
        });
        $('body').on('focusout', '.js-search-name', function(e) {
            //$('.js-search-results').addClass('d-none')
        });
        $('body').on('click', '.search-product-item', function(e){
            var new_product = focus_input.closest('tr');
            new_product.find('input').each(function(){
                $(this).val('');
            });
            new_product.find('[name="id"]').val($(this).data('id'));
            new_product.find('[name="name"]').val($(this).data('name'));
            new_product.find('[name="product"]').val($(this).data('product'));
            new_product.find('[name="price"]').val($(this).data('price'));
            new_product.find('[name="weight"]').val($(this).data('weight'));

            //calculateProductsSum()

            $('.products-order-table tbody').append(new_product);
            $('.js-search-results').removeClass('d-none');
            $('.js-search-results').html('');
        });
        $('body').on('click', '.js-delete-productrow', function(){
            $(this).closest('tr').remove();
            var table = $(this).closest('table');
            calculateProductsSum(table);
            $('.js-save-panel-products').show();
        });
        $(window).click(function(e){
            if(!e.target.closest('[name="name"]') && !e.target.closest('.js-search-results')) {
                $('.js-search-results').removeClass('d-none');
                $('.js-search-results').html('');
            }
        });
        $('body').on('click', '[data-toggle="tab"]', function(){
            $($('[data-toggle="tab"].active').attr('href')).hide();
            $('[data-toggle="tab"].active').removeClass('active');
            $(this).addClass('active');
            $($(this).attr('href')).show();
        });

        $("body").on('click', '.js-submit-products', function(){
            var model = $('.t-body').data('model');
            var data = {};
            data['_token'] = $('[name=_token]').val();
            data['_method'] = 'PUT';

            var products = [],
                expenses = [];

            $('.products-order-table tbody tr').each(function(){
                if($(this).find('[name="name"]').val().length) {
                    var product = {
                        'id': $(this).find('[name="id"]').val(),
                        'name': $(this).find('[name="name"]').val(),
                        'price': $(this).find('[name="price"]').val(),
                        'count': $(this).find('[name="count"]').val(),
                        'weight': $(this).find('[name="weight"]').val(),
                        'product': $(this).find('[name="product"]').val(),
                        'sum': $(this).find('[name="sum"]').val(),
                    };

                    products.push(product);
                }
            });
            if($('.expenses-order-table').length) {
                $('.expenses-order-table tbody tr').each(function(){
                    if($(this).find('[name="name"]').val().length) {
                        var expense = {
                            'name': $(this).find('[name="name"]').val(),
                            'desc': $(this).find('[name="desc"]').val(),
                            'price': $(this).find('[name="price"]').val(),
                            'count': $(this).find('[name="count"]').val(),
                            'sum': $(this).find('[name="sum"]').val(),
                        };

                        expenses.push(expense);
                    }
                });
                data['expenses'] = JSON.stringify(expenses);
            }

            data['products'] = JSON.stringify(products);

            console.log(data)
            $.ajax({
                type: 'post',
                url: '/objects/{{ $slug }}/update/{{ $current->id }}',
                data: objectToQueryString(data),
                success: function(data) {
                    $('.js-save-panel-products').hide();
                    $('.blue-btn.disabled').removeClass('disabled');
                    updateContentProductsOrder();

                }
            });
        });

        $(".js-sort-products").sortable({
          group: 'no-drop',
          handle: '.btn-drag',
          update: function( event, ui ) {
            $('.js-save-panel-products').show();
          },

        });

        $('.js-add-remnant').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            var product = $(this).data('id');

            $.ajax({
                type: 'post',
                url: '/objects/remnants/store',
                async: false,
                data: {
                    '_token': $('input[name=_token]').val(),
                    'product_id': product 
                },
                success: function(res) {
                    ddajaxsidepanel.showhidepanel('/objects/remnants/show/'+res.id+'?ajax=y&create=Y', 'show', 'iframe');
                    
                }
            });

        });

        @if($slug == 'products')
        var table_settings,
            table_columns = [],
            table_columns_v = [],
            table_columns_fix = [],
            run = false;
        $.ajax({
              "url": "/state_load?user_id={{ Auth::user()->id }}&table=table_page_remnants",
              "async": false,
              "dataType": "json",
              "success": function (json) {
                table_settings = json;
                var columns = table_settings['columns'];
                
                $.each(columns, function(index, el) {
                    if(el['name']) {
                        table_columns[el['name']] = el['width'];
                        table_columns_v.push(el['name']);
                        if(el['is_sticky'] == 'true')
                            table_columns_fix.push(el['name']);
                    }
                });
              }
            });
        var table = $('#table').DataTable({
            dom: 'Rfrtilp',
            paging: true,
            pagingType: 'numbers',
            searching: false,
            stateSave: true,
            stateDuration: 1,
            @if(isset($table_settings))
            colReorder: {
                order: {!! json_encode(\App\Models\Settings::fixTableOrder(count($remnant_fields)+1, $table_settings['ColReorder'])) !!}
            },
            order: [[{{ $table_settings['order'][0][0] }}, '{{ $table_settings['order'][0][1] }}']],
            @else
            colReorder: true,
            @endif
            columnDefs: [
                {
                    targets: 0,
                    checkboxes: {
                       selectRow: true
                    }
                }
            ],
            selected: true,
            select: {
              style: 'multi'
            },
            autoWidth: false,
            lengthMenu: [ 25, 50, 75, 100 ],
            "stateSaveParams": function (settings, data) {
                var columns = data['columns'], k;
                $.each(columns, function(index, el) {
                    k = index+1;
                    data['columns'][index]['width'] = $('#table th:nth-child('+k+')').width();
                    data['columns'][index]['visible'] = data['columns'][index]['visible'];
                    data['columns'][index]['name'] = $('#table th:nth-child('+k+')').data('name');
                    data['columns'][index]['is_sticky'] = $('#table th:nth-child('+k+')').hasClass('sticky-head');
                });
            },
            "stateSaveCallback": function (settings, data) {
                if (run == true) {
                    $.ajax( {
                      "url": "/state_save",
                      "data": {'data': data, 'table': 'table_page_remnants','_token': $('input[name=_token]').val()},
                      "dataType": "json",
                      "type": "POST",
                      "success": function (res) {
                        console.log(res)
                      }
                    } );
                };
                run = false;
            },
            "stateLoadCallback": function (settings) {
                if(table_settings) {
                    var o = table_settings;
                    return o;
                } else 
                    return settings;
                
            },
            
            columns: [
                { "name": "checkbox", "data": "checkbox", "width": table_columns['checkbox']+'px' },
                @foreach($remnant_fields as $field)
                { "name": "{{ $field->field }}", "data": "{{ $field->field }}", "width": table_columns['{{ $field->field }}']+'px' },
                @endforeach
                { "name": "actions", "data": "actions", "width": table_columns['actions']+'px' },
            ],
            createdRow: function (row, data, dataIndex, cells) {
                $(row).attr('data-id', $(row).find('[data-field="number"]').data('id'));
            },
            language: {
                url: '/tenancy/assets/lang/Russian_orders.json'
            }
        });
        table.on('init', function(){
            $('[name="table_length"]').select2();
            $('.dataTables_info').appendTo('.toolbar-table');
            $('.dataTables_paginate').appendTo('.toolbar-table');
            $('.dataTables_length').appendTo('.toolbar-table');
            
            

            var colname,
                header_text,
                label_col,
                input_col,
                div_col;
            table.columns().every( function () {
                colname = $(this.header()).data('name');
                header_text = this.header().textContent;
                if(colname == 'actions')
                    header_text = 'Действия';
                if(colname !== undefined) {
                    input_col = $('<input type="checkbox">').attr('id', 'customCheck'+colname).attr('value', colname).addClass('custom-control-input column-toggle');
                    label_col = $('<label></label>').attr('for', 'customCheck'+colname).addClass('custom-control-label').text(header_text);
                    div_col = $('<div></div>').addClass('custom-control custom-checkbox');
                    div_col.append(input_col).append(label_col);
                    $('.table-vis').append($('<li></li>').append(div_col));
                    input_col = $('<input type="checkbox">').attr('id', 'customCheck-fix-'+colname).attr('value', colname).addClass('custom-control-input column-toggle-fix input-table-fix');
                    label_col = $('<label></label>').attr('for', 'customCheck-fix-'+colname).addClass('custom-control-label').text(header_text);
                    div_col = $('<div></div>').addClass('custom-control custom-checkbox');
                    div_col.append(input_col).append(label_col);
                    $('.table-fix').append($('<li></li>').append(div_col));

                    div_col = $('<div class="column-sort align-items-center mb-2" data-column="'+($(this.header()).data('column-index'))+'"><span class="me-2 btn-drag start-0 top-0 ui-sortable-handle" ><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="11px" height="12px" viewBox="0 0 11 12" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g  transform="translate(-257.000000, -95.000000)" fill="#A6B7D4"><g transform="translate(254.000000, 85.000000)"><path d="M4,10 L7,10 C7.55228475,10 8,10.4477153 8,11 C8,11.5522847 7.55228475,12 7,12 L4,12 C3.44771525,12 3,11.5522847 3,11 C3,10.4477153 3.44771525,10 4,10 Z M4,15 L7,15 C7.55228475,15 8,15.4477153 8,16 C8,16.5522847 7.55228475,17 7,17 L4,17 C3.44771525,17 3,16.5522847 3,16 C3,15.4477153 3.44771525,15 4,15 Z M4,20 L7,20 C7.55228475,20 8,20.4477153 8,21 C8,21.5522847 7.55228475,22 7,22 L4,22 C3.44771525,22 3,21.5522847 3,21 C3,20.4477153 3.44771525,20 4,20 Z M10,10 L13,10 C13.5522847,10 14,10.4477153 14,11 C14,11.5522847 13.5522847,12 13,12 L10,12 C9.44771525,12 9,11.5522847 9,11 C9,10.4477153 9.44771525,10 10,10 Z M10,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L10,17 C9.44771525,17 9,16.5522847 9,16 C9,15.4477153 9.44771525,15 10,15 Z M10,20 L13,20 C13.5522847,20 14,20.4477153 14,21 C14,21.5522847 13.5522847,22 13,22 L10,22 C9.44771525,22 9,21.5522847 9,21 C9,20.4477153 9.44771525,20 10,20 Z" /></g></g></g></svg></span>'+header_text+'</div>');
                    $('.column-table-order').append($('<li></li>').append(div_col));
                    $(".column-table-order").sortable({
                      group: 'no-drop',
                      handle: '.btn-drag',
                      update: function( event, ui ) {
                        var items = [];
                        var $item = ui.item;
                        $('.column-table-order .column-sort').each(function(index, el) {
                            items.push($(this).data('column'));
                            $(this).data('column', index);
                        });
                        table.colReorder.order(items);
                        select2init();
                      },

                    });
                   
                }
            } );
            $('body').on('click', '.column-toggle', function (e) {
                var index = $('.column-toggle[value="'+$(this).val()+'"]').closest('li').index();
                if($(this).prop('checked')) {
                    $('.column-toggle-fix[value="'+$(this).val()+'"]').closest('li').show();
                    $('.column-table-order .column-sort[data-column="'+index+'"]').removeClass('hide');
                } else {
                    $('.column-toggle-fix[value="'+$(this).val()+'"]').closest('li').hide();
                    $('.column-table-order .column-sort[data-column="'+index+'"]').addClass('hide');
                }
                $('.save-state').removeClass('d-none');
                var column = table.column($(this).val()+':name');
                column.visible(!column.visible());
            });
            $('.column-toggle').each(function(index, el) {
                if((table_columns_v.includes($(this).attr('value')) || table_columns_v.length == 0))
                    $('.column-toggle').eq(index).prop('checked', true);
                else {
                    var column = table.column($(this).attr('value')+':name');
                    column.visible(false);
                    $('.column-table-order .column-sort[data-column="'+index+'"]').addClass('hide');
                }
            })
            $('body').on('click', '.column-toggle-fix', function (e) {
                var width = 0, table = $(this).closest('.box').find('.dataTables_wrapper table');
                $('.save-state').removeClass('d-none');
                if($(this).prop('checked')) {
                    table.find('th[data-name="'+$(this).val()+'"]').addClass('sticky-head');
                    table.find('td div[data-field="'+$(this).val()+'"]').closest('td').addClass('sticky-td');
                    table.find('th.sticky-head').each(function(i, elem){
                        $(this).css({'left': width+'px'});
                        var indx = $(this).index();
                        table.find('tr').each(function(j, tr){
                            $(tr).find('td:eq('+indx+')').css({'left': width+'px'});
                        });
                        width = width + $(this).outerWidth();
                    });
                    table.find('tr').each(function(i, tr){
                        width = 0;
                        $(tr).find('td.sticky-td').each(function(j, td){
                            //$(td).css({'left': width+'px'});
                            width = width + $(td).outerWidth();
                        })
                    });
                } else {
                    table.find('th[data-name="'+$(this).val()+'"]').removeClass('sticky-head');
                    table.find('th[data-name="'+$(this).val()+'"]').css({'left': 'auto'});
                    table.find('td div[data-field="'+$(this).val()+'"]').closest('td').removeClass('sticky-td');
                    table.find('td div[data-field="'+$(this).val()+'"]').closest('td').css({'left': 'auto'});
                    table.find('th.sticky-head').each(function(i, elem){
                        $(this).css({'left': width+'px'});
                        var indx = $(this).index();
                        table.find('tr').each(function(j, tr){
                            $(tr).find('td:eq('+indx+')').css({'left': width+'px'});
                        });
                        width = width + $(this).outerWidth();
                    });
                    table.find('tr').each(function(i, tr){
                        width = 0;
                        $(tr).find('td.sticky-td').each(function(j, td){
                            width = width + $(td).outerWidth();
                        })
                    });
                }
                
                
            });
            $('.column-toggle-fix').each(function(index, el) {
                if(!table_columns_v.includes($(this).attr('value')) && table_columns_v.length != 0) {
                    $('.column-toggle-fix').eq(index).closest('li').hide();
                }
                if((table_columns_fix.includes($(this).attr('value')))) {
                    $('.column-toggle-fix').eq(index).trigger('click');
                }
                $('.save-state').addClass('d-none');
            })
            
        })

        var run_reorder = true;
        table.on('column-reorder', function(e, diff, edit) {
            if(!run_reorder) {
                $('.save-state').removeClass('d-none');
            }
        });
        table.on('column-resize.dt.mouseup', function(event, oSettings) {
            if(!run_reorder) {
                $('.save-state').removeClass('d-none');
            }
        });

        setTimeout(function(){run_reorder = false;select2init();}, 1000);

        $('.save-state').on('click', function(){
            run = true;
            table.state.save(run);
            run = false;
            $('.save-state').addClass('d-none');
        });
        $("body").on('dblclick', 'thead tr th', function(){
            var column = table.column($(this).data('name')+':name').index();
                       
            if (!$(this).hasClass('sorting_asc'))
                table.order([column, 'asc']).draw();
            else
                table.order([column, 'desc']).draw();
        });

        table.on('draw',function(){
            @if(isset($_GET['id']))
            $('#table_filter input').val('{{ request()->id }}').trigger('keyup');
            @endif
            var th_width = 0, table = $('.dataTables_wrapper table');
            $('.column-toggle-fix:checked').each(function(index, el) {
                var width = 0;
                table.find('td div[data-field="'+$(this).val()+'"]').closest('td').addClass('sticky-td');
                
            });
            
            table.find('th.sticky-head').each(function(i, elem){
                $(this).css({'left': th_width+'px'});
                var indx = $(this).index();
                table.find('tr').each(function(j, tr){
                    $(tr).find('td:eq('+indx+')').css({'left': th_width+'px'});
                });
                th_width = th_width + $(this).outerWidth();
            });
            $('.box').show();
            ddajaxsidepanel.init({
                targetselector: '[rel="ajaxpanel"]',
                ajaxloadgif: '/img/squareloading.gif', //full path to "loading" gif relative to document. When in doubt use absolute URL to image.
                fx: {dur:500, easing: 'easeInQuad'}, // dur: duration of slide effect (milliseconds), easing: 'ease_in_type_string'
                openamount:'80%', // Width of panel when fully opened (Percentage value relative to page, or pixel value
                openamount_minthreshold:'400px' //Minimum required width of panel (when fully opened)  before panel is shown. This prevents panel from being shown on small screens or devices.
            })
        });
        @endif
        $('body').on('click', '.js-add-relation-entity', function(e){
            e.preventDefault();
            var btn = $(this);
            
            $.ajax({
                type: 'post',
                url: '/relations/'+btn.data('parent')+'/'+btn.data('id')+'/'+btn.data('model'),
                data: {'_token': $('[name="_token"]').val()},
                success: function(data) {
                    location.reload()

                }
            });
        })
        
    });
</script>
@endsection
