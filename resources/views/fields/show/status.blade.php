@php
$status_select = '<div class="form-group status-group js-editable position-relative" data-id="'.$current->id.'">';
$tenant = tenant('id');
$fields_values[$field->field] = \App\Models\Field::getStatuses($field->id);
$exist = false;
$first = null;
$text_value = '';
$i = 0;
$color = '#000';
foreach($fields_values[$field->field] as $list_item) {
    if(!$list_item->is_hidden)
        $i++;
    if($i == 1) {
        $first = $list_item;
    }
    if($current->{$field->field} && $current->{$field->field} == $list_item->id) {
        if($list_item->file)
            $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
        else
            $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: '.$list_item->color.'"></div>';
        if($list_item->is_hidden)
            $color = $list_item->color;
        $text_value = $list_item->name;
        $exist = true;
    } elseif(!$current->{$field->field} && !$list_item->is_hidden) {
        if($list_item->file)
            $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
        else
            $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: '.$list_item->color.'"></div>';
        $exist = true;
        $text_value = $list_item->name;
        break;
    }

}
if(!$exist && $first) {
    $text_value = $first->name;
    if($first->file)
        $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$first->file.') '.$list_item->color.'"></div>';
    else
        $status_select.= '<div class="point_status_rect" data-id="'.$current->id.'" style="background: '.$first->color.'"></div>';
}
$status_select.= '<select name="'.$field->field.'" class="form-control form-control-status form-control-status-select field-status-select2 js-field-status" data-field="'.$field->id.'" data-color="'.$color.'" data-type="status">';
if($color != '')
    $status_select.= '<option disabled selected value></option>';
foreach($fields_values[$field->field] as $list_item) {
    
    if(!$list_item->is_hidden)
        $status_select.= '<option data-file="/storage/tenant'.$tenant.'/app/'.$list_item->file.'" data-color="'.$list_item->color.'" value="'.$list_item->id.'"'.($current->{$field->field} == $list_item->id ? ' selected="selected"' : '').'>'.$list_item->name.'</option>';
}
$status_select.= '</select><span class="js-select-text">'.$text_value.'</span></div>';
@endphp
{!! $status_select !!}