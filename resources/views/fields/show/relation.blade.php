@php
if(!isset($options)) {
    $options = array();
    $field_details = json_decode($field->details, true);
    if(isset($field_details['table'])) {
        $options_o = array();
        if($field_details['table'] == 'car_marks') {
            
        } elseif($field_details['table'] == 'car_models') {
            
        } else {
            $options_o = \DB::table($field_details['table'])->whereNull('deleted_at')->get();
        }
        if(count($options_o))
            foreach($options_o as $option) {
                $options[$option->id] = (isset($option->display_name) ? $option->display_name : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
            }
    } else
        $options = $field_details['options'];
}
if($field->is_plural && $value) {
    $values = json_decode($value, true);
    if(!is_iterable($values)) {
        $values = array($values);
    }
    $objects = \DB::table($field_details['table'])->whereIntegerInRaw('id', $values)->get();
} else {
    if($field_details['table'] == 'users')
        $obj = \App\Models\User::find($value);
    else
        $obj = \DB::table($field_details['table'])->where('id', $value)->first();
}

@endphp
<div class="card-relations js-editable" data-field="{{ $field->field }}" data-type="relation" @if($field->is_plural) data-multiple="1" @endif>
    <div class="mb-2 card p-3 bg-light d-none">
        <div class="d-flex justify-content-start align-items-center" style="flex-grow: 1;">
            <div class="dropdown">
                <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                    <svg class="icon icon-dots">
                        <use xlink:href="#icon-dots"></use>
                    </svg>
                </a>

                <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                    <a href="#" class="dropdown-item js-delete-relation-object text-danger" data-id="" data-field="{{ $field->id }}">Удалить</a>
                </div>
            </div>
            <h5 class="mb-0 w-100">
                <div class="position-relative">
                    <div class="row g-2 flex-nowrap">
                        <div class="col-lg-12">
                            <div class="position-relative w-100">
                                <select name="select" >
                                    <option value="" selected>не выбрано</option>
                                    @foreach($options as $k => $option)
                                        @if($option)
                                        @if(isset($values) && is_array($values) && in_array($k, $values))
                                        @else
                                        <option value="{{ $k }}" @if ($value == $k || isset($values) && is_array($values) && in_array($k, $values)) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                                        @endif
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </h5>
        </div>
    </div>
    @if($field->is_plural && isset($objects))
        @foreach($objects as $object)
        <div class="mb-2 card p-3 bg-light">
                <div class="d-flex justify-content-start align-items-center" style="flex-grow: 1;">
                    <div class="dropdown">
                        <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                            <svg class="icon icon-dots">
                                <use xlink:href="#icon-dots"></use>
                            </svg>
                        </a>

                        <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                            <a href="#" class="dropdown-item">Посмотреть</a>
                            <a class="dropdown-item-fancy js-change-relation-object">Редактировать</a>
                            <a href="#" class="dropdown-item js-delete-relation-object text-danger" data-id="{{ $object->id }}" data-field="{{ $field->id }}">Удалить</a>
                        </div>
                    </div>
                    <h5 class="mb-0 w-100 js-relation-input d-flex align-items-center">
                        @php              
                            $photo = null;
                            if(isset($object->photo) && $object->photo) {
                                $photos = json_decode($object->photo, true);
                                if(is_array($photos)) {
                                    $photo = \App\Models\File::find($photos[0]);
                                }
                            }

                            if(isset($object->avatar) && $object->avatar) {
                                $photos = json_decode($object->avatar, true);
                                if(is_array($photos)) {
                                    $photo = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->first();
                                }
                            }
                            
                        @endphp
                        
                    
                        @if($photo)
                        <div class="pic me-3" style="width:30px;height:30px;overflow: hidden;background: url({{ \Thumbnail::src(str_replace('/public', '/tenantopt6/app/public', Storage::disk('public')->url($photo->path)))->heighten(200)->url() }});background-size: cover;background-position: center;">
                            
                        </div>
                        @endif
                        @if(isset($object->avatar) && isset($photo->path))
                            <div class="pic me-3" style="width:30px;height:30px;overflow: hidden;background: url({{ \Thumbnail::src(str_replace('/public', '/tenantopt6/app/public', Storage::disk('public')->url($photo->path)))->heighten(200)->url() }});background-size: cover;background-position: center;">
                            
                            </div>
                        @elseif(isset($object->avatar))
                            @php
                            $name = $last_name = '';
                            if($users[$history_item->user_id]->name)
                                $name = mb_substr($users[$history_item->user_id]->name,0,1);
                            if($users[$history_item->user_id]->last_name)
                                $last_name = mb_substr($users[$history_item->user_id]->last_name,0,1);
                            @endphp
                            <div class="pic me-3" style="width:30px;height:30px;overflow: hidden;background: {{ $users[$history_item->user_id]->getColor() }};">
                                {{ ucfirst($name).ucfirst($last_name) }}
                            </div>
                        @endif
                        
                        <!-- <span class="empty-val">{{ $object->name }}</span> -->
                        <div class="position-relative w-100 d-none1">
                            <div class="row g-2 flex-nowrap">
                                <div class="col-lg-12">
                                    <div class="position-relative">
                                        <select class="js-select" name="select" >
                                            <option value="" selected>не выбрано</option>
                                            @foreach($options as $k => $option)
                                                @if($option)
                                                <!-- if(isset($values) && is_array($values) && in_array($k, $values))
                                                else -->
                                                <option value="{{ $k }}" @if ($object->id == $k) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                                                <!--endif-->
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </h5>
                </div>
        </div>
        @endforeach
    @elseif(!$field->is_plural)
        <div class="mb-2 card p-3 bg-light">
            <div class="d-flex justify-content-start align-items-center" style="flex-grow: 1;">
                <div class="dropdown">
                    <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                        <svg class="icon icon-dots">
                            <use xlink:href="#icon-dots"></use>
                        </svg>
                    </a>

                    <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                        <a href="#" class="dropdown-item">Посмотреть</a>
                        <a class="dropdown-item-fancy js-change-relation-object">Редактировать</a>
                        @if($obj)
                        <a href="#" class="dropdown-item js-delete-relation-object text-danger" data-id="{{ $obj->id }}" data-field="{{ $field->id }}">Удалить</a>
                        @endif
                    </div>
                </div>
                <h5 class="mb-0 w-100 d-flex align-items-center js-relation-input">
                    

                    @if(!$value)
                    <!-- <span class="empty-val">не выбрано</span> -->
                    @else
                        @php        
                            $photo = null;
                            if(isset($obj->photo) && $obj->photo) {
                                $photos = json_decode($obj->photo, true);
                                if(is_array($photos)) {
                                    $photo = \App\Models\File::find($photos[0]);
                                }
                            }

                            if(isset($obj->avatar) && $obj->avatar) {
                                $photos = json_decode($obj->avatar, true);
                                if(is_array($photos)) {
                                    $photo = \App\Models\File::whereIn('id', $value)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$value).")"))->first();
                                }
                            }
                            
                        @endphp
                        @if($photo)
                        <div class="pic me-3 d-none" style="width:30px;height:30px;overflow: hidden;background: url({{ \Thumbnail::src(str_replace('/public', '/tenantopt6/app/public', Storage::disk('public')->url($photo->path)))->heighten(200)->url() }});background-size: cover;background-position: center;">
                            
                        </div>
                        @endif
                        
                        @if(isset($obj->avatar) && isset($photo->path))
                            <div class="pic me-3 d-none" style="width:30px;height:30px;overflow: hidden;background: url({{ \Thumbnail::src(str_replace('/public', '/tenantopt6/app/public', Storage::disk('public')->url($photo->path)))->heighten(200)->url() }});background-size: cover;background-position: center;">
                            
                            </div>
                        @elseif($field_details['table'] == 'users')
                            @php
                            $name = $last_name = '';
                            if($obj->name)
                                $name = mb_substr($obj->name,0,1);
                            if($obj->last_name)
                                $last_name = mb_substr($obj->last_name,0,1);
                            @endphp
                            <div class="pic me-3 d-none" style="width:30px;height:30px;overflow: hidden;background: {{ $obj->getColor() }};">
                                {{ ucfirst($name).ucfirst($last_name) }}
                            </div>
                        @endif
                        @foreach($options as $k => $option)
                            @if($value == $k)
                            <!-- <span class="empty-val">{{ $option }}</span> -->
                            @endif
                        @endforeach
                    @endif
                    <div class="position-relative w-100 d-none1">
                        <div class="row g-2 flex-nowrap">
                            <div class="col-lg-12">
                                <div class="position-relative">
                                    <select class="js-select" name="select" >
                                        <option value="" selected>не выбрано</option>
                                        @foreach($options as $k => $option)
                                            @if($option)
                                            <option value="{{ $k }}" @if ($value == $k) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </h5>
            </div>
        </div>
    @endif
</div>
@if($field->is_plural)
<a class="d-inline-block link show me-2 fs-14 js-add-relation-object" href="javascript:;" >
    + Добавить
</a>
@endif