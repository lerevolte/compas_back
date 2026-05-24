<div class="position-relative mb-3 px-5 d-none">
    <label for="#" class="label text-dark mb-1">
        Единицы измерения
    </label>
    <input type="text" name="measure" value="" class="form-control">
</div>
<div class="position-relative mb-3 px-5">
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
                <select name="rules[orders][field]" class="js-select form-control">
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
<div class="position-relative mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="set_color" type="checkbox" value="1" id="set_color">
        <div class="form-group status-group color-input-wrapper" >
            <input id="new-field-color" class="hide-color"type="color" name="label_color" value="">
            <!-- <div class="point_status_rect point_status_rect-4"></div>
            <select class="form-control form-control-status form-control-status-select field-color" name="label_color">
              <option class="status-default" value="#c0c0c0" data-value="1">1</option>
              <option class="status-success" value="#0b9a1e" data-value="2">2</option>
              <option class="status-fail" value="#ae0a0a" data-value="3">3</option>
              <option class="status-change" value="#fd8301" data-value="4" selected>4</option>
            </select> -->
        </div>
        <label class="form-check-label form-check-label-color" for="new-field-color">
           Другой цвет текста
        </label>
    </div>
</div>