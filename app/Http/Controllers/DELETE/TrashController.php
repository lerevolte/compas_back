<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Models\SidebarItem;
use App\Helpers\ValueHelper;

class TrashController extends Controller
{
	public function index(Request $request)
    {
    	$menu_items = array();
    	$types = collect(\DB::table('data_types')->get())->keyBy('model_name')->toArray();
    	foreach($types as $model => $type) {
    		if(!strstr($model,'TCG') && $model::onlyTrashed()->first())
    			$menu_items[] = $type;
    	}

    	return view('trash.index', compact('menu_items'));
    }

    public function list($slug, Request $request)
    {
        cache()->flush();
        $settings = get_settings();
        $tenant = tenant('id');
        $menu_items = array();
    	$types = collect(\DB::table('data_types')->get())->keyBy('model_name')->toArray();
    	foreach($types as $model => $type) {
    		if(!strstr($model,'TCG') && $model::onlyTrashed()->first()) {
    			$menu_items[] = $type;
    			if($type->slug == $slug)
    				$active_menu = $type;
    		}
    	}
        
        $user = \Auth::user();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        $entity_class = $entity->model_name;
        //$entity
        $model_fields = $entity_class::getFields();
        $table_settings_name = 'table_page_orders';
        $visible_fields = \App\Models\Field::getVisibleFields($slug);

        $table_settings = $user->{$table_settings_name} ? json_decode($user->{$table_settings_name}, true) : '';
        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ?? '';
            $perms['read'][$field->field] = (!optional($request->user())->canRead($field->field, $slug) ? 'disabled':'');
            $perms['write'][$field->field] = (!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'');
        }

        $items = $entity_class::onlyTrashed()->orderBy('id', 'desc')/*->take(50)*/->get();

