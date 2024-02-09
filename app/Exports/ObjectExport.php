<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Helpers\ValueHelper;

class ObjectExport implements FromCollection, WithHeadings, WithMapping
{
    protected $slug, $params;

    public function __construct(string $slug, array $params)
    {
        $this->slug = $slug;
        $this->params = $params;
    }

    public function headings(): array
    {
        return array_values($this->params['headings']['names']);
    }

    public function map($item): array
    {
        $data = array();
        foreach($this->params['headings']['fields'] as $field) {
            if(isset($item[$field])) {
                $data[] = $item[$field];
            } else {
                $data[] = '-';
            }
        }
        return $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        if(!isset($this->params['filter']) && isset($this->params['is_slug'])) {
            return response()->json([]);
        }
        $slug = $this->slug;

        $sort_field = isset($this->params['sort_field']) ? $this->params['sort_field'] : 'id';
        $sort_order = isset($this->params['sort_order']) ? $this->params['sort_order'] : 'desc';

        $settings = get_settings();
        $tenant = tenant('id');

        $entity = \DB::table('data_types')->where('slug', $slug)->first();
        if(!$entity || !$entity->enable) {
            return response()->json(
                array(
                    'code' => 404,
                    'error' => 'Сущность не найдена',
                )
            );
        }
        $entity_class = $entity->model_name;
        $model_fields = $entity_class::getFields();

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );


        if($sort_order == 'asc')
            $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field");
        else
            $paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field desc");
        if($sort_field == 'id')
            $paginator = $entity_class::orderByRaw("CAST($sort_field AS DECIMAL) $sort_order");
        

