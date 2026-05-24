@php
if(!isset($options)) {
    $options = array();
    $field_details = json_decode($field_data->details, true);
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
$values = array();
if($field_data->is_plural && $value) {
    $values = $value;
}
@endphp
<div class="position-relative">
    <ul class="row g-2 ps-0 flex-nowrap">
        <li class="col-lg-12">
            <div class="position-relative">
                <select name="select" class="js-select" @if($field_data->is_plural) multiple @endif data-type="select_dropdown">
                    @if(!$field_data->is_plural)
                    <option value="" @if (!$value) selected @endif>не выбрано</option>
                    @endif
                    @foreach($options as $k => $option)
                        @if($option)
                        <option value="{{ $k }}" @if ($value == $k || isset($values) && is_iterable($values) && in_array($k, $values)) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </li>
    </ul>
</div>