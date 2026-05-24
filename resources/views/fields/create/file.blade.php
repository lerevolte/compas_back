<div class="bg-gray px-5 py-2">
    <div class="position-relative">
        <label for="#" class="label text-dark mb-1">
            Название кнопки
        </label>
        <ul class="list-unstyled mb-0 js-sort-values sort-values">
            <li class="d-flex position-relative mb-3">
                <input type="text" name="button_name" value="" class="form-control">
            </li>                                           
        </ul>
    </div>
</div>
<div class="position-relative mt-3 mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always">
        <label class="form-check-label" for="visible_always">
           Показывать всегда
        </label>
    </div>
</div>

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
<!-- <div class="position-relative mb-3 px-5">
    <div class="form-check">
        <input class="form-check-input" name="show_file_image" type="checkbox" value="1" id="show_file_image">
        <label class="form-check-label" for="show_file_image">
           Показывать содержимое файла
        </label>
    </div>
</div> -->