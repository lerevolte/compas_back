<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use App\Models\Field;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Exports\ObjectExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\CrudService;
use App\Services\SearchService;

class ObjectController extends Controller
{
    private CrudService $crudService;
    private SearchService $searchService;

    public function __construct(CrudService $crudService, SearchService $searchService)
    {
        $this->crudService = $crudService;
        $this->searchService = $searchService;
    }

    public function list($slug, Request $request)
    {
        $data = \App\Models\EntityObject::list($slug, $request);

        return response()->json($data);

    }

    public function compose_list($slug, Request $request)
    {
        $profile = \Auth::user();
        $data_type_id = \DB::table('data_types')->where('slug', $slug)->first()->id;
        $permissions = array();
        if($profile->role_id) {
            $permissions = $profile->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
        }
            
        if(!$profile->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !$profile->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        if(isset($permissions) && !is_array($permissions))
            $permissions = $permissions->toArray();
        //api/tables/$slug
        $table = \App\Models\Table::get($slug);
        if(isset($table['error'])) {
            return response()->json([
                    'message' => $table['error']['message']
                ], $table['error']['code']);
        }
        
        //api/objects/$slug
        $add_params = [];//['sort_order' => $sort_order, 'sort_field' => $sort_field];
        if(isset($permissions['read_p']) && $permissions['read_p'] == 'Y' && !$profile->is_admin)
            $add_params['filter']['user_id'] = \Auth::user()->id;
        $request->merge($add_params);
        $list = \App\Models\EntityObject::list($slug, $request);
        if(isset($list['error'])) {
            return response()->json([
                    'message' => $list['error']['message']
                ], $list['error']['code']);
        }
        //api/fields/$slug
        $fields = \App\Models\Field::list($slug);
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get();
        //api/roles
        $roles = \App\Models\Role::list();
        
        //api/sidebar/get
        $sidebar = \Auth::user()->getSidebar();
        //api/filter/$slug
        $filters = \App\Models\Filter::list($slug);
        $categories = array();
        if($slug == 'products') {
            $categories = \Modules\Products\Entities\Category::get()->toTree()->toArray();
        }
        if($slug == 'instructions') {
            $categories = \Modules\Instructions\Entities\Category::get()->toTree()->toArray();
        }
        if($slug == 'faq') {
            $categories = \App\Models\FaqCategory::get()->toTree()->toArray();
        }
        if($slug == 'knowledge') {
            $categories = \App\Models\KnowledgeCategory::get()->toTree()->toArray();
        }
        if($slug == 'articles') {
            $categories = \App\Models\BlogCategory::get()->toTree()->toArray();
        }
        if($slug == 'guides') {
            $categories = \App\Models\GuideCategory::get()->toTree()->toArray();
        }
        foreach($categories as $k => $category) {
            $name = json_decode($category['name'], true);
            if(isset($name['value'])) {
                $categories[$k]['name'] = $name['value'];
            }

            foreach($category['children'] as $i => $child) {
                $name = json_decode($child['name'], true);
                if(isset($name['value'])) {
                    $categories[$k]['children'][$i]['name'] = $name['value'];
                }
            }

            
        }

        $tabs = \App\Models\Menu::tabs($slug);
        
        
        $data = array(
            'list' => $list,
            'table' => $table,
            'fields' => $fields,
            'entities' => $entities,
            'filters' => $filters,
            'categories' => $categories,
            'permissions' => $permissions
        );

        return response()->json($data);
    }

    public function compose_show($slug, $id, Request $request)
    {
        $isExternalAccess = !$request->headers->has('Authorization') && !app('auth')->guard('api')->check();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        if(!$entity)
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        $data_type_id = $entity->id;
        $permissions = array();

        if(!$isExternalAccess && \Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
                    'read_p',
                    'create_p',
                    'update_p',
                    'delete_p',
                    'export_p',
                    'import_p'
                ])->where('entity_id', $data_type_id)->first();
            }
            $permissions = $permissions->toArray();
        } elseif($isExternalAccess) {
            $permissions = ['read_p' => 'A'];
        }
        if(\Auth::user() && $slug == 'users' && $id == \Auth::user()->id || \Auth::user() && \Auth::user()->is_admin)
            $permissions['update_p'] = 'A';
        if(isset($permissions['read_p']) && $permissions['read_p'] == 'N' && ($slug != 'users' || 
            $slug == 'users' && \Auth::user()->id != $id) && !\Auth::user()->is_admin) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }
        //api/objects/$slug/$id
        $add_params = [];
        if(\Auth::user() && isset($permissions['read_p']) && $permissions['read_p'] == 'Y')
            $add_params['user_id'] = \Auth::user()->id;
        if($request->is_copy)
            $add_params['is_copy'] = 1;
        $request->merge($add_params);

        $detail = \App\Models\EntityObject::detail($slug, $id, $request);
        if(isset($detail['error'])) {
            return response()->json([
                    'message' => $detail['error']['message']
                ], $detail['error']['code']);
        }
        //api/objects/products?order_id=$id
        $products = array();
        if($slug == 'logistic_tasks' && $id) {
            $nrequest = new Request(['order_id' => $id]);
            $products = \App\Models\EntityObject::list('products', $nrequest);
        }
        //api/tables/order_products
        $table = array();
        if($slug == 'logistic_tasks')
            $table = \App\Models\Table::get_order_products();
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get();
        //api/history/$slug/$id
        if(!$id || $request->is_copy) {
            $history_events = $history_fields = array();
        } else {
            $history_events = \App\Models\History::list($slug, $id, null, new Request(['filter' => 'events']));
            $history_fields = \App\Models\History::list($slug, $id, null);
        }

        //api/entities/$slug/menu
        $menu = \App\Models\Menu::get($slug);
        
        $data = array(
            'detail' => $detail,
            'table' => array(
                'tableKeys' => $table,
                'tableBody' => $products
            ),
            'history_events' => $history_events,
            'history_fields' => $history_fields,
            'tabs' => $menu,
            'permissions' => $permissions
        );

        return response()->json($data);
    }

    public function compose_show_module($slug, $id, $module, Request $request)
    {
        //api/objects/$slug/$id
        $request = new Request([]);
        $detail = \App\Models\EntityObject::detail_module($slug, $id, $module, $request);
        if(isset($detail['error'])) {
            return response()->json([
                    'message' => $detail['error']['message']
                ], $detail['error']['code']);
        }
        //api/objects/products?order_id=$id
        $products = array();
        if($slug == 'logistic_tasks') {
            $request = new Request(['order_id' => $id]);
            $products = \App\Models\EntityObject::list('products', $request);
        }
        //api/tables/order_products
        $table = \App\Models\Table::get_order_products();
        //api/entities
        $entities = \DB::table('data_types')->select(['slug', 'title_singular', 'title_plural', 'color'])->get();
        //api/history/$slug/$id
        $history_events = \App\Models\History::list($slug, $id, $module, new Request(['filter' => 'events']));
        $history_fields = \App\Models\History::list($slug, $id, $module);
        //api/entities/$slug/menu
        $menu = \App\Models\Menu::get($slug);

        //check module entities
        $module = \App\Models\Module::where('slug', $module)->firstOrFail();
        $related_entities = $module->statusesEntities($slug, $id);

        $data_type_id = \DB::table('data_types')->where('slug', $slug)->first()->id;

        $permissions = \Auth::user()->role->permissions()->select([
            'read_p',
            'create_p',
            'update_p',
            'delete_p',
            'export_p',
            'import_p'
        ])->where('entity_id', $data_type_id)->get()->toArray();
            
        $data = array(
            'detail' => $detail,
            'products' => $products,
            'table' => $table,
            'history_events' => $history_events,
            'history_fields' => $history_fields,
            'menu' => $menu,
            'related_entities' => $related_entities,
            'permissions' => $permissions
        );

        return response()->json($data);
    }
   

    public function batch($slug, Request $request)
    {
        $result = $this->crudService->batch($slug, $request->rows);

        return response()->json($result, $result['status']);
    }

    public function delete($slug, Request $request)
    {
        $result = $this->crudService->delete($slug, $request->ids);

        return response()->json($result, $result['status']);

    }

    public function restore($slug, Request $request)
    {
        $result = $this->crudService->restore($slug, $request->ids);

        return response()->json($result, $result['status']);
    }

    public function restore_single($slug, $id)
    {
        $settings = app('settings');
        if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
            return response()->json([
                'message' => 'Entity not found'
            ], 404);
        }
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $item = $entity_class::withTrashed()->where('id', $id)->first();
        $history_text = 'Восстановлена запись: '.$item->id;
        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $item->id, 'user_id' => \Auth::user()->id, 'text' => $history_text, 'event' => 'OBJECT_RESTORED']);
        $history->save();
        $history_response_events[] = \App\Models\History::getDataList([$history]);
        $item->restore();
        \App\Models\Settings::clear_cache();
        $settings = \App\Models\Settings::get(true);
        $data = $item->getData(array(), $settings);

        \App\Events\ObjectUpdated::dispatch('ObjectRestored', $data, tenant('id'));
        
        return response()->json(['success' => true, 'details' => $data['viewDetail'], 'history_events' => $history_response_events]);
    }

    public function search(Request $request)
    {
        $result = $this->searchService->find($request->all());

        return response()->json($result);
    }

    public function export($slug, Request $request) 
    {
        info($slug);
        info($request->all());
        $settings = app('settings');
        $params = $request->all();

        $user = \Auth::user();
        $tables = \Auth::user()->tables;
        if($tables)
            $tables = json_decode($tables, true);
        else
            $tables = array();
        if(!isset($tables[$slug])) {
            $role = \App\Models\Role::findOrFail(\Auth::user()->role_id);
            if($role && $role->tables) {
                $role_tables = json_decode($role->tables, true);
                if(!is_array($role_tables))
                    $role_tables = array();
                if(isset($role_tables[$slug])) {
                    $tables[$slug] = $role_tables[$slug];
                    $user->tables = json_encode($tables);
                    $user->saveQuietly();
                }
            }

            if(!isset($tables[$slug])) {
                $settings_table = \DB::table('settings')->where('key', 'tables')->first();
                if($settings_table && $settings_table->value) {
                    $tables_all = json_decode($settings_table->value, true);
                    if(isset($tables_all[$slug])) {
                        $tables[$slug] = $tables_all[$slug];
                        $user->tables = json_encode($tables);
                        $user->saveQuietly();
                    }
                }
            }
        }
        if(isset($tables[$slug]) && !$request->fields) {
            if(!isset($settings['models'][$slug]) || !$settings['models'][$slug]->enable) {
                return response()->json([
                    'message' => 'Entity not found'
                ], 404);
            }
            $entity = $settings['models'][$slug];
            $entity_class = $entity->model_name;
            $model_fields = collect($settings[$slug]['fields']);//$entity_class::getFields();
            $table_columns = collect($tables[$slug]);
            $table_columns = $table_columns->keyBy('key')->toArray();
            foreach($table_columns as $key => $column) {
                if(!$model_fields->contains('field', $key) && $key != 'isChoose' && $key != 'actions' && $key != 'iconDrag' && $key != 'iconDelete') {
                    
                    unset($table_columns[$key]);
                }
                    
            }
            
        } else {
            if($slug == 'balance')
                $slug = 'balance_operations';
            $entity = $settings['models'][$slug];
            $entity_class = $entity->model_name;
            $model_fields = $settings[$slug]['fields'];//$entity_class::getFields();
            $table_columns = array();
            $i = 0;
            foreach ($model_fields as $field) {
                if(!array_key_exists($field->field, $table_columns) && $field->type != 'text_group'/* && $field->type != 'file'*/ && $field->type != 'password') {
                    if($request->fields && in_array($field->field, $request->fields)) {
                        $table_columns[$field->field] = array('title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title, 'key' => $field->field, 'sort' => array_search($field->field, $request->fields));
                    } elseif(!$request->fields) {
                        $table_columns[$field->field] = array('title' => $field->display_parent_name ? $field->display_parent_name.', '.$field->title : $field->title, 'key' => $field->field, 'sort' => $i);
                        $i++;
                    }
                }
            }
            array_sort_by_column($table_columns, 'sort', SORT_ASC, SORT_NATURAL);
        }

        $params['headings']['fields'] = array();
        foreach($table_columns as $column) {
            if(isset($column['title']) && $column['key'] != 'isChoose' && $column['key'] != 'actions' && $column['key'] != 'iconDrag' && $column['key'] != 'iconDelete') {
                $params['headings']['names'][] = $column['title'];
                $params['headings']['fields'][] = $column['key'];
            }
        }
        $now = strtotime(now());
        Excel::store(new ObjectExport($slug, $params), 'export'.$now.'.xlsx');
        
        if(tenant('id'))
            return response()->json(['link' => 'https://'.tenant('id').'.compas.pro/storage/tenant'.tenant('id').'/app/public/export'.$now.'.xlsx']);
        else
            return response()->json(['link' => 'https://compas.pro/storage/app/public/export'.$now.'.xlsx']);
    }

}