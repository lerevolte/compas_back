
@php
$disabled_class = '';

if(($field->field == 'car_mark' || $field->field == 'car_model') && !$current->car_category) {
    $disabled_class = 'disabled';
}
if(($field->field == 'car_model') && !$current->car_mark) {
    $disabled_class = 'disabled';
}
@endphp
<div class="js-editable {{ $disabled_class }}" data-field="{{ $field->field }}" data-value="{{ $current->{$field->field} }}" data-type="{{ $field->type }}" style="{{ $field->label_color ? 'color:'.$field->label_color : ''}}" @if($field->field == 'car_mark' || $field->field == 'car_model') data-category="{{ $current->car_category }}" @endif @if($field->field == 'car_model') data-mark="{{ $current->car_mark }}" @endif>
    {!! $current->{$field->field} ? $current->getValue($field) : '<span class="empty-val">не заполнено</span>' !!}
</div>