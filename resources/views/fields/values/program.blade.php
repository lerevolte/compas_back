<div class="js-editable" data-id="{{ isset($current) ? $current->id : '' }}" data-field="{{ $field->field }}" data-value="{{ isset($current) && $current->{$field->field} != ' ' ? $current->{$field->field} : '' }}" @if($field->field == 'date_delivery_status') data-type="date" @else data-type="{{ $field->type }}" @endif style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}">
    @if($field->field == 'store_name')
        @if($current->replic_num && $current->replic_num_split)
            <span class="replic-status-4">{{ $current->replic_num }}</span>-<span>{{ $current->replic_num_split }}</span>-{!! $current->{$field->field} && $current->{$field->field} != ' ' ? nl2br($current->getValue($field)) : '<span class="empty-val">не заполнено</span>' !!}
        @elseif($current->replic_num)
            <span class="replic-status-4">{{ $current->replic_num }}</span>-{!! $current->{$field->field} && $current->{$field->field} != ' ' ? nl2br($current->getValue($field)) : '<span class="empty-val">не заполнено</span>' !!}
        @elseif($current->replic_num_split)
            <span>{{ $current->replic_num_split }}</span>-{!! $current->{$field->field} && $current->{$field->field} != ' ' ? nl2br($current->getValue($field)) : '<span class="empty-val">не заполнено</span>' !!}
        @else
        {!! $current->{$field->field} && $current->{$field->field} != ' ' ? nl2br($current->getValue($field)) : '<span class="empty-val">не заполнено</span>' !!}
        @endif
    @else
        {!! $current->{$field->field} && $current->{$field->field} != ' ' ? nl2br($current->getValue($field)) : '<span class="empty-val">не заполнено</span>' !!}
    @endif
    
</div>