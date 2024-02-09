<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Filter;

class FilterController extends Controller
{   
    public function show(Filter $filter)
    {
        //Filter::where(['is_active' => 1, 'data_type' => $filter->data_type, 'user_id' => \Auth::user()->id])->update(['is_active' => 0]);
        $filter->is_active = 1;
        $filter->save();

        return view('filters.show', compact('filter'));
    }

    public function store(Request $request)
    {
        $last_filter = Filter::where(['data_type' => $request->filter_type, 'user_id' => \Auth::user()->id])->orderBy('sort', 'desc')->first();
        $sort = 1;
        if($last_filter)
            $sort = $last_filter->sort + 1;
        $item = Filter::create([
            'name' => $request->filter_name,
            'data_type' => $request->filter_type,
            'user_id' => \Auth::user()->id,
            'sort' => $sort
        ]);

        $config = array();
        foreach($request->all() as $field => $value) {
            if($field == 'filter_name' && $field == 'filter_type')
                continue;
            $config['fields'][$field] = $value;
        }
        $item->config = json_encode($config, JSON_UNESCAPED_UNICODE);
        $item->save();

        return response()->json($item);
    }

    public function update(Request $request, Filter $filter)
    {
        $filter->update([
            'name' => $request->filter_name,
        ]);

        $config = array();
        foreach($request->all() as $field => $value) {
            if($field == 'filter_name' && $field == 'filter_type')
                continue;
            $config['fields'][$field] = $value;
        }
        $filter->config = json_encode($config, JSON_UNESCAPED_UNICODE);
        $filter->save();

        return response()->json($filter);
    }

    public function changeSort(Request $request, Filter $filter)
    {
        $filters = \App\Models\Filter::where(['user_id' => \Auth::user()->id, 'data_type' => $filter->data_type])->orderBy('sort', 'asc')->get();

        $sort = 1;
        info($filter);
        if($filter)
            $sort = $filter->sort;
        foreach ($filters as $key => $item) {
            if($item->id == $filter->id && $request->direction == 'up' && isset($filters[$key-1])) {
                $filter->sort = $filters[$key-1]->sort;
                $filters[$key-1]->sort = $sort;
                $filter->save();
                $filters[$key-1]->save();
            } elseif($item->id == $filter->id && $request->direction == 'down' && isset($filters[$key+1])) {
                $filter->sort = $filters[$key+1]->sort;
                $filters[$key+1]->sort = $sort;
                $filter->save();
                $filters[$key+1]->save();
            }
        }
    }

    public function destroy(Request $request)
    {
        $section = Filter::find($request->filter);
        Filter::destroy($request->filter);
    }

    public function add_field(Request $request, Filter $filter)
    {
        $filter->add_field($request->field);
    }

    public function show_field(Request $request)
    {
        $field_data = \DB::table('data_rows')->where(['field' => $request->field, 'data_type_id' => $request->data_type])->first();

        return view('fields.filter.'.$field_data->type, compact('field_data'));
    }

    public function changeSortFields(Request $request)
    {
        $filter = Filter::find($request->filter);
        $config = json_decode($filter->config, true);
        $fields = array();
        info($request->all());
        foreach ($request->items as $key => $field) {
            $fields[$field] = $config['fields'][$field];
        }
        $config['fields'] = $fields;
        $filter->config = json_encode($config, JSON_UNESCAPED_UNICODE);
        $filter->save();

        cache()->flush();

        return json_encode($filter);
    }
}
