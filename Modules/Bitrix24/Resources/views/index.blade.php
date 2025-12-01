@extends('layouts.main')
@section('content')
    <form method="post" action="/bitrix24/update">
        {{ csrf_field() }}
        <div class="mb-3">
            <label>Адрес вебхука</label>
            <input name="webhook" class="form-control" type="text" value="{{ $config->webhook }}" placeholder="Введите webhook">
        </div>
        <br>
        <div class="row">
            @if($b24_fields)
            <div class="col-6 col-md-4">
                <ul class="position-relative row list-unstyled c-list mb-0">
                    <li class="col-lg-12">
                        <div class="position-relative">
                            <select class="js-select js-b24-select">
                                @foreach($b24_fields as $code => $field)
                                    @if(!isset($params['params'][$code]))
                                    <option data-items="{{ isset($field['items']) ? json_encode($field['items'], JSON_UNESCAPED_UNICODE) : '' }}" value="{{ $code }}">{{ $field['listLabel'] ?? $field['title'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <button class="btn btn-primary js-b24-add-field">Добавить поле</button>
            </div>
            @endif
        </div>
        <div class="b24-fields">
            <div class="js-field-row field-row row mb-5 d-none">
                <div class="col-md-4">
                    <div class="white-box">
                        <div class="panel panel-bold panel-b24 d-flex align-items-center">
                            Поле из Битрикс24
                        </div>
                        <div class="c-body pt-3">
                            <div class="px-3">
                                <ul class="position-relative row list-unstyled c-list mb-0">
                                    <li class="col-lg-12">
                                        <div class="position-relative">
                                            <div class="js-b24-field-values pt-3">
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="white-box">
                        <div class="panel panel-bold d-flex align-items-center">
                            Тип связи
                        </div>
                        <div class="c-body pt-3">
                            <div class="px-3">
                                <ul class="position-relative row list-unstyled c-list mb-0">
                                    <li >
                                        <div class="position-relative">
                                            <div class="form-group status-group">
                                                <div class="d-none">1</div>
                                                <div class="point_status_rect" data-id="1" style="background: url(/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png) #0f0"></div>

                                                <select name="relation_type" class="form-control form-control-status form-control-status-select ">
                                                    <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#0f0" value="1">Синхронизировать</option>
                                                    <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#f00" value="2">Не синхронизировать</option>
                                                </select>
                                                <span class="js-select-text">Синхронизировать</span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="white-box">
                        <div class="panel panel-bold d-flex align-items-center">
                            Назначьте поле в compas
                        </div>
                        <div class="c-body pt-3">
                            <div class="px-3">
                                <ul class="position-relative row list-unstyled c-list mb-0">
                                    <li class="col-lg-12">
                                        <div class="position-relative">

                                            <select class="js-b24-field" name=""><!-- B24 FIELD -->
                                                <option value="0">Выбрать</option>
                                                @foreach($order_fields as $code => $order_field)
                                                    @if($order_field->type == 'select_dropdown')
                                                        @php
                                                        $field_details = json_decode($order_field->details, true);
                                                        if(isset($field_details['table'])) {
                                                            $options_o = array();
                                                            $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
                                                            if(count($options_o))
                                                                foreach($options_o as $option) {
                                                                    $opts[$option->id] = (isset($option->title) ? $option->title : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
                                                                }
                                                        } else
                                                            $opts = $field_details['options'];
                                                        $opts = json_encode($opts, JSON_UNESCAPED_UNICODE);
                                                        @endphp
                                                        <option data-field="{{ $order_field->field }}" data-items="{{ $opts }}" value="{{ $order_field->field }}">{{ $order_field->title }}</option>
                                                    @else
                                                        <option data-field="{{ $order_field->field }}" value="{{ $order_field->field }}">{{ $order_field->title }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <!-- <div class="js-b24-field-values pt-3">
                                            </div> -->
                                        </div>
                                    </li>
                                    <li class="col-lg-12">
                                        <div class="d-none">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="empty-val me-2">1.</span>
                                                <select></select>
                                            </div>
                                        </div>
                                        <div class="js-options">
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if(isset($params['params']) && $b24_fields)
                @foreach($params['params'] as $b24_code => $code)
                @php
                $b24_title = $b24_fields[$b24_code]['listLabel'] ?? $b24_fields[$b24_code]['title'];

                @endphp
                <div class="js-field-row field-row row mb-5">
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold panel-b24 d-flex align-items-center">
                                {{ $b24_title }}
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                        <li class="col-lg-12">
                                            <div class="position-relative">
                                                <div class="js-b24-field-values pt-3">
                                                    @if(isset($b24_fields[$b24_code]['items']))
                                                        @php
                                                        $items = $b24_fields[$b24_code]['items'];
                                                        $i = 1;
                                                        @endphp
                                                        @if(is_array($items))
                                                            @foreach($items as $item)
                                                                <input type="hidden" name=""><span class="empty-val" data-val="{{ $item['ID'] }}" data-name="{{ $item['VALUE'] }}">{{ $i }}.</span> {{ $item['VALUE'] }}<br>
                                                                @php
                                                                $i++;
                                                                @endphp
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold d-flex align-items-center">
                                Тип связи
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                        <li >
                                            <div class="position-relative">
                                                <div class="form-group status-group">
                                                    <div class="d-none">1</div>
                                                    @if(!isset($params['relation'][$code]) || isset($params['relation'][$code]) && $params['relation'][$code] == 1)
                                                    <div class="point_status_rect" data-id="1" style="background: url(/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png) #0f0"></div>
                                                    @else
                                                    <div class="point_status_rect" data-id="1" style="background: url(/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png) #f00"></div>
                                                    @endif

                                                    <select name="config[relation][{{ $b24_code }}]" class="form-control form-control-status form-control-status-select js-field-status">
                                                        <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#0f0" value="1" @if(!isset($params['relation'][$code]) || isset($params['relation'][$code]) && $params['relation'][$code] == 1) selected @endif>Синхронизировать</option>
                                                        <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#f00" value="2" @if(isset($params['relation'][$code]) && $params['relation'][$code] == 2) selected @endif>Не синхронизировать</option>
                                                    </select>
                                                    @if(!isset($params['relation'][$code]) || isset($params['relation'][$code]) && $params['relation'][$code] == 1)
                                                    <span class="js-select-text">Синхронизировать</span>
                                                    @else
                                                    <span class="js-select-text">Не синхронизировать</span>
                                                    @endif
                                                    
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold d-flex align-items-center">
                                Назначьте поле в compas 
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                        <li class="col-lg-12">
                                            <div class="position-relative">

                                                <select class="js-b24-field js-select" name="config[params][{{ $b24_code }}]"><!-- B24 FIELD -->
                                                    <option value="0">Выбрать</option>
                                                    @foreach($order_fields as $field)
                                                        @php
                                                        if($params['params'][$b24_code] == $field->field)
                                                            $order_field = $field;
                                                        @endphp
                                                        @if($field->type == 'select_dropdown')
                                                            @php
                                                            $field_details = json_decode($field->details, true);
                                                            if(isset($field_details['table'])) {
                                                                $options_o = array();
                                                                $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
                                                                if(count($options_o))
                                                                    foreach($options_o as $option) {
                                                                        $opts1[$option->id] = (isset($option->title) ? $option->title : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
                                                                    }
                                                            } else
                                                                $opts1 = $field_details['options'];
                                                            $opts1 = json_encode($opts, JSON_UNESCAPED_UNICODE);
                                                            
                                                            @endphp
                                                            <option data-field="{{ $field->field }}" data-items="{{ $opts1 }}" value="{{ $field->field }}" @if($params['params'][$b24_code] == $field->field) selected @endif>{{ $field->title }}</option>
                                                        @else
                                                            <option data-field="{{ $field->field }}" value="{{ $field->field }}" @if($params['params'][$b24_code] == $field->field) selected @endif>{{ $field->title }}</option>
                                                        @endif
                                                        @php
                                                        unset($opts1);
                                                        @endphp
                                                    @endforeach
                                                </select>
                                                <!-- <div class="js-b24-field-values pt-3">
                                                </div> -->
                                            </div>
                                        </li>
                                        <li class="col-lg-12">
                                            <div class="d-none">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="empty-val me-2">1.</span>
                                                    <select></select>
                                                </div>
                                            </div>
                                            <div class="js-options">
                                                @if($order_field->type == 'select_dropdown')

                                                    @php
                                                    $field_details = json_decode($order_field->details, true);
                                                    if(isset($field_details['table'])) {
                                                        $options_o = array();
                                                        $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
                                                        if(count($options_o))
                                                            foreach($options_o as $option) {
                                                                $options[$option->id] = (isset($option->title) ? $option->title : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
                                                            }
                                                    } else
                                                        $options = $field_details['options'];
                                                    $i = 1;
                                                    @endphp
                                                    @if(isset($params['values'][$order_field->field]))
                                                        @foreach($params['values'][$order_field->field] as $b24_val => $val)
                                                            <div class="d-flex align-items-center mb-2">
                                                                <span class="empty-val me-2">{{ $i }}.</span>
                                                                <select class="js-select" name="config[values][{{ $order_field->field }}][{{ $b24_val }}]">
                                                                    @foreach($options as $k => $option)
                                                                        <option value="{{ $k }}" @if($val == $k) selected @endif>{{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            @php
                                                            $i++;
                                                            @endphp
                                                        @endforeach
                                                    @else
                                                        <div class="position-relative empty-val">
                                                            не заполнено
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        @if($b24_fields)
            @foreach($order_fields as $order_field)
                <div class="field-row row mb-5 d-none">
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold d-flex align-items-center">
                                Поле из Битрикс24
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                        <li class="col-lg-12">
                                            <div class="position-relative">

                                                <select class="js-select js-b24-field" name="">
                                                    <option value="0">Выбрать</option>
                                                    @foreach($b24_fields as $code => $field)
                                                        <option data-items="{{ isset($field['items']) ? json_encode($field['items'], JSON_UNESCAPED_UNICODE) : '' }}" value="{{ $code }}" @if(isset($params['params'][$order_field->field]) && $params['params'][$order_field->field] == $code) selected @endif>{{ $field['listLabel'] ?? $field['title'] }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="js-b24-field-values pt-3">
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold d-flex align-items-center">
                                Тип связи
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                        <li >
                                            <div class="position-relative">
                                                <div class="form-group status-group">
                                                    <div class="d-none">1</div>
                                                    <div class="point_status_rect" data-id="1" style="background: url(/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png) #0f0"></div>

                                                    <select name="relation_type" class="form-control form-control-status form-control-status-select js-field-status">
                                                        <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#0f0" value="1">Синхронизировать</option>
                                                        <option data-file="/storage/field_icons/1/x8hA82TSLMRyioYILJQkfmnin6eiv2y8E3640v9y.png" data-color="#f00" value="2">Не синхронизировать</option>
                                                    </select>
                                                    <span class="js-select-text">Синхронизировать</span>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="white-box">
                            <div class="panel panel-bold d-flex align-items-center">
                                {{ $order_field->title }}
                            </div>
                            <div class="c-body pt-3">
                                <div class="px-3">
                                    <ul class="position-relative row list-unstyled c-list mb-0">
                                    @if($order_field->type == 'select_dropdown')
                                        @php
                                        $field_details = json_decode($order_field->details, true);
                                        if(isset($field_details['table'])) {
                                            $options_o = array();
                                            $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
                                            if(count($options_o))
                                                foreach($options_o as $option) {
                                                    $options[$option->id] = (isset($option->title) ? $option->title : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
                                                }
                                        } else
                                            $options = $field_details['options'];
                                        @endphp
                                        <li class="col-lg-12">
                                            <div class="d-none">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="empty-val me-2">1.</span>
                                                    <select name="{{ $order_field->field }}">
                                                        @foreach($options as $k => $option)
                                                            <option value="{{ $k }}">{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="js-options">
                                            @php
                                            $i = 1;
                                            @endphp
                                            @if(isset($params['values'][$order_field->field]))
                                                @foreach($params['values'][$order_field->field] as $b24_val => $val)
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="empty-val me-2">{{ $i }}.</span>
                                                    <select class="js-select" name="config[values][{{ $order_field->field }}][{{ $b24_val }}]">
                                                        @foreach($options as $k => $option)
                                                            <option value="{{ $k }}" @if($val == $k) selected @endif>{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @php
                                                $i++;
                                                @endphp
                                                @endforeach
                                            @else
                                                <div class="position-relative empty-val">
                                                    не заполнено
                                                </div>
                                            @endif
                                            </div>
                                            @foreach($options as $k => $option)
                                            <!-- <div class="d-flex my-2 align-items-center">
                                                <span class="empty-val me-2">{{ $i }}.</span> <input type="text" class="form-control" value="{{ $option }}" disabled>
                                            </div> -->
                                            
                                            @endforeach
                                        </li>
                                    @else
                                        <li class="col-lg-12">
                                            <div class="position-relative empty-val">
                                                не заполнено
                                            </div>
                                        </li>
                                    @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        </div>
        <div class="mb-3">
            <button class="btn btn-primary" type="submit">Сохранить</button>
        </div>
    </form>
    <div class="mb-3">
        <a href="/bitrix24/sync">Синхронизировать (не забыть сохранить)</a>
    </div>
    <style type="text/css">
        .select2-results__option {
            text-align: left;
        }
        .js-options .select2 {
            flex: 1 1;
        }
    </style>
    <script type="text/javascript">
        $(document).ready(function(){
            select2init();
            $('body').on('click', '.js-b24-add-field', function(e){
                e.preventDefault();

                var option = $('.js-b24-select').find(':selected'),
                    cl = $('.js-field-row.d-none').clone(),
                    values = cl.find('.js-b24-field-values'),
                    options = $(this).closest('.field-row').find('.js-options');
            

                cl.removeClass('d-none');
                cl.find('.panel-b24').text(option.text());

                cl.find('.js-b24-field').addClass('js-select');
                cl.find('.js-b24-field').attr('name', 'config[params]['+option.val()+']')

                cl.find('[name="relation_type"]').addClass('js-field-status');
                cl.find('[name="relation_type"]').attr('name', 'config[relation]['+option.val()+']')
                
                values.html('');
                options.html('<div class="position-relative empty-val">не заполнено</div>');
                if(option.data('items').length) {
                    var items = option.data('items');
                    var html = '';
                    options.html('');
                    $.each(items, function(key, item) {
                        values.append('<input type="hidden" name=""><span class="empty-val" data-val="'+item['ID']+'" data-name="'+item['VALUE']+'">'+(key+1)+'.</span> '+item['VALUE']+'<br>');

                        // var clone = options.prev('.d-none').find('.d-flex').clone();
                        // clone.find('.empty-val').text(key+1 + '.');
                        // clone.find('select').addClass('js-select');
                        // clone.find('select').attr('name', 'config[values]['+options.prev('.d-none').find('select').attr('name')+']['+item['ID']+']');
                        // options.append(clone);
                        
                    });
                    
                }

                $('.b24-fields').append(cl);
                $('.js-select').each(function(){
                    var $this = $(this),
                        $wrap = $this.closest('.position-relative');

                    if($this.find('option').length < 10) {
                        $this.select2({
                            width: 'auto',
                            dropdownParent: $wrap,
                            minimumResultsForSearch: -1
                        });
                    } else {
                        $this.select2({
                            width: 'auto',
                            dropdownParent: $wrap
                        });
                    }
                });
                select2init();
                option.detach();
            });
            $('body').on('change', '.js-b24-field', function(){

                var $option = $(this).find(':selected'),
                    values = $(this).closest('.field-row').find('.js-b24-field-values'),
                    options = $(this).closest('.field-row').find('.js-options'),
                    clone;
                //values.html('');
                options.html('');
                // console.log($option)
                // console.log(values.find('.empty-val').length)
                
                if(values.find('.empty-val').length) {
                    var i = 1;
                    if($option.attr('data-items') !== undefined) {
                        values.find('.empty-val').each(function(){
                            clone = options.prev('.d-none').find('.d-flex').clone();
                            clone.find('.empty-val').text(i + '.');
                            clone.find('select').addClass('js-select');
                            clone.find('select').attr('name', 'config[values]['+$option.attr('data-field')+']['+$(this).data('val')+']');
                            console.log($option.attr('data-items'))
                            if($option.attr('data-items').length) {
                                var items = jQuery.parseJSON($option.attr('data-items'));
                                $.each(items, function(key, item) {
                                    clone.find('select').append('<option value="'+key+'">'+item+'</option>')
                                });
                            }
                            options.append(clone);
                            i++;
                            //console.log($(this).data('val'));
                        });
                        $('.js-select').each(function(){
                            var $this = $(this),
                                $wrap = $this.closest('.position-relative');

                            if($this.find('option').length < 10) {
                                $this.select2({
                                    width: 'auto',
                                    dropdownParent: $wrap,
                                    minimumResultsForSearch: -1
                                });
                            } else {
                                $this.select2({
                                    width: 'auto',
                                    dropdownParent: $wrap
                                });
                            }
                        });
                    } else {
                        options.html('<div class="position-relative empty-val">не заполнено</div>');
                    }
                }
                return;

                if($option.attr('data-items').length) {
                    var items = jQuery.parseJSON($option.attr('data-items'));
                    var html = '';
                    options.html('');
                    //var sel = clone.find('select');
                    var i = 1;
                    clone = options.prev('.d-none').find('.d-flex').clone();
                    clone.find('.empty-val').text(i + '.');
                    clone.find('select').addClass('js-select');
                    $.each(items, function(key, item) {
                        //values.append('<input type="hidden" name=""><span class="empty-val">'+(key+1)+'.</span> '+item['VALUE']+'<br>');
                        // clone = options.prev('.d-none').find('.d-flex').clone();
                        // clone.find('.empty-val').text(i + '.');
                        // clone.find('select').addClass('js-select');
                        //clone.find('select').attr('name', 'config[values]['+options.prev('.d-none').find('select').attr('name')+']['+item['ID']+']');
                        clone.find('select').attr('name', 'config[values]['+options.prev('.d-none').find('select').attr('name')+']['+item+']');
                        sel.append('<option value="'+key+'">'+item+'</option>');
                        sel.append(clone);
                        $('.js-select').each(function(){
                            var $this = $(this),
                                $wrap = $this.closest('.position-relative');

                            if($this.find('option').length < 10) {
                                $this.select2({
                                    width: 'auto',
                                    dropdownParent: $wrap,
                                    minimumResultsForSearch: -1
                                });
                            } else {
                                $this.select2({
                                    width: 'auto',
                                    dropdownParent: $wrap
                                });
                            }
                        });
                        i++;
                        //values.append('<div class="d-flex my-2 align-items-center"><span class="empty-val">'+(key+1)+'.</span> <input type="text" class="form-control" value="'+item['VALUE']+'"></div>');
                        //alert( key + ": " + value );
                    });
                    select2init();
                    
                } else {
                    values.html('<div class="position-relative empty-val">не заполнено</div>');
                }
                console.log('change')
            });
        });
    </script>
@endsection
