<div class="js-editable" data-field="{{ $field->field }}" data-value="{{ isset($current) ? $current->{$field->field} : '' }}" data-type="{{ $field->type }}" style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}">
    {!! $current->{$field->field} ? $current->getValue($field) : '<span class="empty-val">не заполнено</span>' !!}
</div>