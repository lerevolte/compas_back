@php
if(!isset($options)) {
    $options = array();
    $field_details = json_decode($field->details, true);
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
if($field->is_plural) {
    $values = $value;
}
@endphp
<div class="card-relations js-editable" data-field="{{ $field->field }}" data-type="relation" @if($field->is_plural) data-multiple="1" @endif>
    <div class="mt-2 card p-3 bg-light d-none">
        <div class="d-flex justify-content-start align-items-center" style="flex-grow: 1;">
            <div class="dropdown">
                <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                    <svg class="icon icon-dots">
                        <use xlink:href="#icon-dots"></use>
                    </svg>
                </a>

                <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                    <a href="#" class="dropdown-item">Посмотреть</a>
                    <a href="#changeDriver" data-fancybox data-touch="false" class="dropdown-item-fancy">Редактировать</a>
                    <a href="#" class="dropdown-item js-delete-relation text-danger" data-id="" data-field="{{ $field->id }}">Удалить</a>
                </div>
            </div>
            <h5 class="mb-0 w-100">
                <div class="position-relative">
                    <div class="row g-2 flex-nowrap">
                        <div class="col-lg-12">
                            <div class="position-relative">
                                <select name="select" >
                                    <option value="" selected>не выбрано</option>
                                    @foreach($options as $k => $option)
                                        @if($option)
                                        <option value="{{ $k }}" @if ($value == $k || isset($values) && is_array($values) && in_array($k, $values)) selected @endif data-value="{{ $option }}">{{ $option }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </h5>
        </div>
    </div>
    <!-- <div class="card p-3 bg-light">
        
        <div class="d-flex align-items-center">

            <div class="d-flex justify-content-start" style="flex-grow: 1;">
                <div class="dropdown">
                    <a class="dropdown-toggle me-2" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                        <svg class="icon icon-dots">
                            <use xlink:href="#icon-dots"></use>
                        </svg>
                    </a>

                    <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions dropdown-menu-left" href="javascript:;" aria-labelledby="dropdownMenuButton" x-placement="bottom-start" >
                        <a href="#" class="dropdown-item">Посмотреть</a>
                        <a href="#changeDriver" data-fancybox data-touch="false" class="dropdown-item-fancy">Заменить</a>
                        <a href="#" class="dropdown-item js-delete-relation" data-id="" data-model="cars" data-field="driver_id">Удалить</a>
                    </div>
                </div>
                <h5 class="h5 mb-0">
                    <span href="#changeDriver" data-fancybox data-touch="false" class="empty-val">не заполнено</span>
                </h5>
            </div>

        </div>
    </div> -->
</div>
@if($field->is_plural)
<a class="mt-2 d-inline-block link show me-2 fs-14 js-add-relation-object" href="javascript:;" >
    + Добавить
</a>
@endif