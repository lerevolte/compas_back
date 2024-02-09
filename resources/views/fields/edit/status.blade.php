@php
    if($field->rules) {
        $rules = json_decode($field->rules, true);
        $field_source = \DB::table('data_rows')->where('field', $rules['orders']['field'])->where('data_type_id', 8)->first();
        $fields_values = \App\Models\Field::getStatuses($field_source->id);

        $option_num = 0;
    }
    $tenant = tenant('id');          
@endphp
<input type="hidden" name="id" value="{{ $field->id }}">
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Тип поля
    </label>
    <div>
        Статус
    </div>
</div>
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Название поля
    </label>
    <input type="text" name="name" value="{{ $field->display_name }}" class="form-control">
</div>

<div class="bg-gray px-5 py-3">
	<div class="position-relative">
        <label for="#" class="label text-dark mb-1">
            Элементы списка
        </label>
        <ul class="list-unstyled mb-0 js-sort-values sort-values">

            @forelse($options as $key => $option)
                @if(!$option->is_hidden)
                <li class="mb-3">
                    <div class="d-flex position-relative">
                        <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                        <input type="hidden" name="value_ids[]" value="{{ $option->id }}" class="form-control">
                        <label class="list-label-color" style="background: {{ $option->color }}">
                            <input class="js-list-color coloris-edit" type="text" name="colors[]" value="{{ $option->color ? $option->color : '#000' }}">
                            <!-- <input class="js-list-color" type="color" name="colors[]" value="{{ $option->color }}"> -->
                        </label>
                        <div class="position-relative">
                            <input class="js-list-file-val" type="hidden" name="file_values[]">
                            <label class="list-label-file" @if($option->file) style="background-image:url({{ '/storage/tenant'.$tenant.'/app/'.$option->file }})" @else style="background-image:url(/img/file-upload.svg?v=2);background-size:10px" @endif>
                                <input class="js-list-file" type="file" name="files[]">
                            </label>
                            <span class="list-label-file-delete js-list-file-delete @if(!$option->file) d-none @endif"><i class="fa fa-close"></i></span>
                        </div>
                        <div class="mr-3" style="flex: 1">
                            <input type="text" name="values[]" data-key="{{ $option->id }}" value="{{ $option->name }}" class="form-control">
                        </div>
                        @if($field->data_type_id == 39)
                        <div class="js-compare-status position-relative">
                            @if($field->rules)
                            <div class="form-group status-group">
                                @php
                                    $exist = false;
                                    $first = null;
                                    $i = 0;
                                    $color = '#000';
                                @endphp
                                @foreach($fields_values as $list_item)
                                    @php
                                    if(!$list_item->is_hidden)
                                        $i++;
                                    @endphp
                                    @php
                                    if($i == 1)
                                        $first = $list_item;
                                    @endphp
                                    @if($rules['field_value'][$option_num] == $list_item->id)
                                        @if($list_item->file)
                                            <div class="point_status_rect" style="background: url({{ Storage::disk()->url($list_item->file) }}) {{ $list_item->color }}"></div>
                                        @else
                                            <div class="point_status_rect" style="background: {{ $list_item->color }}"></div>
                                        @endif
                                        @php
                                        if($list_item->is_hidden)
                                            $color = $list_item->color;
                                        $exist = true;
                                        @endphp
                                    @elseif(!$rules['field_value'][$option_num] && !$list_item->is_hidden)
                                        @if($list_item->file)
                                            <div class="point_status_rect" style="background: url({{ Storage::disk()->url($list_item->file) }}) {{ $list_item->color }}"></div>
                                        @else
                                            <div class="point_status_rect" style="background: {{ $list_item->color }}"></div>
                                        @endif
                                        @php
                                            $exist = true;
                                            break;
                                        @endphp
                                    @endif

                                @endforeach
                                @if(!$exist)
                                    @if($first->file)
                                        <div class="point_status_rect" style="background: url({{ Storage::disk()->url($first->file) }}) {{ $list_item->color }}"></div>
                                    @else
                                        <div class="point_status_rect" style="background: {{ $first->color }}"></div>
                                    @endif
                                @endif
                                <select name="rules[field_value][{{ $option_num }}]" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status">
                                    @foreach($fields_values as $list_item)
                                        @if(!$list_item->is_hidden)
                                            <option data-file="{{ Storage::disk()->url($list_item->file) }}" data-color="{{ $list_item->color }}" value="{{ $list_item->id }}" @if($rules['field_value'][$option_num] == $list_item->id) selected="selected" @endif>{{ $list_item->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            @php
                            $option_num++;
                            @endphp
                            @endif
                        </div>
                        @endif
                        <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
                    </div>
                </li>
                @endif
                
            @empty
                <li class="d-flex mb-3">
                    <div class="d-flex position-relative">
                        <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                        <label class="list-label-color" style="background: #c0c0c0">
                            <input class="js-list-color" type="color" name="colors[]" value="#c0c0c0">
                        </label>
                        <div class="position-relative">
                            <input class="js-list-file-val" type="hidden" name="file_values[]">
                            <label class="list-label-file" style="background-image:url(/img/file-upload.svg?v=2);background-size:10px">
                                <input class="js-list-file" type="file" name="files[]">
                            </label>
                            <span class="list-label-file-delete js-list-file-delete d-none"><i class="fa fa-close"></i></span>
                        </div>
                        <div class="mr-3" style="flex: 1">
                            <input type="text" name="values[]" value="" class="form-control">
                        </div>
                        <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
                    </div>
                </li>    
            @endforelse                                      
        </ul>
        <a class="js-add-field-value link" href="#" style="margin-left: 30px;">Добавить</a>
    </div>
</div>

<div class="position-relative mt-3 px-5 mb-3">
    <div class="form-check">
        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always" {{ $field->visible_always ? 'checked' : '' }}>
        <label class="form-check-label" for="visible_always">
           Показывать всегда
        </label>
    </div>
</div>
@if($field->data_type_id == 39)
<div class="position-relative mt-3 mb-3 px-5">
    <div class="form-check">
        <input id="rules_field_task" type="checkbox" class="js-toggle-next form-check-input" @if($field->rules) checked @endif>
        <label class="form-check-label" for="rules_field_task">
           Сопоставление с полями задачи
        </label>
    </div>
    <ul class="list-unstyled">
        <li class="col-lg-12">
            <div class="position-relative mb-3 js-hidden-block" @if(!$field->rules) style="display: none;" @endif>
                <label for="#" class="label text-dark mb-1">
                    Поле для создания задачи
                </label>
                <select name="rules[orders][field]" class="js-select js-select-compare-field form-control" data-model="orders">
                    @php
                    $order_fields = \App\Models\Order::getFields();
                    
                    @endphp
                    @foreach($order_fields as $order_field)
                    <option value="{{ $order_field->field }}" @if(isset($rules['orders']['field']) && $rules['orders']['field'] == $order_field->field) selected @endif>{{ $order_field->display_name }}</option>
                    @endforeach
                </select>

            </div>
        </li>
    </ul>
</div>

@endif
@if($field->data_type_id == 8 || $field->data_type_id == 35)
<div class="position-relative mt-3 mb-3 px-5">
    <div class="form-check">
        <input class="js-toggle-next form-check-input" name="show_in_mobile" type="checkbox" value="1" id="show_in_mobile" @if($field->mobile_pages) checked @endif>
        <label class="form-check-label" for="show_in_mobile">
           Показывать в мобильном приложении
        </label>
    </div>
    <ul class="list-unstyled">
        <li class="col-lg-12">
            <div class="position-relative mb-3 js-hidden-block"  @if(!$field->mobile_pages) style="display: none;" @endif>
                <label for="#" class="label text-dark mb-1">
                    Страницы
                </label>
                <select name="mobile_pages[]" multiple class="js-select form-control">
                    @if($field->data_type_id == 8)
                        <option value="drivers.order" @if($field->mobile_pages && in_array('drivers.order', json_decode($field->mobile_pages))) selected @endif>Заказ</option>
                        <option value="drivers.route" @if($field->mobile_pages && in_array('drivers.route', json_decode($field->mobile_pages))) selected @endif>Список доставок</option>
                    @elseif($field->data_type_id == 35)
                        <option value="fines.show" @if($field->mobile_pages && in_array('fines.show', json_decode($field->mobile_pages))) selected @endif>Детальная страница</option>
                        <option value="fines.list" @if($field->mobile_pages && in_array('fines.list', json_decode($field->mobile_pages))) selected @endif>Список</option>
                    @endif
                </select>
            </div>
        </li>
    </ul>
</div>
@endif
@if(isset($roles))
    <div class="position-relative mb-3 px-5">
        <div class="form-check">
            <input class="js-toggle-next form-check-input" name="has_roles_read" type="checkbox" value="1" id="has_roles_read" @if($field->roles_read) checked @endif>
            <label class="form-check-label" for="has_roles_read">
               Ограничить видимость поля
            </label>
        </div>
        <ul class="list-unstyled">
            <li class="col-lg-12">
                <div class="position-relative mb-3 js-hidden-block"  @if(!$field->roles_read) style="display: none;" @endif>
                    <label for="#" class="label text-dark mb-1">
                        Роли
                    </label>
                    <select name="roles_read[]" multiple class="js-select form-control">
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" @if($field->roles_read && in_array($role->id, json_decode($field->roles_read))) selected @endif >{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>
            </li>
        </ul>
    </div>
     
    <div class="position-relative mb-3 px-5">
        <div class="form-check">
            <input class="js-toggle-next form-check-input" name="has_roles_write" type="checkbox" value="1" id="has_roles_write" @if($field->roles_write) checked @endif>
            <label class="form-check-label" for="has_roles_write">
               Ограничить редактирования поля
            </label>
        </div>
        <ul class="list-unstyled" >
            <li class="col-lg-12">
                <div class="position-relative mb-3 js-hidden-block" @if(!$field->roles_write) style="display: none;" @endif>
                    <label for="#" class="label text-dark mb-1">
                        Роли
                    </label>
                    <select name="roles_write[]" multiple class="js-select form-control">
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" @if($field->roles_write && in_array($role->id, json_decode($field->roles_write))) selected @endif >{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>
            </li>
        </ul>
    </div>
@endif