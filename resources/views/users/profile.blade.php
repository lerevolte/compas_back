@extends('layouts.main')
@section('title')
Личные настройки
@endsection
@section('h1')
    <h1 class="my-0 h1">Личные настройки</h1>
@endsection
@section('subnav')
@endsection
@section('content')
    {{ csrf_field() }}
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>

    <div class="rounded-1 bg-white border t">
        <div class="t-body " data-model="users">
            <div class="row g-0">
                <div class="col-lg-3 border-end">
                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Категории</h6>
                    </div>
                    <div class="c-body p-0">
                        <ul class="storages-list c-drag-list list-unstyled mb-0 ui-sortable">
                            <li class="side-list__item active" data-id="{{ $current->id }}">
                                <div class="position-relative active">
                                    <a href="javascript:;">Личный профиль</a>
                                </div>
                            </li>
                            <li>
                                <div class="position-relative">
                                    <a href="{{ route('users.edit_password') }}">Пароль</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    <form class="js-form form-edit-add" 
                              action="{{ route('users.update_profile', $current) }}"
                              method="POST" enctype="multipart/form-data" autocomplete="off">
                              {{ csrf_field() }}
                              <input type="hidden" name="_method" value="PUT">
                    <ul class="list-unstyled js-sort-t">
                    @if($current)
                        @if($sections)
                            @foreach($sections as $section)
                            <li>
                            <div class="c-top pe-2 bg-light border-bottom d-flex justify-content-end align-items-center toolbar-section" data-id="{{ $section->id }}">
                                <div class="position-relative me-auto d-flex align-items-center">
                                    <h6 id="section-title-{{ $section->id }}" class="h6 my-0 me-auto">{{ $section->name }}</h6>
                                </div>
                                <a href="javascript:;" class="link js-edit-section" data-model="users">Изменить</a>   
                                <div class="settings position-relative">
                                </div>
                            </div>
                            <div class="c-body p-4">
                                <div class="row mb-2 justify-content-between">
                                    <div class="col-lg-6">
                                        <ul class="position-relative row list-unstyled c-list js-sort-form" data-section="{{ $section->id }}">

                                        @if($section->visible_fields && count($section->visible_fields))
                                            @foreach($section->visible_fields as $k => $field)
                                                @php
                                                    $visible_field = false;
                                                    $subfield_names = array();
                                                    $subfield_values = array();
                                                    if($field->type == 'text_group') {
                                                        $subfields = \App\Models\Field::getByGroup($field->id);
                                                        
                                                        
                                                        foreach($subfields as $subfield) {
                                                            $subfield_names[] = $subfield->field;
                                                            $subfield_values[] = $current->{$subfield->field};
                                                            if($current->{$subfield->field}) {
                                                                $visible_field = true;
                                                            }
                                                        }
                                                    }
                                                    
                                                @endphp
                                                <li class="@if($field->type != 'status')col-lg-12 @endif {{ !$field->visible_always && !$current->{$field->field} && $field->type != 'text_group' || $field->type == 'text_group' && !$visible_field ? 'hidden-field' : '' }}" data-id="{{ $field->id }}">

                                                    <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                        <div class="label">
                                                            {{ $field->display_name }}
                                                        </div>
                                                    </div>
                                                    @if($field->type == 'text_group')
                                                        <div class="js-editable" data-field="{{ implode(',', $subfield_names) }}" data-value="{{ implode(',', $subfield_values) }}" data-type="multiple_input">
                                                            @if(request()->edit)
                                                                @include('fields.show.multipletext', ['field_data' => $field, 'field' => implode(',', $subfield_names), 'value' => implode(',', $subfield_values), 'model' => 'users' ])
                                                            @else
                                                                <div class="row g-2 flex-nowrap">
                                                                    @foreach($subfields as $subfield)
                                                                    <div class="col-4">
                                                                        <div class="label text-secondary">
                                                                            {{ $subfield->display_name }}
                                                                        </div>
                                                                        <div class="position-relative">
                                                                            {!! $current->{$subfield->field} ?? '<span class="empty-val">не заполнено</span>' !!}
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        @if(request()->edit && $field->type != 'status')
                                                            <div class="js-editable active"  data-field="{{ $field->field }}" data-type="{{ $field->type }}" data-value="{{ $current->{$field->field} }}">
                                                            @include('fields.show.'.$field->type, ['field_data' => $field, 'value' => $current->{$field->field} ])
                                                            </div>
                                                        @else
                                                            @include('fields.values.'.$field->type, ['field' => $field, 'current' => $current])
                                                        @endif
                                                    @endif
                                                </li>
                                            @endforeach
                                        @else
                                        <li></li>
                                        @endif
                                        </ul>
                                        <div class="">
                                            <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
                                                <div class="label">
                                                    API-ключ
                                                </div>
                                            </div>
                                            <input style="width:100%;" class="token" disabled type="text" name="" value="{{ $current->api_token ?? '-' }}"><br>
                                            <a href="javascript:;" class="js-generate-token">сгенерировать ключ</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </li>
                            @endforeach
                        @endif
                    @endif
                    </ul>
                    </form>

                </div>
            </div>

        </div>

    </div>
    <div class="js-save-panel-roles save-panel" style="display: none;left:0">
        <button type="submit" class="js-submit-roles blue-btn">Сохранить</button>
        <button class="gray-btn js-reset-fields-roles">Отменить</button>
    </div>
    <script>
        $(document).ready(function () {
            

            $('body').on('click', '.js-perm-box', function(){
                $('.js-perm-box').each(function(){
                    $(this).show();
                    $(this).next('span').hide();
                })
                $(this).hide();
                $(this).next('span').show();
            })
            $('body').on('click', '.js-toggle-perm-childs', function() {
                $(this).find('i').toggleClass('fa-plus fa-minus');
                $('tr[data-parent="'+$(this).data('name')+'"]').toggle();
                
            })
            $(window).on('click', function(e){
                if(!e.target.closest('select') && !e.target.closest('.js-perm-box')) {
                    $('.js-perm-box').each(function(){
                        $(this).show();
                        $(this).next('span').hide();
                    })
                }
            });
            $('body').on('change', '.js-perm-select', function(){
                var tr = $(this).closest('tr'),
                    index = $(this).closest('td').index(),
                    val = $(this).val(),
                    val_text = $(this).find('option:selected').text();
                $(this).closest('span').prev('.js-perm-box').text(val_text);
                if(tr.hasClass('parent')) {
                    console.log('parent')
                    $('tr[data-parent="'+tr.find('.js-toggle-perm-childs').data('name')+'"]').each(function(){
                        $(this).find('td:eq('+index+')').find('select option[value="'+val+'"]').prop('selected', true).trigger('change');
                    });
                }
                $('.js-save-panel-roles').show();
            });
            $('body').on('keyup', 'input', function(){
                $('.js-save-panel-roles').show();
            });
            
            $('body').on('click', '.js-submit-roles', function(){
                $('.js-form').submit();
            });

            $('body').on('click', '.js-reset-fields-roles', function(){
                $('.js-save-panel-roles').hide();
                updateContent();
            });
            
        });
    </script>
    <style type="text/css">
        span.js-perm-box {
            border-bottom: 1px dashed #999;
            color: #333;
            cursor: pointer;
            display: inline-block;
            padding: 0 1px;
        }
        .child-perm {
            display: none;
        }
        .js-save-panel {
            display: none!important;
        }
    </style>

@endsection
    