@extends('layouts.main')
@section('title')
Роли
@endsection
@section('h1')
    <h1 class="my-0 h1">Роли</h1>
@endsection
@section('subnav')
    <div class="t-nav mt-4">
        <div class="btn-group mb-3" role="group" aria-label="Nav">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Пользователи</a>
            <a href="{{ route('settings.roles') }}" class="btn btn-outline-secondary active">Роли</a>
            @if(request()->user()->hasRole('admin'))
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">Административные настройки</a>
            @endif
            <a href="{{ route('settings.balance') }}" class="btn btn-outline-secondary">Тариф</a>
        </div>
    </div>
@endsection
@section('content')
    @php
    $items = \App\Models\Role::orderBy('sort')->get();
    @endphp
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"></script>
    <script src="{{ asset('js/dashboard.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <script src="{{ asset('js/fields.js?v=') }}<?=random_int(1, 20000)?>"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/pages/carriers.css?v=') }}<?=random_int(1, 20000)?>"/>

    <div class="rounded-1 bg-white border t">
        <div class="t-body " data-model="roles">
            <div class="row g-0">
                <div class="col-lg-3 border-end">
                    <div class="c-top px-3 bg-light border-bottom bg-light d-flex align-items-center justify-content-between">
                        <h6 class="h6 m-0">Роли</h6>
                        <a href="/admin/roles/create" class="link" data-touch="false">Добавить</a>
                    </div>
                    <div class="c-body p-0">
                        <ul class="c-drag-list list-unstyled mb-0 js-sort">
                            @if(!isset($dataTypeContent->id))
                                <li class="side-list__item d-flex position-relative active">
                                    <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                    <div class="btn btn-light w-100">
                                        <span class="text-gray">(не заполнено)</span>
                                        <a class="dropdown-toggle" href="#" id="dd" role="button" data-toggle="dropdown" aria-expanded="false">
                                            <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                        </a>
                                    </div>                                            
                                </li>
                            @endif
                            @foreach($items as $item)
                            <li class="side-list__item d-flex position-relative @if(isset($dataTypeContent->id) && $dataTypeContent->id == $item->id) active @endif" data-id="{{ $item->id }}" data-model="roles" data-href="/roles/edit/{{$item->id}}">
                                <span class="btn btn-drag position-absolute start-0 top-0" ><svg class="icon icon-line"><use xlink:href="#icon-line"></use></svg></span>
                                <div class="btn btn-light w-100">
                                    <span @if(!$item->display_name) class="text-gray" @endif >{{ $item->display_name ?? '(не заполнено)' }}</span>
                                    <a class="dropdown-toggle" href="#" id="dd{{ $item->id }}" role="button" data-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dd{{ $item->id }}">
                                        <li><a class="dropdown-item js-delete-model" data-id="{{ $item->id }}" data-model="roles" href="#"><span class="text-danger">Удалить</span></a></li>
                                    </ul>
                                </div>                                            
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 carrier-content">
                    <div class="c-body p-4">
                        <form class="form-edit-add" role="form"
                              action="@if(isset($dataTypeContent->id)){{ route('voyager.'.$dataType->slug.'.update', $dataTypeContent->id) }}@else{{ route('voyager.'.$dataType->slug.'.store') }}@endif"
                              method="POST" enctype="multipart/form-data">

                            <!-- PUT Method if we are editing -->
                            @if(isset($dataTypeContent->id))
                                {{ method_field("PUT") }}
                            @endif

                            <!-- CSRF TOKEN -->
                            {{ csrf_field() }}

                            <div class="panel-body">

                                @if (count($errors) > 0)
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @foreach($dataType->addRows as $row)
                                    <div class="form-group">
                                        <label for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>

                                        {!! Voyager::formField($row, $dataType, $dataTypeContent) !!}

                                    </div>
                                @endforeach

                                <label for="permission">{{ __('voyager::generic.permissions') }}</label><br>
                                <a href="#" class="permission-select-all">{{ __('voyager::generic.select_all') }}</a> / <a href="#"  class="permission-deselect-all">{{ __('voyager::generic.deselect_all') }}</a>
                                <ul class="permissions checkbox">
                                    <?php
                                        $role_permissions = (isset($dataTypeContent)) ? $dataTypeContent->permissions->pluck('key')->toArray() : [];
                                    ?>
                                    @foreach(Voyager::model('Permission')->all()->groupBy('table_name') as $table => $permission)
                                        @php
                                            if($table)
                                                continue;
                                        @endphp
                                        <li>
                                            <input type="checkbox" id="{{$table}}" class="permission-group">
                                            <label for="{{$table}}"><strong>{{\Illuminate\Support\Str::title(str_replace('_',' ', $table))}}</strong></label>
                                            <ul>
                                                @foreach($permission as $perm)
                                                    @php
                                                        if(!$perm->visible)
                                                            continue;
                                                    @endphp
                                                    <li>
                                                        <input type="checkbox" id="permission-{{$perm->id}}" name="permissions[{{$perm->id}}]" class="the-permission" value="{{$perm->id}}" @if(in_array($perm->key, $role_permissions)) checked @endif>
                                                        <label for="permission-{{$perm->id}}">{{\Illuminate\Support\Str::title($perm->name)}}</label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endforeach
                                </ul>
                            </div><!-- panel-body -->
                            <div class="panel-footer">
                                <button type="submit" class="btn btn-primary">{{ __('voyager::generic.submit') }}</button>
                            </div>
                        </form>

                        <iframe id="form_target" name="form_target" style="display:none"></iframe>
                        <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post"
                              enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
                            {{ csrf_field() }}
                            <input name="image" id="upload_file" type="file"
                                   onchange="$('#my_form').submit();this.value='';">
                            <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            

            $('.permission-group').on('change', function(){
                $(this).siblings('ul').find("input[type='checkbox']").prop('checked', this.checked);
            });

            $('.permission-select-all').on('click', function(){
                $('ul.permissions').find("input[type='checkbox']").prop('checked', true);
                return false;
            });

            $('.permission-deselect-all').on('click', function(){
                $('ul.permissions').find("input[type='checkbox']").prop('checked', false);
                return false;
            });

            function parentChecked(){
                $('.permission-group').each(function(){
                    var allChecked = true;
                    $(this).siblings('ul').find("input[type='checkbox']").each(function(){
                        if(!this.checked) allChecked = false;
                    });
                    $(this).prop('checked', allChecked);
                });
            }

            parentChecked();

            $('.the-permission').on('change', function(){
                parentChecked();
            });
            $('body').on('click', '.js-submit-roles', function(){
                $('.form-edit-add').submit();
                $(this).addClass('disabled');
            });
        });
    </script>


@endsection
    