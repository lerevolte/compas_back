<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Models\Filter;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class FilterController extends Controller
{   
    public function list($model)
    {
        $data = \App\Models\Filter::list($model);

        return response()->json($data);
    }

    public function store($model, Request $request)
    {
        $entity = \DB::table('data_types')->where('slug', $model)->first();
        $last_filter = Filter::where(['data_type' => $entity->id, 'user_id' => \Auth::user()->id])->orderBy('sort', 'desc')->first();
        $sort = 1;
        if($last_filter)
            $sort = $last_filter->sort + 1;
        $item = Filter::create([
            'name' => $request->title,
            'data_type' => $entity->id,
            'user_id' => \Auth::user()->id,
            'sort' => $sort,
            'is_hidden' => null
        ]);

        $config = array();
        foreach($request->all()['fields'] as $field => $value) {
            $config['fields'][$field] = $value;
        }
        if(isset($config['fields'])) {
            $item->config = json_encode($config, JSON_UNESCAPED_UNICODE);
            $item->save();
        }

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "filters/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        cache()->getMemcached()->delete(tenant('id').':filter-'.$model.'-'.\Auth::user()->id);
        // $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':filter-'.$model.'-'.\Auth::user()->id;
        // foreach($keys as $item_cache) {
        //     if(preg_match('/'.$regex.'/', $item_cache)) {
        //         cache()->getMemcached()->delete($item_cache);
        //     }
        // }

        return response()->json($item);
    }

    public function update($model, $id, Request $request)
    {
        $filter = Filter::find($id);

        $filter->update([
            'name' => $request->title,
            'user_id' => \Auth::user()->id
        ]);

        if($request->get('fields')) {
            $config = array();
            foreach($request->all()['fields'] as $field => $value) {
                $config['fields'][$field] = $value;
            }
            $filter->config = json_encode($config, JSON_UNESCAPED_UNICODE);
        }
        $filter->save();

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "filters/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        cache()->getMemcached()->delete(tenant('id').':filter-'.$model.'-'.\Auth::user()->id);
        // $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':filter-'.$model.'-'.\Auth::user()->id;
        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }

        return response()->json($filter);
    }

    public function delete($model, $id, Request $request)
    {
        $filter = Filter::find($id);
        if($filter->is_hidden)
            return response()->json(['error' => 403]);
        Filter::destroy($id);

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "filters/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        cache()->getMemcached()->delete(tenant('id').':filter-'.$model.'-'.\Auth::user()->id);
        // $keys = cache()->getMemcached()->getAllKeys();
        // $regex = tenant('id').':filter-'.$model.'-'.\Auth::user()->id;
        // foreach($keys as $item) {
        //     if(preg_match('/'.$regex.'/', $item)) {
        //         cache()->getMemcached()->delete($item);
        //     }
        // }

        return response()->json(['success' => true]);
    }

    public function sort($slug, Request $request)
    {
        info('FILTER SORT');
        info($request->items);
        $items = \App\Models\Filter::whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            info($item->id.' '.$key);
            $item->sort = $key;
            $item->save();
        }

        $now = Carbon::now();
        if(\DB::table('local_cache')->where(['url' => "filters/$slug", 'user_id' => \Auth::user()->id])->exists())
            \DB::table('local_cache')->where(['url' => "filters/$slug", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
        else
            \DB::table('local_cache')->insert(['url' => "filters/$slug", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

        cache()->getMemcached()->delete(tenant('id').':filter-'.$slug.'-'.\Auth::user()->id);

        return response()->json(['success' => true]);
    }

    // public function changeSort($model, $id, Request $request)
    // {
    //     $entity = \DB::table('data_types')->where('slug', $model)->first();
    //     $filter = Filter::find($id);
    //     $filters = \App\Models\Filter::where(['user_id' => \Auth::user()->id, 'data_type' => $entity->id])->orderBy('sort', 'asc')->get();
    //     $sort = $filter->sort;
    //     foreach ($filters as $key => $item) {
    //         if($item->id == $filter->id && $request->direction == 'up' && isset($filters[$key-1])) {
    //             $filter->sort = $filters[$key-1]->sort;
    //             $filters[$key-1]->sort = $sort;
    //             $filter->save();
    //             $filters[$key-1]->save();
    //         } elseif($item->id == $filter->id && $request->direction == 'down' && isset($filters[$key+1])) {
    //             $filter->sort = $filters[$key+1]->sort;
    //             $filters[$key+1]->sort = $sort;
    //             $filter->save();
    //             $filters[$key+1]->save();
    //         }
    //     }

    //     $now = Carbon::now();
    //     if(\DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->exists())
    //         \DB::table('local_cache')->where(['url' => "filters/$model", 'user_id' => \Auth::user()->id])->update(['updated_at' => $now]);
    //     else
    //         \DB::table('local_cache')->insert(['url' => "filters/$model", 'user_id' => \Auth::user()->id, 'created_at' => $now, 'updated_at' => $now]);

    //     info('FILTER SORT');
    //     $keys = cache()->getMemcached()->getAllKeys();
    //     $regex = tenant('id').':filter-'.$model.'-'.\Auth::user()->id;
    //     foreach($keys as $item) {
    //         if(preg_match('/'.$regex.'/', $item)) {
    //             cache()->getMemcached()->delete($item);
    //         }
    //     }

    //     return response()->json(['success' => true]);
    // }
    
}
