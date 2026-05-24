<input type="hidden" name="id" value="{{ $field->id }}">
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Тип поля
    </label>
    <div>
        Число
    </div>
</div>
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Название поля
    </label>
    <input type="text" name="name" value="{{ $field->display_name }}" class="form-control">
</div>
<div class="position-relative mb-3 px-5 d-none">
    <label for="#" class="label text-dark mb-1">
        Единицы измерения
    </label>
    <input type="text" name="measure" value="{{ $field->measure }}" class="form-control">
</div>
<div class="position-relative mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always" {{ $field->visible_always ? 'checked' : '' }}>
        <label class="form-check-label" for="visible_always">
           Показывать всегда
        </label>
    </div>
</div>
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
<div class="position-relative mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="set_color" type="checkbox" value="1" id="set_color" {{ $field->label_color ? 'checked' : '' }}>
        <div class="form-group status-group color-input-wrapper">
            <input id="{{ $field->field }}-color" class="hide-color" type="color" name="label_color" value="{{ $field->label_color }}">
            <!-- <div class="point_status_rect point_status_rect-4"></div>
            <select class="form-control form-control-status form-control-status-select field-color" name="label_color">
              <option class="status-default" value="#c0c0c0" data-value="1" {{ $field->label_color == '#c0c0c0' ? 'selected' : '' }}>1</option>
              <option class="status-success" value="#0b9a1e" data-value="2" {{ $field->label_color == '#0b9a1e' ? 'selected' : '' }}>2</option>
              <option class="status-fail" value="#ae0a0a" data-value="3" {{ $field->label_color == '#ae0a0a' ? 'selected' : '' }}>3</option>
              <option class="status-change" value="#fd8301" data-value="4" {{ $field->label_color == '#fd8301' || !$field->label_color ? 'selected' : '' }}>4</option>
            </select> -->
        </div>
        <label class="form-check-label form-check-label-color" for="{{ $field->field }}-color" style="color: {{ $field->label_color ?? '#000' }};border-color: {{ $field->label_color ?? '#000' }}">
           Другой цвет текста
        </label>
    </div>
</div>
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