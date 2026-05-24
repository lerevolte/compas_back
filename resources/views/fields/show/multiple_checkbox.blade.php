@php
if(!isset($options)) {
    $options = array();
    $field_details = json_decode($field_data->details, true);
    if(isset($field_details['table'])) {
        $options_o = \DB::table($field_details['table'])->get();
        foreach($options_o as $option) {
            $options[$option->id] = $option->display_name ? $option->display_name : $option->name;
        }
    } else
        $options = $field_details['options'];
    if($value)
        $value = json_decode($value, true);
}
@endphp
<!-- <div class="g-2 flex-nowrap">
    <div class="col-lg-12">
        <div class="position-relative border">
            <div class="form-group py-2 px-3">
                <ul class="list-unstyled">
                    @php
                    $i = 0;
                    @endphp
                    @foreach($options as $key => $option)
                    <li class="mb-1">
                        <div class="form-check">
                            <input class="form-check-input" name="{{ $field_data->field }}[]" type="checkbox" value="{{ $key }}" id="ddd-{{ $field_data->field }}-{{ $i }}" @if(is_array($value) && in_array($key, $value)) checked @endif>
                            <label class="form-check-label" for="ddd-{{ $field_data->field }}-{{ $i }}">
                               {{ $option }}
                            </label>
                        </div>
                    </li>
                    @php
                    $i++;
                    @endphp
                    @endforeach
                    
                </ul>
            </div>
        </div>
    </div>
</div> -->

<div class="position-relative">
    <div class="row g-2 flex-nowrap">
        <div class="col-lg-12">
            <div class="position-relative">
                <select name="{{ $field_data->field }}" multiple class="js-select">
                    @foreach($options as $k => $option)
                        @if($option)
                        <option value="{{ $k }}" @if(is_array($value) && in_array($k, $value)) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>