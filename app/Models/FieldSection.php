<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;


class FieldSection extends Model
{
    use NodeTrait;

	protected $guarded = ['id'];
	protected $fillable = ['name', 'page', 'column_id', 'sort'];
    protected $hidden = [
        '_lft',
        '_rgt'
    ];

    public function fields() {
        return $this->hasMany(Field::class, 'section_id')->where(['hide' => 0, 'is_remove' => 0])->whereNull('group_id')/*->where('hide', 0)*/->orderBy('sort');
    }

    public function module_fields() {
        return \DB::table('data_rows')->whereJsonContains('module_section_id', $this->id)->where(['hide' => 0, 'is_remove' => 0])->orderBy('sort')->get();
        //return $this->hasMany(Field::class, 'module_section_id')->where(['hide' => 0, 'is_remove' => 0])->whereNull('group_id')/*->where('hide', 0)*/->orderBy('sort');
    }
    //->whereJsonContains($field, (int)$val);

    public function all_fields() {
        return $this->hasMany(Field::class, 'section_id')->where(['hide' => 0])->where(['is_remove' => 0])->whereNull('group_id')->orderBy('sort');
    }

    public function visible_fields() {
        return $this->hasMany(Field::class, 'section_id')->where(['hide' => 0, 'visible_always' => 1])->where(['is_remove' => 0])->whereNull('group_id')->orderBy('sort');
    }

    public function visible_fields_points() {
        return $this->hasMany(Field::class, 'section_id')->where(['hide' => 0])->whereNull('group_id')->orderBy('sort');
    }

    public static function get(string $model, int $column_id = 1, $module = null) {
        if($module)
            $name = tenant('id').':section-'.$model.'-'.$column_id.'-'.$module;
        else
            $name = tenant('id').':section-'.$model.'-'.$column_id;
        $sections = cache()->getMemcached()->get($name);
        
        if(!$sections) {
            //$sections = cache()->rememberForever($name, function() use ($model, $column_id, $module) {
            	$data = \App\Models\FieldSection::orderBy('sort');
                $data = $data->where(['page' => $model, 'column_id' => $column_id, 'module' => $module]);  
                $data = $data->get()->toTree();
                $sections = $data;
                cache()->getMemcached()->add($name, $sections);
               // return $data;
            //});
        }

    	return $sections;
    }

    public function getFieldsByRoute(string $route) {
        $fields = $this->hasMany(Field::class, 'section_id')->where(['hide' => 0])->where(['is_remove' => 0])->whereNull('group_id')->orderBy('sort')->get();
        $fields = $fields->filter(function ($item) use ($route) {
            if($item->mobile_pages) {
                $pages = json_decode($item->mobile_pages, true);

                return in_array($route, $pages);
            }

            return false;
        });
        
        return $fields;
        
    }

}
