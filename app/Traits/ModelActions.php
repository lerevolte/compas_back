<?
namespace App\Traits;

use App\DataRow;
use Auth;
use Illuminate\Http\Request;
use App\Helpers\ValueHelper;

trait ModelActions
{
	public static function getTableName()
    {
        return with(new static)->getTable();
    }

	public static function getFields()
	{
        $table = self::getTableName();
        $cache_name = tenant('id').':'.$table.'-fields';
        $fields = cache()->getMemcached()->get($cache_name);
	    if(!$fields) {
            $settings = app('settings');
    	    $row_type = $settings['models'][$table];//\DB::table('data_types')->where('name', $table)->first();
    	    $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0])/*->where('type', '!=', 'text_group')*/->orderBy('sort')->get();
            cache()->getMemcached()->add($cache_name, $fields);
        }

	    return $fields;
	    
	}

    public static function getFieldsByModule($module)
    {
        $table = self::getTableName();
        $cache_name = tenant('id').':'.$table.'-fields-'.$module;
        //$fields = cache()->getMemcached()->get($cache_name);
        //if(!$fields) {
            $settings = app('settings');
            $row_type = $settings['models'][$table];//\DB::table('data_types')->where('name', $table)->first();
            $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0])->whereJsonContains('module', $module)->orderBy('sort')->get();
          //  cache()->getMemcached()->add($cache_name, $fields);
        //}
        // $fields = cache()->getMemcached()->get($cache_name);
        // if(!$fields) {
        //     $settings = app('settings');
        //     $row_type = $settings['models'][$table];//\DB::table('data_types')->where('name', $table)->first();
        //     $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0])->whereJsonContains('module', $module)->orderBy('sort')->get();
        //     cache()->getMemcached()->add($cache_name, $fields);
        // }


        // $table = self::getTableName();
        // $row_type = \DB::table('data_types')->where('name', $table)->first();
        // $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0])->whereJsonContains('module', $module)->orderBy('sort')->get();
        
        return $fields;
        
    }

	public static function getFieldsByRoute(string $route)
	{
	    $table = self::getTableName();

	    $row_type = \DB::table('data_types')->where('name', $table)->first();
	    $fields = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'is_remove' => 0])->orderBy('section_id')->orderBy('sort')->get();

	    $fields = $fields->filter(function ($item) use ($route) {
	    	if($item->mobile_pages) {
	    		$pages = json_decode($item->mobile_pages, true);

	    		return in_array($route, $pages);
	    	}

		    return false;
		});
	    
	    return $fields;
	    
	}

	public static function getListData($params)
	{
        $sort_field = isset($params['sort_field']) ? $params['sort_field'] : 'id';
        $sort_order = isset($params['sort_order']) ? $params['sort_order'] : 'desc';
        $settings = app('settings');
        $tenant = tenant('id');
        $user = \Auth::user();

        $entity = \DB::table('data_types')->where('slug', $params['slug'])->first();
        $entity_class = $entity->model_name;
        $model_fields = $settings[$params['slug']]['fields'];

        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if($field->type == 'status')
                $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            // $perms['read'][$field->field] = (!optional(request()->user())->canRead($field->field, $params['slug']) ? 'disabled':'');
            // $perms['write'][$field->field] = (!optional(request()->user())->canWrite($field->field, $params['slug']) ? 'disabled':'');
        }

        $paginator = $entity_class::orderBy($sort_field, $sort_order);
        if(isset($params['filter']) && is_array($params['filter'])){
            foreach($params['filter'] as $field => $val) {
                if($field == 'created_at' || $field == 'updated_at')
                    $paginator = $paginator->whereDate($field, $val);
                else
                    $paginator = $paginator->where($field, $val);
            }
        }
        if(isset($params['order_id']) && $params['slug'] == 'products') {
            $order = \App\Models\Order::find($params['order_id']);
            if($order) {
                $products = json_decode($order->products, true);
                $product_ids = array();
                $fix_order = false;
                if(is_array($products)) {
                    foreach($products as $product_k => $product) {
                        if(!isset($product['id'])) {
                            $prod = \Modules\Products\Entities\Product::where('name', $product['name'])->first();
                            if($prod) {
                                $fix_order = true;
                                $product_ids[] = $prod->id;
                                $products[$product_k]['id'] = $prod->id;
                            }
                            
                        } else {
                            $product_ids[] = $product['id'];
                        }
                    }
                    if($fix_order) {
                        $order->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                        $order->saveQuietly();
                    }
                    $paginator = $paginator->whereIn('id', $product_ids);
                } else {
                    return response()->json([]);
                }
            }
        };
        if(isset($params['ids'])) {
        	$paginator = $paginator->whereIntegerInRaw('id', $params['ids']);
        };
        if(isset($params['q'])) {
            $search_columns = $model_fields->filter(function ($field) {
                                return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
                            })->pluck('field')->toArray();
            $q = $params['q'];
            $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
                foreach ($search_columns as $column) {
                    $query->orWhere($column, 'like', "%{$q}%");
                }
            });
        };
        $paginator = $paginator->get();
        
        $objects = array();
        $field_values = array();
        foreach ($paginator as $item) {

            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if($field->field == 'password')
                    continue;
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group') {
                    $value = $item->{$field->field};
                    $data[$field->field] = array(
                        'value' => is_array($value) && ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value,
                        'type' => $field->type,
                        'read_only' => $field->only_read,
                        'can_edit' => $field->only_read ? 0 : 1,//!$settings[$params['slug']]['perms'][$field->field]['write'] ? 1 : 0,
                        'color' => $field_colors[$field->field],
                        'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural)
                    );
                    if(isset($settings['list_values'][$field->id])) {
                        $data[$field->field]['options'] = $settings['list_values'][$field->id];
                    };
                    if($field->type == 'relation') {
                        $data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                    }
                }
            }
            if(isset($order) && $order && $params['slug'] == 'products') {
                $products = json_decode($order->products, true);
                if(is_array($products)) {
                    foreach($products as $num => $product) {
                        if($product['id'] == $item->id) {
                            $data['product_name'] = array(
                                'value' => isset($product['product_name']) ? $product['product_name'] : $product['name'],
                                'type' => 'text',
                                'read_only' => 0,
                                'can_edit' => 1,
                                'color' => '',
                                'sort' => 0,
                            );
                            $data['product_price'] = array(
                                'value' => $product['price'],
                                'type' => 'number',
                                'read_only' => 0,
                                'can_edit' => 1,
                                'color' => '',
                                'sort' => 1,
                            );
                            $data['product_count'] = array(
                                'value' => $product['count'],
                                'type' => 'number',
                                'read_only' => 0,
                                'can_edit' => 1,
                                'color' => '',
                                'sort' => 2,
                            );
                            $data['product_weight'] = array(
                                'value' => $product['weight'],
                                'type' => 'number',
                                'read_only' => 0,
                                'can_edit' => 1,
                                'color' => '',
                                'sort' => 3,
                            );
                            $data['product_sum'] = array(
                                'value' => $product['sum'],
                                'type' => 'number',
                                'read_only' => 1,
                                'can_edit' => 0,
                                'color' => '',
                                'sort' => 4,
                            );
                            $data['sort'] = $num;
                        }
                    }
                }
                
            }
            

            $objects[] = $data;
        }

        return $objects;
	}

	public function getData($changed_fields = [], $settings = [])
	{
		$slug = self::getTableName();
        if(!$settings)
            $settings = app('settings');
        $tenant = tenant('id');
        $user = \Auth::user();
        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        info('tenant '.$tenant);
        info($slug);
        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];

        $users = \App\Models\User::get(['id', 'name', 'last_name'])->keyBy('id')->toArray();
        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );

        foreach($model_fields as $field) {
            if(in_array($field->id, $changed_fields) || !count($changed_fields)) {
                if($field->type == 'status')
                    $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
                // $perms['read'][$field->field] = (!optional(request()->user())->canRead($field->field, $slug) ? 'disabled':'');
                // $perms['write'][$field->field] = (!optional(request()->user())->canWrite($field->field, $slug) ? 'disabled':'');
            }
        }

        $objects = array();
        $field_values = array();
        $data = array(
            'id' => $this->id
        );
        $fields_data = array();
        $fields_values = array('id' => $this->id);
        foreach($model_fields as $field) {
            if($field->field == 'password')
                continue;
            $value = '';
            if(!array_key_exists($field->field, $fields_data) && $field->type != 'text_group' && (in_array($field->id, $changed_fields) || !count($changed_fields))) {
            //if(!array_key_exists($field->field, $fields_data) && $field->type != 'text_group') {
                $value = $this->{$field->field};
                $list_values = array();
                if(isset($settings['list_values'][$field->id]))
                    $list_values = $settings['list_values'][$field->id];
                $field_value = !is_array($value) && ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;
                if($field->field == 'products') {
                    $field_value = $this->getHtmlProducts();
                }

                if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                    $relation_table = $field->relation_table;
                    $relation = method_exists($this, $relation_table) ? $this->{$relation_table} : null;
                    $field_value = $relation ? $relation->pluck('id')->toArray() : [];
                }
                if($this->getTable() == 'routes' && $field->field == 'task_id' && $field->type == 'relation' && method_exists($this, 'tasks')) {
                    $field_value = $this->tasks()->get()->pluck('id')->toArray();
                }
                if($field->type == 'relation' && \App\Models\Settings::lazy_table($settings, $field->id)) {
                    $list_values += \App\Models\Settings::resolve_list_values($settings, $field->id, $field_value);
                }
                $fields_values[$field->field] = $field_value;

                
                if($field->type == 'date')
                    $fields_values[$field->field] = \Carbon\Carbon::parse($field_value)->format('Y-m-d H:i:s');
                $fields_data[$field->field] = array(
                    'id' => $field->id,
                    'name' => $field->field,
                    'value' => $field_value,
                    'type' => $field->type,
                    'read_only' => $field->only_read,
                    'can_edit' => $field->only_read ? 0 : 1,//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                    'color' => $field_colors[$field->field],
                    'is_plural' => ($field->type == 'text' ? 1 : $field->is_plural),
                    'unit' => $field->unit
                );
                if($field->type == 'relation' && $field->is_plural) {

                    $values = $field_value;
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    if(is_array($values)) {
                        foreach($values as $val) {
                            if(isset($list_values[$val])) {
                                $fields_data[$field->field]['value']['value'][] = $list_values[$val]['value'];
                                $fields_data[$field->field]['value']['localOptions'][] = $list_values[$val];
                            }
                        }
                    }
                } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    $fields_data[$field->field]['value']['localOptions'] = array($list_values[$field_value]);
                    $fields_data[$field->field]['value']['value'] = array($list_values[$field_value]['value']);
                    
                } elseif($field->type == 'relation') {
                    $fields_data[$field->field] = array(
                        'value' => array(
                            'value' => null,
                            'localOptions' => null
                        )
                    );
                }
                if($field->type == 'relation') {
                    $fields_values[$field->field] = $fields_data[$field->field]['value'];
                }
                

                if(isset($settings['list_values'][$field->id])) {

                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                        if($field->is_plural && is_array($fields_data[$field->field]['value'])) {
                            foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                if(isset($list_values[$field_val]))
                                    $field_values[$field_val] = $list_values[$field_val];
                            }
                        } elseif($this->{$field->field} && isset($list_values[$this->{$field->field}])) {
                            $field_values[$this->{$field->field}] = $list_values[$this->{$field->field}];
                        } elseif($this->{$field->field}) {
                            $field_values[$this->{$field->field}] = null;
                        }
                    } else {
                        $field_values = $settings['list_values'][$field->id];
                    }
                    $fields_data[$field->field]['options'] = array_values($field_values);
                    $fields_data[$field->field]['options_key_value'] = $field_values;

                };
                if($field->type == 'relation') {
                    $fields_data[$field->field]['related_table'] = json_decode($field->details, true)['table'];
                }

            }
            
        }
        
        $viewList = $fields_values;
        $data['fields'] = $fields_data;

        //viewDetail
        $data = array(
            'id' => $this->id,
            'title' => isset($this->store_name) ? $this->store_name : $this->name,
            'fields' => array()
        );
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value'])) {
                $data['title'] = $data['title']['value'];
            } else {
                $data['title'] = null;
            }
        } else {
            $data['title'] = $data['title'];
        }

        
        foreach($model_fields as $field) {
            $fields_data = array();
            if($field->field == 'password')
                continue;
            if(!array_key_exists($field->field, $fields_data) && (in_array($field->id, $changed_fields) || !count($changed_fields))) {
            //if(!array_key_exists($field->field, $fields_data)) {
                $created_data[$field->field] = $this->{$field->field};
                if(/*!$field->group_id && */isset($settings[$slug]['field_data'][$field->field])) {
                    $fields_data = $settings[$slug]['field_data'][$field->field];
                    $field_value = !is_array($this->{$field->field}) && ValueHelper::isJson($this->{$field->field}) && $field->field != 'products' && is_array(json_decode($this->{$field->field}, true)) ? json_decode($this->{$field->field}, true) : $this->{$field->field};
                    if($field->field == 'products') {
                        $field_value = $this->getHtmlProducts();
                    }
                    if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                        $relation_table = $field->relation_table;
                        $relation = method_exists($this, $relation_table) ? $this->{$relation_table} : null;
                        $field_value = $relation ? $relation->pluck('id')->toArray() : [];
                    }
                    $fields_data['can_edit'] = $field->only_read ? 0 : 1;//isset($settings[$slug]['perms'][$field->field]['write']) && !$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
                    $fields_data['value'] = $field_value;
                    $fields_data['used_in_modules'] = $settings[$slug]['used_in_modules'][$field->field];
                    $fields_data['comparison'] = $comparisons[$field->id] ?? null;
                    $fields_data['editableFields']['model'] = $slug;
                    $fields_data['choosed'] = [];
                    if(isset($settings[$slug]['fields'][$field->field]->choosed))
                        $fields_data['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
                    $value = $fields_data['value'];


                    $list_values = array();
                    if(isset($settings['list_values'][$field->id]))
                        $list_values = $settings['list_values'][$field->id];
                    if($field->type == 'relation' && \App\Models\Settings::lazy_table($settings, $field->id)) {
                        $list_values += \App\Models\Settings::resolve_list_values($settings, $field->id, $field_value);
                    }
                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $field_value;
                        $fields_data['value'] = array(
                            'value' => array(),
                            'localOptions' => array()
                        );
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($list_values[$val])) {
                                    $fields_data['value']['value'][] = $list_values[$val]['value'];
                                    $fields_data['value']['localOptions'][] = $list_values[$val];
                                }
                            }
                        }
                    } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
                        $fields_data['value'] = array(
                            'value' => array(),
                            'localOptions' => array()
                        );
                        $fields_data['value']['localOptions'] = array($list_values[$field_value]);
                        $fields_data['value']['value'] = array($list_values[$field_value]['value']);
                        
                    } elseif($field->type == 'relation') {
                        $fields_data['value'] = array(
                            'value' => array(),
                            'localOptions' => array()
                        );
                    }
                    if(isset($settings['list_values'][$field->id])) {
                        $values = array();
                        if($field->type == 'relation') {
                            $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                            if($field->is_plural && is_array($fields_data['value'])) {
                                foreach($fields_data['value']['value'] as $field_val) {
                                    if(isset($list_values[$field_val]))
                                        $field_values[$field_val] = $list_values[$field_val];
                                }
                            } elseif($this->{$field->field} && isset($list_values[$this->{$field->field}])) {
                                $field_values[$this->{$field->field}] = $list_values[$this->{$field->field}];
                            } elseif($this->{$field->field}) {
                                $field_values[$this->{$field->field}] = null;
                            }
                        } else {
                            $field_values = $settings['list_values'][$field->id];
                        }
                        $fields_data['options'] = array_values($field_values);//$field_values;
                        $fields_data['options_key_value'] = $field_values;
                        if($field->type == 'status') {
                            $simple_options = array();
                            foreach($settings['list_values'][$field->id] as $option) {
                                $simple_options[$option['value']] = $option['label']['text'];
                                $values[] = array(
                                    'value' => $option['value'],
                                    'label' => $option['label']['text'],
                                    'sort' => $option['label']['sort']
                                );
                            }
                            $fields_data['options_key_value'] = $simple_options;
                        } else {
                            foreach($settings['list_values'][$field->id] as $k => $option) {
                                $simple_options[$k] = $option;
                                $values[] = $option;
                            }
                        }
                        $fields_data['editableFields']['values'] = $values;
                        if($field->type == 'status')
                            $fields_data['editableFields']['statuses'] = $settings['list_values'][$field->id];
                    };
                    if($field->type == 'relation' && $t = json_decode($field->details, true)) {
                        if(isset($t['table']))
                            $fields_data['related_table'] = $t['table'];
                    }
                    if($field->type == 'text_group') {
                        $subfields = \App\Models\Field::getByGroup($field->id);
                        $values = array();
                        $fields_data['options'] = array();
                        $fields_data['options_key_value'] = array();
                        foreach($subfields as $sort => $subfield) {
                            $fields_data['options'][$subfield->id] = $subfield->title;
                            $fields_data['options_key_value'][$subfield->id] = $subfield->title;
                            $values[] = array(
                                'value' => $subfield->id,
                                'label' => $subfield->title,
                                'sort' => $sort
                            );
                        };
                        $fields_data['editableFields']['options'] = $values;
                        $fields_data['subfields'] = array();
                        foreach($subfields as $subfield) {
                            $fields_data['subfields'][] = array(
                                'id' => $subfield->id,
                                'title' => $subfield->title,
                                'key' => $subfield->field,
                                'type' => $subfield->type,
                                'visible_always' => $field->visible_always,
                                'is_hidden' => $field->hide,
                                'is_plural' => $field->is_plural,
                                'required' => $field->required,
                                'read_only' => $field->only_read,
                                'can_edit' => $field->only_read ? 0 : 1,//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0,
                                'color' => $field_colors[$field->field],
                                'group_id' => $subfield->group_id,
                                'value' => $this->{$subfield->field},
                                'sort' => $subfield->sort,
                                'mask' => $field->mask,
                            );
                        }
                    }

                    $data['fields'][] = array(
                        'field' => $fields_data,
                        'section_id' => $field->section_id
                    );
                }
            }
        }

    
        $viewDetail = $data;
        $avatar = isset($this->avatar) ? $this->avatar : (isset($this->photo) ? $this->photo : '');
        
        if(isset($this->store_name)) {
            if(is_array($this->store_name)) {
                $name = $this->store_name['value'];
            } else {
                $name = $this->store_name;
            }
            $list_val = array(
                'value' => $this->id,
                'label' => $name,
                'color' => isset($this->color) ? $this->color : '',
                'file' => $avatar
            );
        } elseif(isset($this->title)) {
            $list_val = array(
                'value' => $this->id,
                'label' => $this->title,
                'color' => isset($this->color) ? $this->color : '',
                'file' => $avatar
            );
        } elseif(isset($this->first_name)) {
            if(is_array($this->first_name)) {
                $name = $this->first_name['value'];
            } else {
                $name = $this->first_name;
            }
            $list_val = array(
                'value' => $this->id,
                'label' => $name.' '.$this->last_name,
                'color' => isset($this->color) ? $this->color : '',
                'file' => $avatar
            );
        } elseif(isset($this->name)) {
            if(is_array($this->name)) {
                $name = $this->name['value'];
            } else {
                $name = $this->name;
            }
            $list_val = array(
                'value' => $this->id,
                'label' => $name.(isset($this->last_name) ? ' '.$this->last_name:''),
                'color' => isset($this->color) && !$this->color ? $this->getColor() : ($this->color ?? ''),
                'file' => $avatar
            );
        }
        if(isset($created_data))
            $data['created'] = $created_data;
        $data/*['updated']*/ = array(
            'changed_by' => \Auth::user() ? \Auth::user()->id : 1,
            'user_id' => $this->user_id,
            'slug' => $slug,
            // 'option' => array(
            //     'label' => $list_val,
            //     'value' => $this->id
            // ),
            'viewDetail' => $viewDetail,
            'viewList' => $viewList
        );


        return $data;
	}

    public function saveRelations($field_name, $value)
    {
        $settings = app('settings');
        $slug = self::getTableName();
        if(isset($settings['list_values'][$field_name])) {

            $field = $settings[$slug]['fields'][$field_name];
            $list_values = $settings['list_values'][$field_name];
            foreach($list_values as $k => $item) {
                if(isset($item['label']))
                    $list_values[$item['value']] = $item['label'];
            }
            $old_value = \App\Models\Field::getHumanValue($field, $this->{$field_name});
            $new_value = \App\Models\Field::getHumanValue($field, $value);

            $history_text = '';
            if($old_value != $new_value)
                $history_text = $field->title.': '.$old_value.' -> '.$new_value;
 
            if($history_text) {
                $history_data = array(
                    'entity' => $slug, 
                    'entity_id' => $this->id, 
                    'user_id' => \Auth::user()->id, 
                    'text' => $history_text,
                    'field' => $field->field,
                    'old_value' => $old_value,
                    'new_value' => $new_value
                );
                if($field->type == 'relation')
                    $history_data['event'] = 'RELATION_CHANGED';
                $history = new \App\Models\History($history_data);
                $history->save();
                
            }
            $related_table = $field->relation_table;
            if($related_table && isset($settings['models'][$related_table])) {
                if(is_array($value) && count($value)) {
                    $new_values = $value;
                    if(is_array($arr)) 
                        $new_values = array_diff($value, $arr);
                    if(count($new_values))
                        \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                } elseif($value) {
                    \DB::table($related_table)->where('id', $value)->update(['choosed_at' => \DB::raw('now()')]);
                }
            }
        }
    }

    public function saveHistory($row)
    {
        $settings = app('settings');
        $slug = self::getTableName();
        $object = $this->toArray();
        $model_fields = $settings[$slug]['fields'];
        $keys = array();

        $changed_fields = array();
        $history_items = array();
        foreach($row as $field_name => $value) {
            if(!$value && $field_name == 'name') {
                continue;
            }
            if(!$value) {
                $row[$field_name] = null;
            } elseif(is_array($value)) {
                $row[$field_name] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                foreach($model_fields as $field) {
                    
                    if($field->field == $field_name && $field->type == 'date')
                        $row[$field_name] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');//$value = strtotime($value);
                    if($field->field == $field_name && $field->type == 'text')
                        $row[$field_name] = (string)$value;
                }
            }
        }
        //print_r($row);
        $keys = array_keys($row);
        
        
        foreach($model_fields as $field) {
            if($field->field == 'id' || $field->field == 'password')
                continue;

            if(array_key_exists($field->field, $row) && (array_key_exists($field->field, $object) && $object[$field->field] !== $row[$field->field] || !array_key_exists($field->field, $object))) {
                //echo $field->field.'<br>';
                $changed_fields[] = $field->id;
                if($field->type == 'status' && isset($settings['list_values'][$field->id])) {
                    $statuses = collect($settings['list_values'][$field->id]);
                    $visible_statuses = $statuses->filter(function ($status) {
                            return !$status['label']['is_hidden'];
                        })->pluck('id')->toArray();
                    $old_status = null;
                    if(isset($object[$field->field]))
                        $old_status = $statuses->firstWhere('id', $object[$field->field]);
                    $new_status = $statuses->firstWhere('id', $row[$field->field]);
                    
                    if($old_status && $new_status) {
                        if($old_status->is_hidden) {
                            $old_value = $old_status->color;
                        } else {
                            $old_value = $old_status->value ? $old_status->value : '';//'Значение '.(array_search($old_status->id, $visible_statuses)+1);
                        }
                        if($new_status->is_hidden) {
                            $new_value = $new_status->color;
                        } else {
                            $new_value = $new_status->value ? $new_status->value : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                        }
                        $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                    } elseif($new_status) {
                        if($new_status->is_hidden) {
                            $new_value = $new_status->color;
                        } else {
                            $new_value = $new_status->value ? $new_status->value : '';//'Значение '.(array_search($new_status->id, $visible_statuses)+1);
                        }
                        $history_text = $field->title.': '.(isset($object[$field->field]) ? $object[$field->field]:'').' -> '.$new_value;
                    } else {
                        $history_text = $field->title.': '.(isset($object[$field->field]) ? $object[$field->field]:'').' -> '.$row[$field->field];
                    }
                } elseif(isset($settings['list_values'][$field->id])) {
                    $list_values = $settings['list_values'][$field->id];
                    foreach($list_values as $k => $item) {
                        if(isset($item['label']))
                            $list_values[$item['value']] = $item['label'];
                    }
                    if($field->is_plural) {
                        $old_list = $new_list = array();
                        $arr = array();
                        if(isset($object[$field->field]))
                            $arr = json_decode($object[$field->field], true);
                        if(is_array($arr)) {
                            $old_list = $arr;
                        } elseif(is_integer($arr)) {
                            $old_list[] = $arr;
                        }
                        foreach($old_list as $k => $val) {
                            if(isset($list_values[$val]))
                                $old_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                            else
                                unset($old_list[$k]);
                        }
                        
                        if(is_array($row[$field->field])) {
                            $new_list = $row[$field->field];
                        } elseif(is_integer($row[$field->field])) {
                            $new_list[] = $row[$field->field];
                        }
                        foreach($new_list as $k => $val) {
                            if(isset($list_values[$val]))
                                $new_list[$k] = isset($list_values[$val]['text']) ? $list_values[$val]['text'] : $list_values[$val];
                            else
                                unset($new_list[$k]);
                        }

                        $old_value = implode(', ', $old_list);
                        $new_value = implode(', ', $new_list);
                        if($old_value || $new_value)
                            $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                    } else {
                        $old_value = isset($object[$field->field]) && isset($list_values[$object[$field->field]]) ? $list_values[$object[$field->field]] : '';
                        $new_value = isset($list_values[$row[$field->field]]) ? $list_values[$row[$field->field]] : '';
                        if($old_value || $new_value)
                            $history_text = $field->title.': '.(is_array($old_value) ? $old_value['text'] : $old_value).' -> '.(is_array($new_value) ? $new_value['text'] : $new_value);
                    }
                    if($field->type == 'relation') {
                        $related_table = json_decode($field->details, true)['table'];
                        if($related_table && isset($settings['models'][$related_table])) {
                            if(is_array($row[$field->field]) && count($row[$field->field])) {
                                $new_values = $row[$field->field];
                                if(is_array($arr)) 
                                    $new_values = array_diff($row[$field->field], $arr);
                                if(count($new_values))
                                    \DB::table($related_table)->whereIntegerInRaw('id', $new_values)->update(['choosed_at' => \DB::raw('now()')]);
                            } elseif($row[$field->field]) {
                                \DB::table($related_table)->where('id', $row[$field->field])->update(['choosed_at' => \DB::raw('now()')]);
                            }
                        }
                         
                    }
                } else {
                    if(is_array($row[$field->field])) {
                        if($field->type == 'address') {
                            $old_addr = json_decode($object[$field->field], true);
                            $history_text = $field->title.': '.(isset($old_addr['text']) ? $old_addr['text'] : '').' -> '.$row[$field->field]['text'];
                        } elseif($field->type == 'file') {
                            $file_values = array();
                            foreach($row[$field->field] as $v) {
                                if(isset($v['name']))
                                    $file_values[] = $v['name'];
                            }
                            $old_values = array();
                            if($object[$field->field]) {
                                $old_files = json_decode($object[$field->field], true);
                                foreach($old_files as $v) {
                                    if(isset($v['name']))
                                        $old_values[] = $v['name'];
                                }
                            }
                            if($old_value != implode(', ', $file_values))
                                $history_text = $field->title.': '.implode(', ', $old_values).' -> '.implode(', ', $file_values);
                        } else {
                            $old_value = null;
                            if(isset($object[$field->field]))
                                $old_value = $object[$field->field];
                            if($field->is_external_link) {
                                $arr_value = json_decode($object[$field->field], true);
                                if($arr_value && isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link'] && is_array($arr_value)) {
                                    $old_value = implode(', ', array_values($arr_value));
                                }
                                elseif(is_array($arr_value) && isset($arr_value['value']))
                                    $old_value = $arr_value['value'];
                            }
                            $new_value = array_filter($row[$field->field], fn($value) => !is_null($value) && $value !== '');
                            if($old_value != implode(', ', array_values($new_value)))
                                $history_text = $field->title.': '.$old_value.' -> '.implode(', ', array_values($new_value));
                        }
                    } else {
                        $old_value = null;
                        $new_value = $row[$field->field];
                        if(isset($object[$field->field]))
                            $old_value = $object[$field->field];
                        if($field->type == 'file') {
                            $old_values = array();
                            if(isset($object[$field->field])) {
                                $old_files = json_decode($object[$field->field], true);
                                foreach($old_files as $v) {
                                    if(isset($v['name']))
                                        $old_values[] = $v['name'];
                                }
                            }
                            $old_value = implode(', ', $old_values);
                        }

                        if($field->is_external_link) {
                            $arr_value = json_decode($object[$field->field], true);
                            if(isset($arr_value['value']) && isset($arr_value['external_link']) && $arr_value['external_link']) {
                                $old_value = implode(', ', array_values($arr_value));
                            }
                        }
                        if($field->type == 'date') {
                            $old_value = date('d.m.Y', strtotime($old_value));
                            $new_value = date('d.m.Y', strtotime($new_value));
                        }
                        if($old_value != $new_value)
                            $history_text = $field->title.': '.$old_value.' -> '.$new_value;
                    }
                    
                }
                if(isset($history_text) && $history_text) {
                    $module = $field->module ? $field->module : null;
                    if(isset(\Auth::user()->id))
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $this->id, 'user_id' => \Auth::user()->id, 'text' => $history_text, 'module' => $module]);
                    else
                        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $this->id, 'user_id' => 1, 'text' => $history_text, 'module' => $module]);
                    $history->save();
                    if($module) {
                        $history_replic = $history->replicate();
                        $history_replic->module = null;
                        $history_replic->save();
                    }
                    $history_items[] = $history;
                    
                }
            }
        }
    }

}