@php
$settings = get_settings();

$is_admin = $settings['is_admin'] ?? Auth::user()->isAdmin();
$write_perm = isset($settings['pages']['perms']['write_'.$slug]) && $settings['pages']['perms']['write_'.$slug] != 'disabled' || $is_admin;
@endphp
<div class="c-body pt-3">
    <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">
        @if($section->all_fields && count($section->all_fields))
            @foreach($section->all_fields as $k => $field)
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
                <li class=" @if($field->type != 'status')col-lg-12 @endif {{ $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] == 'disabled' && !$is_admin) data-blocked="true" @endif @if($current->route_id && $field->field == 'date_delivery_status') data-disabled="true" @endif>
                    @if($field->field == 'address')
                    <div class="mb-2 card p-3 bg-light">
                    @endif
                        <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                            @if($write_perm)
                            <span class="btn btn-sort js-edit-position btn-drag btn-drag-field btn-xs p-0 text-muted">
                                <svg class="icon icon-linedot"><use xlink:href="#icon-linedot"></use></svg>
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
                                    @if($field->type != 'relation' && !$field->only_read)
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
                                    @if($field->type != 'relation' && !$field->only_read)
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
                            @if((request()->edit && $field->type != 'status' || request()->create && $field->type != 'status') && (isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['write'] != 'disabled' || $is_admin) && !$field->only_read)
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
<style type="text/css">
    ul {
        list-style: none;
    }
</style>