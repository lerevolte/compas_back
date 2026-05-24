<input type="hidden" name="id" value="{{ $field->id }}">
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Тип поля
    </label>
    <div>
        Блок
    </div>
</div>
<div class="position-relative mb-3 px-5">
    <label for="#" class="label text-dark mb-1">
        Название поля
    </label>
    <input type="text" name="name" value="{{ $field->display_name }}" class="form-control">
</div>
<div class="position-relative mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always" {{ $field->visible_always ? 'checked' : '' }}>
        <label class="form-check-label" for="visible_always">
           Показывать всегда
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