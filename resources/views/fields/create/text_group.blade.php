<div class="bg-gray px-5 py-3">
	<div class="position-relative">
        <label for="#" class="label text-dark mb-1">
            Элементы списка
        </label>
        <ul class="list-unstyled mb-0 js-sort-values sort-values">
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <div class="mr-3" style="flex: 1">
                	<input type="text" name="values[]" value="" class="form-control">
                </div>
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>    
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <div class="mr-3" style="flex: 1">
                	<input type="text" name="values[]" value="" class="form-control">
                </div>
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>    
            <li class="d-flex position-relative mb-3">
                <span class="btn-drag mr-3"><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                <div class="mr-3" style="flex: 1">
                	<input type="text" name="values[]" value="" class="form-control">
                </div>
                <span class="js-delete-dropdown-item"><i class="fa fa-close"></i></span>
            </li>                                             
        </ul>
        <a class="js-add-field-value link" href="#" style="margin-left: 30px;">Добавить</a>
    </div>
</div>
<div class="position-relative px-5 my-3">
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