        if(isset($this->params['trashed'])) {
            $paginator = $paginator->onlyTrashed();
        }
        if(isset($this->params['filter']) && is_array($this->params['filter'])){

            foreach($this->params['filter'] as $field => $val) {
                if($field == 'created_at' || $field == 'updated_at')
                    $paginator = $paginator->whereDate($field, $val);
                elseif($settings[$slug]['fields'][$field]->type == 'date')
                    $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
                else {
                    if(isset($settings[$slug]['fields'][$field]) && $settings[$slug]['fields'][$field]->is_plural) {
                        if($settings[$slug]['fields'][$field]->type == 'relation') {
                            $paginator = $paginator->whereJsonContains($field, (int)$val);
                        }
                        else {
                            $paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
                        }
                    } else {
                        if($field == 'category_id' && !isset($this->params['exclude_childs'])) {
                            foreach($model_fields as $f) {
                                if($f->field == $field) {
                                    $related_table = json_decode($f->details, true)['table'];
                                    $dt = \DB::table('data_types')->where('name', $related_table)->first();
                                    $descendants = $dt->model_name::descendantsAndSelf($val)->pluck('id')->toArray();
                                }
                            }
                            if(isset($descendants) && is_array($descendants)) {
                                $paginator = $paginator->whereIntegerInRaw($field, $descendants);
                            }
                            
                        } else {
                            if(is_array($val))
                                $paginator = $paginator->whereIntegerInRaw($field, $val);
                            else {
                                if($settings[$slug]['fields'][$field]->type == 'text')
                                    $paginator = $paginator->where($field, 'like', "%{$val}%");
                                else
                                    $paginator = $paginator->where($field, $val);
                                
                            }
                        }
                        
                    }
                    
                }
            }
        }
        if(isset($this->params['order_id']) && $slug == 'products') {
            $order = \App\Models\Task::find($this->params['order_id']);
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
                    $paginator = $paginator->whereIntegerInRaw('id', $product_ids);
                } else {
                    return response()->json([]);
                }
            }
        };

        if(isset($this->params['q'])) {
            $q = $this->params['q'];
            $search_columns = $model_fields->filter(function ($field) {
                return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
            })->pluck('field')->toArray();
            
            $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
                foreach ($search_columns as $column) {
                    $query->orWhere($column, 'like', "%{$q}%");
                }
            });
            foreach($model_fields as $field) {
                if($field->type == 'relation') {
                    $relations = collect($settings[$slug]['list_values'][$field->field])->filter(function ($item) use ($q) {
                        return mb_stristr($item['label']['text'], $q);
                    })->pluck('value')->toArray();
                    if(count($relations) && $field->is_plural) {
                        $paginator = $paginator->orWhere(function ($query) use ($field, $relations) {
                           foreach ($relations as $id) {
                               $query->orWhereJsonContains($field->field, $id);
                           }
                        });
                    } elseif(count($relations)) {
                        $paginator = $paginator->orWhere(function ($query) use ($field, $relations) {
                           foreach ($relations as $id) {
                               $query->orWhere($field->field, $id);
                           }
                        });
                    }
                }
            }
        };

        $items = $paginator->get();
        
        $objects = array();
        $field_values = array();
        
        foreach ($items as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password') {
                    $value = $item->{$field->field};
                    $data[$field->field] = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;

                    if($value && ($field->field == 'created_at' || $field->field == 'updated_at')) {
                        $data[$field->field] = $value->format('d.m.Y');
                    }
                    if($field->type != 'relation' && !$field->is_plural && is_array($data[$field->field]) && isset($data[$field->field]['value'])) {
                        $data[$field->field] = $data[$field->field]['value'];
                    }
                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $data[$field->field];
                        $data[$field->field] = array();
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($settings[$slug]['list_values'][$field->field][$val]))
                                    $data[$field->field][] = $settings[$slug]['list_values'][$field->field][$val]['label']['text'];
                            }
                        }
                        $data[$field->field] = implode(', ', $data[$field->field]);
                    } elseif($field->type == 'relation' && isset($settings[$slug]['list_values'][$field->field][$value])) {
                        $data[$field->field] = $settings[$slug]['list_values'][$field->field][$value]['label']['text'];
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = null;
                    };

                    if($field->type == 'select_dropdown' && $field->is_plural && isset($settings[$slug]['list_values'][$field->field])) {
                        if($item->{$field->field})
                            $value = json_decode($item->{$field->field}, true);
                        if(is_array($value)) {
                            $data[$field->field] = array();
                            foreach($value as $val) {
                                if(isset($settings[$slug]['list_values'][$field->field][$val]))
                                    $data[$field->field][] = $settings[$slug]['list_values'][$field->field][$val]['label'];
                            }
                            $data[$field->field] = implode(',', $data[$field->field]);
                        }

                    } elseif($field->type == 'select_dropdown' && !$field->is_plural && isset($settings[$slug]['list_values'][$field->field])) {
                        if(isset($settings[$slug]['list_values'][$field->field][$value])) {
                            $data[$field->field] = $settings[$slug]['list_values'][$field->field][$value]['label'];
                        }
                    } elseif(ValueHelper::isJson($value)) {
                        $value = json_decode($value, true);
                        if($field->type == 'address') {
                            $data[$field->field] = $value['text'];
                        }
                        if(isset($value['external_link'])) {
                            $data[$field->field] = $value['value'];
                        }
                        if($field->field == 'products') {
                            $data[$field->field] = array();
                            foreach($value as $val) {
                                $data[$field->field][] = $val['name'].($val['weight'] ? ' '.$val['weight'].' кг.':'').': '.$val['price'].' руб. x '.$val['count'].' шт. - '.$val['sum'];
                            }
                            $data[$field->field] = implode(';', $data[$field->field]);
                            // [{"id":12,"name":"Стеклошарики фр. 100-200 мкр, 25кг","price":"201","count":"12","weight":1,"sum":2412},{"id":85,"name":"Стеклошарики «Lux» фр. 106-600 мкр, 25кг","price":"20","count":"32","weight":1,"sum":640}]
                        }
                    }
                    if($field->type == 'file') {
                        $data[$field->field] = '-';
                    }
                    if($field->unit) {
                        $data[$field->field] = $data[$field->field].' '.$field->unit;
                    }
                }
            }
            if(isset($order) && $order && $slug == 'products') {
                $products = json_decode($order->products, true);
                if(is_array($products)) {
                    foreach($products as $num => $product) {
                        if($product['id'] == $item->id) {
                            $data['product_name'] = isset($product['product_name']) ? $product['product_name'] : $product['name'];
                            $data['product_price'] = $product['price'];
                            $data['product_count'] = $product['count'];
                            $data['product_weight'] = $product['weight'];
                            $data['product_sum'] = $product['sum'];
                            $data['sort'] = $num;
                        }
                    }
                }
                
            }
            
            $objects[] = $data;
        }
        // if($sort_order == 'asc')
        //     array_sort_by_column($objects, $sort_field, SORT_ASC, SORT_NATURAL);
        // else
        //     array_sort_by_column($objects, $sort_field, SORT_DESC, SORT_NATURAL);
        // info('SORT2');
        // info($sort_order.' '.$sort_field);
        
        return collect($objects);
    }
}
