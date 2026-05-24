<div class="g-2 flex-nowrap tyt">
    <div class="col-lg-12">
        <div class="position-relative">
            @if($field_data->is_plural)
            <textarea id="{{ $field_data->field }}" name="{{ $field_data->field }}" class="form-control" value="{{ $value }}">{{ $value }}</textarea>
            @else
                @if($field_data->field == 'store_name')
                    @php
                    $order = \App\Models\Order::find($id);
                    @endphp
                    @if($order->replic_num || $order->replic_num_split)
                        <div class="split-name">
                            <div class="split-name-bg"> 
                                @if($order->replic_num && $order->replic_num_split)
                                    <span class="replic-status-4">{{ $order->replic_num }}</span>-<span>{{ $order->replic_num_split }}</span>-
                                @elseif($order->replic_num)
                                    <span class="replic-status-4">{{ $order->replic_num }}</span>-
                                @elseif($order->replic_num_split)
                                    <span>{{ $order->replic_num_split }}</span>-
                                @endif
                            </div>
                            <input id="{{ $field_data->field }}" name="{{ $field_data->field }}" type="text" @if($field_data->field == 'date_delivery_status') data-type="date" @endif class="form-control" value="{{ $value }}">
                        </div>
                    @else
                        <input id="{{ $field_data->field }}" name="{{ $field_data->field }}" type="text" @if($field_data->field == 'date_delivery_status') data-type="date" @endif class="form-control" value="{{ $value }}">
                    @endif
                @else
                    <input id="{{ $field_data->field }}" name="{{ $field_data->field }}" type="text" @if($field_data->field == 'date_delivery_status') data-type="date" @endif class="form-control" value="{{ $value }}">
                @endif
            @endif
        </div>                       
    </div>
</div>