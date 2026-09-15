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
        if ($request->module) {
            if (!\App\Services\ModuleLayoutService::isSeeds()) {
                return response()->json(['error' => 'Разделы модулей редактируются только в портале seeds'], 403);
            }
            $sort = (int) \DB::table('field_sections')
                ->where('page', $request->slug)
                ->where('module', $request->module)
                ->where('column_id', (int) $request->column_id)
                ->max('sort') + 1;
            $now = now();
            $id = \DB::table('field_sections')->insertGetId([
                'sort' => $sort, 'name' => $request->name, 'domain_key' => null, 'page' => $request->slug,
                'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
                'column_id' => (int) $request->column_id, 'module' => $request->module, 'parent_id' => null,
                '_lft' => 0, '_rgt' => 0, 'is_short' => 0,
            ]);
            $item = FieldSection::find($id);
            \App\Events\FieldUpdated::dispatch('SectionCreated', ['name' => $item->name, 'page' => $item->page, 'column_id' => $item->column_id, 'sort' => $item->sort, 'module' => $item->module]);
            \App\Services\ModuleLayoutService::queueSyncFromSeeds((string) $request->module, (string) $request->slug);

            return response()->json($item);
        }
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
        if ($field_section->module && !\App\Services\ModuleLayoutService::isSeeds()) {
            return response()->json(['error' => 'Разделы модулей редактируются только в портале seeds'], 403);
        }
        $field_section->update($request->all());
        if ($field_section->module) {
            \App\Services\ModuleLayoutService::queueSyncFromSeeds((string) $field_section->module, (string) $field_section->page);
        }
        $data = array(
            'name' => $field_section->name,
            'page' => $field_section->page,
            'column_id' => $field_section->column_id,
            'sort' => $field_section->sort,
            'is_short' => $field_section->is_short
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

        $ids = array_merge((array) $request->column_1, (array) $request->column_2);
        if (count($ids)) {
            $moduleSections = FieldSection::whereIn('id', $ids)->whereNotNull('module')->where('module', '!=', '')->get(['page', 'module'])
                ->unique(fn ($s) => $s->module . ':' . $s->page);
            foreach ($moduleSections as $section) {
                \App\Services\ModuleLayoutService::queueSyncFromSeeds((string) $section->module, (string) $section->page);
            }
        }

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
        if($section->module)
            $name = tenant('id').':section-'.$section->page.'-'.$section->column_id.'-'.$section->module;
        else
            $name = tenant('id').':section-'.$section->page.'-'.$section->column_id;
        cache()->getMemcached()->delete($name);
        if($section->module) {
            if (!\App\Services\ModuleLayoutService::isSeeds()) {
                return response()->json(['error' => 'Разделы модулей редактируются только в портале seeds'], 403);
            }
            $db = \DB::connection();
            foreach ($db->table('data_rows')->whereJsonContains('module_section_id', (int) $id)->get() as $row) {
                \App\Services\ModuleLayoutService::detachField($db, $row, (string) $section->module, (int) $id);
            }
            $db->table('section_fields_sort')->where('section_id', $id)->delete();
            FieldSection::destroy($id);
            \App\Models\Settings::clear_cache();
            \App\Events\FieldUpdated::dispatch('SectionDeleted', array('id' => $id));
            \App\Services\ModuleLayoutService::queueSyncFromSeeds((string) $section->module, (string) $section->page);

            return response()->json(['id' => $id]);
        }
        if(!$section->fields()->count()){
            FieldSection::destroy($id);
            \App\Events\FieldUpdated::dispatch('SectionDeleted', array('id' => $id));

            
        } else {
            return response()->json(array('error' => 'Удалите поля, привязанные к разделу!'));
        }
    }
}