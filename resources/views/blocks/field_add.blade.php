<div id="addField" class="fancy-modal">  
    <form method="post" class="form js-field-create" enctype="multipart/form-data">
        {{ csrf_field() }}

        @if(isset($carrier))
        <input type="hidden" name="carrier_id" value="{{ $carrier->id }}">
        @endif
        <input type="hidden" name="model" value="{{ $model }}">
        <input type="hidden" name="submodel" value="{{ $submodel ?? '' }}">
        <h5 class="section-title text-center mb-4">
            Добавить поле
        </h5>
        
        <div class="mb-2">
            <div class="position-relative mb-3 px-5">
                <label for="#" class="label text-dark mb-1">
                    Тип поля
                </label>
                <ul class="list-unstyled">
                    <li class="col-lg-12">
                        <div class="position-relative">
                            <select name="type" class="js-select js-type-field form-control">
                                <option value="text">Строка</option>
                                <option value="text_group">Строка несколько значений</option>
                                <option value="number">Число</option>
                                <option value="select_dropdown">Список</option>
                                <option value="file">Файл</option>
                                <option value="status">Статус</option>
                                <option value="program">Программируемое</option>
                            </select>
                        </div>
                    </li>
                </ul>
            </div>
            @if(isset($sections))
                <div class="position-relative mb-3 px-5">
                    <label for="#" class="label text-dark mb-1">
                        Раздел
                    </label>
                    <ul class="list-unstyled">
                        <li class="col-lg-12">
                            <div class="position-relative">
                                <select name="section_id" class="js-select js-type-field form-control">
                                    @foreach($sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>
            @else
                <input type="hidden" name="section_id" value="">
            @endif
            <div class="position-relative mb-3 px-5">
                <label for="#" class="label text-dark mb-1">
                    Название поля
                </label>
                <input type="text" name="name" value="" class="form-control">
            </div>
            <div class="js-field-props">
                <div class="position-relative mb-3 px-5">
                    <div class="form-check">
                        <input class="form-check-input" name="is_plural" type="checkbox" value="1" id="is-plural">
                        <label class="form-check-label" for="is-plural">
                           Множественное
                        </label>
                    </div>
                </div>
                <div class="position-relative mb-3 px-5">
                    <div class="form-check">
                        <input class="form-check-input" name="visible_always" type="checkbox" value="1" id="visible_always" checked>
                        <label class="form-check-label" for="visible_always">
                           Показывать всегда
                        </label>
                    </div>
                </div>
                @if($model == 'addresses')
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
                <div class="position-relative mb-3 px-5">
                    <div class="form-check">
                        <input class="form-check-input" name="set_color" type="checkbox" value="1" id="set_color">
                        <div class="form-group status-group color-input-wrapper" >
                            <input id="new-field-color" class="hide-color" type="color" name="label_color" value="#c0c0c0">
                            <!-- <div class="point_status_rect point_status_rect-4"></div>
                            <select class="form-control form-control-status form-control-status-select field-color" name="label_color">
                              <option class="status-default" value="#c0c0c0" data-value="1">1</option>
                              <option class="status-success" value="#0b9a1e" data-value="2">2</option>
                              <option class="status-fail" value="#ae0a0a" data-value="3">3</option>
                              <option class="status-change" value="#fd8301" data-value="4">4</option>
                            </select> -->
                        </div>
                        <label class="form-check-label form-check-label-color" for="new-field-color">
                           Другой цвет текста
                        </label>
                    </div>
                </div>
            </div>
            @if(isset($roles))
            <div class="position-relative mb-3 px-5">
                <div class="form-check">
                    <input class="js-toggle-next form-check-input" name="has_roles_read" type="checkbox" value="1" id="has_roles_read">
                    <label class="form-check-label" for="has_roles_read">
                       Ограничить видимость поля
                    </label>
                </div>
                <div class="position-relative mb-3 js-hidden-block" style="display: none;">
                    <label for="#" class="label text-dark mb-1">
                        Роли
                    </label>
                    <ul class="list-unstyled">
                        <li class="col-lg-12">
                            <div class="position-relative">
                                <select name="roles_read[]" multiple class="js-select form-control">
                                    @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="position-relative mb-3 px-5">
                <div class="form-check">
                    <input class="js-toggle-next form-check-input" name="has_roles_write" type="checkbox" value="1" id="has_roles_write">
                    <label class="form-check-label" for="has_roles_write">
                       Ограничить редактирования поля
                    </label>
                </div>
                <div class="position-relative mb-3 js-hidden-block" style="display: none;">
                    <label for="#" class="label text-dark mb-1">
                        Роли
                    </label>
                    <ul class="list-unstyled">
                        <li class="col-lg-12">
                            <div class="position-relative">
                                <select name="roles_write[]" multiple class="js-select form-control">
                                    @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary rounded-1 js-add-field" data-model="{{ $model }}" data-submodel="{{ $submodel ?? '' }}">Сохранить</button>
            <button class="btn btn-secondary rounded-1 js-close-modal">Отменить</button>
        </div>
    </form>
</div>
<style type="text/css">
    .select2-container--open {
        z-index: 99999;
    }
    .status-group {
        height: auto;
    }
    .fancy-modal {
        padding-left: 0;
        padding-right: 0;
    }
</style>