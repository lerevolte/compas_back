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
        <div class="position-relative">
            <div class="row g-2 flex-nowrap">
                <div class="col-lg-12">
                    <div class="position-relative">
                        <select name="{{ $field_data->field }}" class="js-select" multiple>
                            <option value="">не выбрано</option>
                            @foreach($options as $k => $option)
                                @if($option)
                                <option value="{{ $k }}" data-value="{{ $option }}" @if(isset($config['fields'][$field_data->field]) && $config['fields'][$field_data->field] == $k) selected @endif>{{ $option }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</li>