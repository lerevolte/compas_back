<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Models\File;
use App\Http\Requests\StoreFileRequest;

class FileController extends Controller
{
    public function store(StoreFileRequest $request)
    {
        if ($files = $request->file('file') && $request->field != 'avatar') {
            $file = $request->file->store('public/files');
            $model = $request->model;
            $id = $request->id;
            $field = $request->field;
            $document = new File();
            $document->name = pathinfo($file)['basename'];
            $document->path = $file;
            $document->save();
            // $item = \DB::table($model)->where(['id' => $id])->first();
            // $file_list = array();
            // info('model '.$model);
            // info('model '.$id);
            // if($item->{$field}) {
            //     $file_list = json_decode($item->{$field}, true);
            // }
            // $file_list[] = $document->id;
            // \DB::table($model)->where(['id' => $id])->update([
            //     $field => json_encode($file_list)
            // ]);
            return Response()->json([
                "id" => $document->id,
                "success" => true,
                "file" => str_replace('/public', '', Storage::disk('public')->url($file)),
                "field_name" => $request->field,
                "field_value" => json_encode($file_list),
                "extension" => $request->file('file')->extension()
            ]);
        }

  
    }
    public function store_to_model(Request $request)
    {
        \DB::table($request->model)->where(['id' => $request->id])->update([
            $request->field => $request->value
        ]);
    }
    public function destroy(Request $request)
    {
        $model = $request->model;
        $id = $request->id;
        $field = $request->field;
        info('FILES');
        info($request->file_ids);
        $file_ids = json_decode($request->file_ids, true);
        $item = \DB::table($model)->where(['id' => $id])->first();
        if($item->{$field}) {
            $file_list = json_decode($item->{$field}, true);
            info($file_list);
            foreach ($file_list as $key => $value) {
                if(in_array($value, $file_ids)) {
                    unset($file_list[$key]);
                    $f = File::find($value);
                    unlink(base_path('storage/app/'.$f->path));
                    $f->delete();
                }
            }
            info('DESTROY FILES');
            info(json_encode(array_values($file_list)));
            \DB::table($model)->where(['id' => $id])->update([
                $field => count($file_list) > 0 ? json_encode(array_values($file_list)) : ''
            ]);
        }
        
        return true;
  
    }

    public function update(Request $request)
    {
        $model = $request->model;
        $id = $request->id;
        $field = $request->field;
        $file_ids = json_decode($request->file_ids, true);
        // if(!is_array($file_ids)) {
        //     $file_ids = 
        // }
        //return Response()->json($file_ids);
        \DB::table($model)->where(['id' => $id])->update([
                $field => count($file_ids) > 0 ? json_encode(array_values($file_ids)) : ''
            ]);
        // $file_ids = json_decode(, true);
        // $item = \DB::table($model)->where(['id' => $id])->first();
        // if($item->{$field}) {
        //     $file_list = json_decode($item->{$field}, true);
        //     foreach ($file_list as $key => $value) {
        //         if(in_array($value, $file_ids)) {
        //             unset($file_list[$key]);
        //             $f = File::find($value);
        //             unlink('/home/i/infowewq/logistic/storage/app/'.$f->path);
        //             $f->delete();
        //         }
        //     }
        //     \DB::table($model)->where(['id' => $id])->update([
        //         $field => count($file_list) > 0 ? json_encode($file_list) : ''
        //     ]);
        // }
        
        return true;
  
    }
}


        