
<div class="c-body p-4">
    <div class="row mb-2 justify-content-between">
        <div class="col-lg-6">
            <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">

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
                    <li class="@if($field->type != 'status')col-lg-12 @endif {{ $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}" @if(isset($settings['orders']['perms'][$field->field]) && $settings['orders']['perms'][$field->field]['write'] == 'disabled') data-blocked="true" @endif >
                        <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                            <span class="btn js-edit-position me-2 btn-drag btn-drag-field btn-xs p-0 text-muted">
                                <svg class="icon icon-linedot"><use xlink:href="#icon-linedot"></use></svg>
                            </span>
                            <div class="label">
                                {{ $field->display_name }}
                            </div>
                            <div class="settings position-absolute" style="right:0">
                                <a class="dropdown-toggle btn p-0 text-secondary" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="false">
                                    <svg class="icon icon-settings "><use xlink:href="#icon-settings"></use></svg>
                                </a>

                                <ul class="dropdown-menu" >
                                    <li><a class="dropdown-item js-field-update-btn" data-field="{{ $field->id }}" href="#updateField" data-fancybox data-touch="false">Настроить</a></li>
                                    <li>
                                        <div class="dropdown-item">
                                            <div class="form-check form-check-xs mb-0">
                                              <input class="form-check-input js-field-show" type="checkbox" value="{{ $field->visible_always == 1 ? 0 : 1 }}" id="flexCheckDefault{{ $field->field }}" data-model="{{ $model }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}" {{ $field->visible_always ? 'checked' : ''}}>
                                              <label class="form-check-label " for="flexCheckDefault{{ $field->field }}">
                                                Показывать всегда
                                              </label>
                                            </div>
                                        </div>
                                    </li>
                                    <li><a class="dropdown-item js-field-hide" data-field="{{ $field->field }}" href="javascript:;">Скрыть</a></li>
                                    <li><a class="dropdown-item js-field-destroy" data-field="{{ $field->id }}" href="javascript:;">Удалить</a></li>
                                </ul>
                            </div>
                        </div>
                        @if($field->type == 'text_group')
                            @if(!isset($settings['orders']['perms'][$field->field]['write']) || !$settings['orders']['perms'][$field->field])
                            @include('fields.show.multipletext', ['field_data' => $field, 'field' => implode(',', $subfield_names), 'value' => implode(',', $subfield_values), 'model' => $model ])
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
                        @else
                            @if((!isset($settings['orders']['perms'][$field->field]['write']) || !$settings['orders']['perms'][$field->field]['write']) && $field->type != 'status' && $field->field != 'number')
                            <div class="js-editable active" data-field="{{ $field->field }}" data-value="{{ $current->{$field->field} }}" data-type="{{ $field->type }}" style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}">
                                @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $current->{$field->field} ])
                            </div>
                            @else
                            @include('fields.values.'.$field->type, ['field' => $field, 'current' => $current])
                            @endif

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
            <div class="pt-1">
                <div class="settings position-relative d-inline-block">
                    <a class="dropdown-toggle link show me-2" href="javascript:;" role="button" data-toggle="dropdown" aria-expanded="true">
                        Добавить поле
                    </a>
                    <ul class="dropdown-menu start-0">
                        @if($hidden_fields)
                            @foreach($hidden_fields as $field)
                            <li><a class="dropdown-item js-field-show" href="javascript:;" data-model="{{ $model }}" data-field="{{ $field->field }}" data-section="{{ $section->id }}">{{ $field->display_name }}</a></li>
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
