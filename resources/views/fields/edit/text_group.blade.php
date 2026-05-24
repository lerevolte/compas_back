<input type="hidden" name="id" value="{{ $field->id }}">
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Тип поля
    </label>
    <div>
        Строка нескольких значений
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
            @foreach($options as $name => $option)
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <div class="mr-3" style="flex: 1">
                	<input type="text" name="values[{{ $name }}]" value="{{ $option }}" class="form-control">
                </div>
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>    
            @endforeach                                        
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