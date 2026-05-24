<div class="bg-gray px-5 py-3">
	<div class="position-relative">
        <label for="#" class="label text-dark mb-1">
            Элементы списка
        </label>
        <ul class="list-unstyled mb-0 js-sort-values sort-values">
            <li class="d-flex position-relative mb-3">
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
                @if($is_address)
                    <div class="js-compare-status position-relative"></div>
                @endif
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>    
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <label class="list-label-color" style="background: #c0c0c0">
                    <input class="js-list-color" type="color" name="colors[]"  value="#c0c0c0">
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
                @if($is_address)
                    <div class="js-compare-status position-relative"></div>
                @endif
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>    
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <label class="list-label-color" style="background: #c0c0c0">
                    <input class="js-list-color" type="color" name="colors[]" value="#c0c0c0">
                </label>
                <div class="position-relative ">
                    <input class="js-list-file-val" type="hidden" name="file_values[]">
                    <label class="list-label-file" style="background-image:url(/img/file-upload.svg?v=2);background-size:10px">
                        <input class="js-list-file" type="file" name="files[]">
                    </label>
                    <span class="list-label-file-delete js-list-file-delete d-none"><i class="fa fa-close"></i></span>
                </div>
                <div class="mr-3" style="flex: 1">
                	<input type="text" name="values[]" value="" class="form-control">
                </div>
                @if($is_address)
                    <div class="js-compare-status position-relative"></div>
                @endif
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>                                             
        </ul>
        <a class="js-add-field-value link" href="#" style="margin-left: 30px;">Добавить</a>
    </div>
</div>
<!-- <div class="position-relative px-5 my-3">
    <div class="form-check">
        <input class="form-check-input" name="is_plural" type="checkbox" value="1" id="is-plural">
        <label class="form-check-label" for="is-plural">
           Множественное
        </label>
    </div>
</div> -->
<div class="position-relative px-5 my-3">
    <div class="form-check">
        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always">
        <label class="form-check-label" for="visible_always">
           Показывать всегда
        </label>
    </div>
</div>
@if($is_address)
<div class="position-relative mt-3 mb-3 px-5">
    <div class="form-check">
        <input id="rules_field_task" type="checkbox" class="js-toggle-next form-check-input" checked>
        <label class="form-check-label" for="rules_field_task">
           Сопоставление с полями задачи
        </label>
    </div>
    <ul class="list-unstyled">
        <li class="col-lg-12">
            <div class="position-relative mb-3 js-hidden-block">
                <label for="#" class="label text-dark mb-1">
                    Поле для создания задачи
                </label>
                <select name="rules[orders][field]" class="js-select js-select-compare-field form-control" data-model="orders">
                    @php
                    $order_fields = \App\Models\Order::getFields();
                    
                    @endphp
                    @foreach($order_fields as $order_field)
                    <option value="{{ $order_field->field }}">{{ $order_field->display_name }}</option>
                    @endforeach
                </select>

            </div>
        </li>
    </ul>
</div>

@endif
<div class="position-relative mt-3 mb-3 px-5">
    <div class="form-check">
        <input class="js-toggle-next form-check-input" name="show_in_mobile" type="checkbox" value="1" id="show_in_mobile" >
        <label class="form-check-label" for="show_in_mobile">
           Показывать в мобильном приложении
        </label>
    </div>
    <ul class="list-unstyled">
        <li class="col-lg-12">
            <div class="position-relative mb-3 js-hidden-block" style="display: none;">
                <label for="#" class="label text-dark mb-1">
                    Страницы
                </label>
                <select name="mobile_pages[]" multiple class="js-select form-control">
                    <option value="drivers.order" >Заказ</option>
                    <option value="drivers.route" >Список доставок</option>
                </select>
            </div>
        </li>
    </ul>
</div>