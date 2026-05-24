@php
$config = array();
if(isset($filter))
    $config = json_decode($filter->config, true);
@endphp
<li class="col-lg-12 active" data-field="{{ $field_data->field }}">
    <div class="position-relative d-flex align-items-center mb-1 toolbar-field">
        <span class="btn btn-sort js-edit-position btn-drag btn-drag-field btn-xs p-0 text-muted ui-sortable-handle">
            <svg class="icon icon-linedot">
                <use xlink:href="#icon-linedot"></use>
            </svg>
        </span>
        <div class="label">
            {{ $field_data->display_name }}
        </div>
    </div>
    <div>
        <div class="g-2 flex-nowrap">
            <div class="col-lg-12">
                <div class="position-relative">
                    @if($field_data->is_plural)
                    <textarea id="{{ $field_data->field }}" name="{{ $field_data->field }}" class="form-control" value="">
                        @if(isset($config['fields'][$field_data->field]))
                            {{ $config['fields'][$field_data->field] }}
                        @endif
                    </textarea>
                    @else
                        <input id="{{ $field_data->field }}" name="{{ $field_data->field }}" type="text" class="form-control" value="{{ isset($config['fields'][$field_data->field]) ? $config['fields'][$field_data->field] : '' }}">
                    @endif
                </div>                       
            </div>
        </div>
    </div>
</li>