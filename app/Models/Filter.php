<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Filter extends Model
{
	protected $guarded = ['id'];

    public static function list($slug)
    {
        if(\Auth::user())
            $user_id = \Auth::user()->id;
        else
            $user_id = 1;
        $name = tenant('id').':filter-'.$slug.'-'.$user_id;
        $data = cache()->getMemcached()->get($name);
        //info('КЭЩ');
        $keys = cache()->getMemcached()->getAllKeys();
        //cache()->getMemcached()->add(string $key, mixed $value, int $expiration = 0): bool
        // info($keys);
        // info($name);
        // info($data);
        if(!$data) {
            //\Cache::tags([tenant('id')])->put($name, $john);
            // $data = cache()->rememberForever($name, function() use ($slug, $name)
            // {
                //info('сохраняем в кэш');
                $settings = get_settings();
                $entity = \DB::table('data_types')->where('slug', $slug)->first();
                $filters = \App\Models\Filter::where(['user_id' => $user_id, 'data_type' => $entity->id])->orderBy('sort', 'asc')->get();
                if(!$filters->count()) {
                    $filter = \App\Models\Filter::create([
                        'name' => 'Фильтр',
                        'user_id' => $user_id,
                        'data_type' => $entity->id,
                        'is_active' => 1,
                        'is_hidden' => 1,
                        'config' => json_encode(array('fields' => array(['key' => 'id','value' => ''])))
                    ]);
                    $filters->push($filter);
                };
                $fields_data = collect(\DB::table('data_rows')->where('data_type_id', $entity->id)->get())->keyBy('field')->toArray();
                
                $data = array();
                foreach($filters as $filter) {
                    $config = json_decode($filter->config, true);
                    $fields = array();
                    if($config) {
                        foreach($config['fields'] as $f => $value) {
                            if(!is_array($value))
                                continue;
                            $field_key = $value['key'];
                            if(isset($fields_data[$field_key])) {
                                $options = null;
                                if(isset($settings[$entity->slug]['list_values'][$field_key])) {
                                    $options = $settings[$entity->slug]['list_values'][$field_key];
                                    if($fields_data[$field_key]->type == 'relation') {
                                        $options = array_slice($settings[$entity->slug]['list_values'][$field_key], 0, 19, true);
                                        info('FSLUG '.$slug);
                                        info('FKEY '.$field_key);
                                        info('FVAL '.$value['value']);
                                        if($fields_data[$field_key]->is_plural && is_array($value)) {
                                            // info('VALUE');
                                            // info($value);
                                            if(is_array($value['value']))
                                                foreach($value['value'] as $field_val) {
                                                    $options[$field_val['value']] = $settings[$slug]['list_values'][$field_key][$field_val];
                                                }
                                        } elseif($value['value'] && isset($settings[$slug]['list_values'][$field_key])) {
                                            //info($settings[$slug]['list_values'][$field_key]);
                                            $options[$value['value']] = $settings[$slug]['list_values'][$field_key][$value['value']];
                                        }
                                    } else {
                                        if(isset($settings[$entity->slug]['options'][$field_key]))
                                            $options = $settings[$entity->slug]['options'][$field_key];
                                        else
                                            $options = $settings[$entity->slug]['list_values'][$field_key];
                                    }
                                }
                                
                                $fdata = array(
                                    'id' => $fields_data[$field_key]->id,
                                    'title' => $fields_data[$field_key]->display_name,
                                    'key' => $field_key,
                                    'type' =>  $fields_data[$field_key]->type,
                                    'options' => $options ? array_values($options) : array(),
                                    'value' =>  $value['value']
                                );
                                // if($fields_data[$field_key]->type == 'relation' && isset($settings[$slug]['list_values'][$field_key][$value]))
                                //     $fdata['value'] = $settings[$slug]['list_values'][$field_key][$value];
                                $fields[] = $fdata;
                            }
                        }
                        $data[] = array(
                            'id' => $filter->id,
                            'title' => $filter->name,
                            'sort' => $filter->sort,
                            'is_hidden' => $filter->is_hidden,
                            'fields' => $fields
                        );
                    }
                }
            cache()->getMemcached()->add($name, $data);
             //   return $data;
            //});
        }
        

        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fields()
    {
    	if($this->config) {
    		$config = json_decode($this->config, true);
    		$ids = array_keys($config['fields']);
    		$ids_str = "'".implode("','", $ids)."'";
    		$fields = \DB::table('data_rows')->whereIn('field', $ids)->where([/*'visible_always' => 1, */'data_type_id' => $this->data_type, 'is_remove' => 0])->orderByRaw("FIELD(field, $ids_str)");
	        
    	} else {
    		$fields = \DB::table('data_rows')->where(['visible_always' => 1, 'data_type_id' => $this->data_type, 'is_remove' => 0]);
    	}
    	$fields = $fields->get();

    	return $fields;
    }

    public function hidden_fields()
    {
    	if($this->config) {
    		$config = json_decode($this->config, true);
    		$ids = array_keys($config['fields']);
    		$ids_str = implode(',', $ids);
    		$hidden_fields = \DB::table('data_rows')->whereNotIn('field', $ids)->where(['data_type_id' => $this->data_type, 'is_remove' => 0])->get();
    	} 

    	return $hidden_fields;
    }

    public function add_field($field)
    {
    	$config = json_decode($this->config, true);
    	$config['fields'][$field] = '';
    	$this->config = json_encode($config, JSON_UNESCAPED_UNICODE);
    	$this->save();
    }
}

// $fields = array(
// 	'fields' => [
// 		'name' => value
// 		'address' => value
// 	]
// );