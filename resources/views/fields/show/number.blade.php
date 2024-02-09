<div class="g-2 flex-nowrap">
    <div class="col-lg-12">
        <div class="position-relative">
            @if($field_data->is_plural)
            <textarea id="{{ $field_data->field }}" name="{{ $field_data->field }}" class="form-control" value="{{ $value }}">{{ $value }}</textarea>
            @else
            <input id="{{ $field_data->field }}" name="{{ $field_data->field }}" type="text" class="form-control" value="{{ $value }}">
            @endif
        </div>                       
    </div>
</div>