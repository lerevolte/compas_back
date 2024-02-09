@php
    if(!isset($names)) {
        $row_type = \DB::table('data_types')->where('name', $model)->first();
        $names = array();
        if(strstr($field, ',')) {
            $names = explode(',', $field);
            $value = explode(',', $value);
            foreach ($names as $key => $name) {
                $field_data = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => trim($name)])->first();
                $field_details = json_decode($field_data->details, true);
                if(isset($field_details['label']))
                    $labels[$name] = $field_details['label'];
                else
                    $labels[$name] = $field_data->display_name;
                
            }
        }
    }
@endphp
    <div class="position-relative">
        <div class="row g-2">
            @foreach($names as $key => $name)
            <div class="col-12">
                <div class="label text-secondary">
                    {{ $labels[$name] }}
                </div>
                <div class="position-relative">
                    <input name="{{ $name }}" type="text" class="form-control" value="{{ $value[$key] }}">
                </div>
            </div>
            @endforeach
        
        </div>
        
    </div>