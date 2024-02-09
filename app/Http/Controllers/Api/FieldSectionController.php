<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\FieldSection;
use App\Models\Field;

class FieldSectionController extends Controller
{
    // public function list(string $model, Request $request)
    // {
    //     $hidden_fields = Field::getHiddenFields($model);
    //     $sections_1 = \App\Models\FieldSection::get($model, 1);
    //     $sections_2 = \App\Models\FieldSection::get($model, 2);

    //     $request->validate([
    //         'name' => 'required',
    //     ]);
    //     $last_column_section = \DB::table('field_sections')->selecT('sort')->where('column_id', $request->column_id)->orderBy('sort', 'desc')->first();
    //     $item = FieldSection::create([
    //         'name' => $request->name,
    //         'page' => $request->page,
    //         'column_id' => $request->column_id,
    //         'sort' => ($last_column_section->sort + 1)
    //     ]);

    //     return response()->json($item);
    // }

	public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $last_column_section = \DB::table('field_sections')->selecT('sort')->where('column_id', $request->column_id)->orderBy('sort', 'desc')->first();
        $data = array(
            'name' => $request->name,
            'page' => $request->slug,
            'column_id' => $request->column_id,
            'sort' => ($last_column_section->sort + 1)
        );
        $item = FieldSection::create($data);

        \App\Events\FieldUpdated::dispatch('SectionCreated', $data);


        cache()->getMemcached()->delete(tenant('id').':section-'.$request->slug.'-'.$request->column_id);


        return response()->json($item);
    }

    public function update(int $id, Request $request)
    {

        $field_section = FieldSection::find($id);
        $field_section->update($request->all());
        $data = array(
            'name' => $field_section->name,
            'page' => $field_section->page,
            'column_id' => $field_section->column_id,
            'sort' => $field_section->sort
        );

        \App\Events\FieldUpdated::dispatch('SectionUpdated', $data);

        cache()->getMemcached()->delete(tenant('id').':section-'.$field_section->page.'-'.$field_section->column_id);
        
        return response()->json($request->all());
    }

    public function changeSort(Request $request)
    {
        $data = array(
            'column_1' => array(),
            'column_2' => array()
        );
        if($request->column_1 && count($request->column_1)) {
            $items = FieldSection::whereIn('id', $request->column_1)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->column_1).")"))->get();
            foreach ($items as $key => $item) {
                $item->sort = $key;
                $item->column_id = 1;
                $item->save();
                $data['column_1'][] = array(
                    'id' => $item->id,
                    'sort' => $item->sort,
                    'column_id' => $item->column_id
                );
                cache()->getMemcached()->delete(tenant('id').':section-'.$item->page.'-'.$item->column_id);
            }
        }

        if($request->column_2 && count($request->column_2)) {
            $items = FieldSection::whereIn('id', $request->column_2)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->column_2).")"))->get();
            foreach ($items as $key => $item) {
                $item->sort = $key;
                $item->column_id = 2;
                $item->save();
                $data['column_2'][] = array(
                    'id' => $item->id,
                    'sort' => $item->sort,
                    'column_id' => $item->column_id
                );
                cache()->getMemcached()->delete(tenant('id').':section-'.$item->page.'-'.$item->column_id);
            }
        }

        
        \App\Events\FieldUpdated::dispatch('SectionSorted', $data);

        // $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':section-'.$item->page.'-*';
        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }
        
    }

    public function hide(Request $request) {
        \DB::table('field_sections')->where(['id' => $request->section])->update([
            'hide' => 1
        ]);

        return $request->section;
    }

    public function delete($id, Request $request)
    {
        $section = FieldSection::find($id);
        cache()->getMemcached()->delete(tenant('id').':section-'.$section->page.'-'.$section->column_id);
        if(!$section->fields()->count()){
            FieldSection::destroy($id);
            \App\Events\FieldUpdated::dispatch('SectionDeleted', array('id' => $id));

            
        } else {
            return response()->json(array('error' => 'Удалите поля, привязанные к разделу!'));
        }
    }
}