        $objects = array();
        $field_values = array();
        foreach ($items as $key => $item) {
            $delete_btn = '<a class="dropdown-item js-delete-model" data-model="'.$slug.'" href="javascript:;"  data-id="'.$item->id.'"><span class="red">Удалить</span></a>';
            $history_btn = '<a class="dropdown-item js-history" href="javascript:;" data-entity="'.$slug.'" data-id="'.$item->id.'">История</a>';
            $last_col = '<div class="dropdown">
                  <button class="btn btn-transparent px-0 btn-actions" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <svg class="icon icon-dots"><use xlink:href="#icon-dots"></use></svg>
                  </button>
                  <div class="new-dropdown-menu dropdown-menu dropdown-menu__actions" aria-labelledby="dropdownMenuButton">
                    '.$history_btn.'
                    '.$delete_btn.'
                  </div>
                </div>';
            $data = array(
                'id' => $item->id,
                'checkbox' => '<div class="not-selectable disabled justify-content-center"><label class="checkbox-container m-auto"><input type="checkbox" class="js-checkbox-row"><span class="checkmark"></span></label></div>',
                'actions' => '<div class="not-selectable disabled" data-field="actions">'.$last_col.'</div>'
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data)) {
                    $value = $item->{$field->field};
                    if($field->field == 'products') {
                        $value = ValueHelper::prepareProducts($item->{$field->field});
                        $data[$field->field] = '<div class="js-editable '.(!optional($request->user())->canWrite($field->field, $slug) ? 'disabled':'').'" data-field="'.$field->field.'" data-value="'.$item->{$field->field} .'" data-type="'.$field->type.'" data-model="orders" title="'.strip_tags($value).'" style="'.($field->label_color ? 'color:'.$field->label_color : '').'">'.$value.'</div>';
                    } elseif(($field->type == 'file' || $field->type =='image') && $item->{$field->field} && $item->{$field->field} != 'null') {
                        $photos = $item->getValue($field);
                        $html = '';
                        if(is_array($photos)) {
	                        $html = '<div class="table-gallery">
	                            <div class="table-gallery__inner">';

	                        foreach($photos as $photo_key => $photo) {
	                            if(strstr($photo->name, '.pdf'))
	                                $html.='<a href="/storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
	                                            <picture class="pdf"><source srcset="/img/pdf.svg" type="image/webp"><img  src="/img/pdf.svg" ></picture>
	                                        </a>';

	                            elseif(!strstr($photo->name, '.jpeg') && !strstr($photo->name, '.jpg') && !strstr($photo->name, '.png'))
	                                $html.='<a href="storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
	                                            <picture class="pdf"><source srcset="/img/pdf.svg" type="image/webp"><img  src="/img/pdf.svg" ></picture>
	                                        </a>';
	                            else
	                                $html.='<a href="/storage/tenant'.$tenant.'/app/'.$photo->path.'" data-fancybox="gallery-'.$field->id.'-'.$item->id.'" target="_blank" '.($photo_key ? 'class="d-none"':'').'>
	                                            <picture><source srcset="'.\Thumbnail::src('https://opt6.compas.pro'.\Storage::disk()->url('tenant'.$tenant.'/app/'.$photo->path))->heighten(200)->url().'" type="image/webp"><img class="table-gallery__preview" src="'.\Thumbnail::src('https://opt6.compas.pro'.\Storage::disk()->url('tenant'.$tenant.'/app/'.$photo->path))->heighten(200)->url().'" ></picture>
	                                        </a>';
	                        }
	                        $html.= '
	                            </div>
	                            <span class="table-gallery__count">'.count($photos).'</span>
	                         </div>';
	                    }
                        $data[$field->field] = $html;
                    } elseif(!isset($settings[$slug]['list_values'][$field->field])) {

                        $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" data-value="'.$item->{$field->field}.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';
                    } elseif(isset($settings[$slug]['list_values'][$field->field]) && $field->type != 'status') {
                        if($field->type == 'select_dropdown' && $field->is_plural && is_array(json_decode($value, true)))
                            $value = implode(', ', json_decode($value, true));
                        $data[$field->field] = '<div class="js-editable position-relative" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'" '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').'>'.$value.'</div>';;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_value => $list_item) {
                            if($list_value == $value) {
                                $data[$field->field] = '<div class="js-editable '.$settings[$slug]['perms'][$field->field]['write'].'" data-field="'.$field->field.'" data-type="'.$field->type.'" data-model="'.$slug.'"  '.($field->label_color ? 'style="color:'.$field->label_color.'"' : '').' data-value="'.$item->{$field->field}.'">'.$list_item.'</div>';
                            }
                        };
                    } else {
                        $status_select = '<div data-field="'.$field->field.'" class="form-group status-group disabled" data-id="'.$item->id.'" data-type="'.$field->type.'">';
                        $text_value = '';
                        $exist = false;
                        $i = 0;
                        foreach($settings[$slug]['list_values'][$field->field] as $list_item) {
                            if(!$i) {
                                $first_item = $list_item;
                            }
                            if($field->field == 'point_status' && $item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect '.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?'comment-cancel-tooltip':'').'"  data-title="'.(($item->{$field->field} == 15 || $item->{$field->field} == 16)?($item->comment_cancel ?? ' '):' ').'" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                            } elseif($item->{$field->field} && $item->{$field->field} == $list_item->id) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"><div class="d-none">'.$item->{$field->field}.'</div></div>';
                                $exist = true;
                            } elseif(!$item->{$field->field}) {
                                $text_value = $list_item->name;
                                if($list_item->file)
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$list_item->file.') '.$list_item->color.'"></div>';
                                else
                                    $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$list_item->color.'"></div>';
                                $exist = true;
                                break;
                            }
                            $i++;
                        };
                        if(!$exist) {
                            if($first_item->file)
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: url(/storage/tenant'.$tenant.'/app/'.$first_item->file.') '.$first_item->color.'"></div>';
                            else
                                $status_select.= '<div class="d-none">'.$item->{$field->field}.'</div><div class="point_status_rect" data-id="'.$item->id.'" style="background: '.$first_item->color.'"></div>';
                        }
                        
                        $status_select.= '<span class="js-select-text">'.$text_value.'</span></div>';
                        $data[$field->field] = $status_select;
                    }
                }
            }
            
            $objects[] = $data;
        }
        $model = $slug;
        $table_settings = ($user->tables ? json_decode($user->tables, true) : array());

        return view('trash.list', compact('user', 'objects', 'model_fields', 'visible_fields', 'table_settings', 'entity', 'settings', 'model', 'table_settings', 'menu_items', 'active_menu'));
    }
}