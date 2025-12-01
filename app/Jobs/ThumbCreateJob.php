<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ThumbCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $object_id;
    private $field_id;

    public function __construct($object_id, $field_id)
    {
        $this->object_id = $object_id;
        $this->field_id = $field_id;
    }

    public function handle()
    {
    	sleep(3);
    	$tenant = tenant('id');
        $field = \DB::table('data_rows')->where('id', $this->field_id)->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();

        $entity_class = $entity->model_name;

        $object = $entity_class::find($this->object_id);
        $files = json_decode($object->{$field->field}, true);
        info('thumbcreate1');
        foreach($files as $k=>$file) {
            info('thumbcreate');
        	info($file);
        	if($file['extension'] == 'pdf' && $files[$k]['url'] == '/public/files/pdfSmall.svg') {
        		$document = \App\Models\File::find($file['id']);
        		$media = $document->addMediaFromUrl($file['file'])->toMediaCollection();
                if($tenant)
        		  $thumbnail = str_replace(base_path(), 'https://'.$tenant.'.compas.pro', $media->getPath('thumb'));
                else
                    $thumbnail = str_replace(base_path(), 'https://compas.pro', $media->getPath('thumb'));
                info('thumbcreate3');
                info($thumbnail);
        		$files[$k]['url'] = $thumbnail;
        	}
        }
        info($files);
        $object->{$field->field} = json_encode($files, JSON_UNESCAPED_UNICODE);
        $object->save();
        $object = $entity_class::find($this->object_id);
        $data = $object->getData();
        //\App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);

    }
}


/*
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ThumbCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $object_id;
    private $field_id;
    public $timeout = 240;

    public function __construct($object_id, $field_id)
    {
        $this->object_id = $object_id;
        $this->field_id = $field_id;
    }

    public function handle()
    {
        sleep(3);
        $tenant = tenant('id');
        $field = \DB::table('data_rows')->where('id', $this->field_id)->first();
        $entity = \DB::table('data_types')->where('id', $field->data_type_id)->first();

        $entity_class = $entity->model_name;

        $object = $entity_class::find($this->object_id);
        $files = json_decode($object->{$field->field}, true);
        //info('thumbcreate1');
        $pdf_file_path = [];
        foreach($files as $k=>$file) {
            // info('thumbcreate');
            // info($file);
            if($file['extension'] == 'pdf' && $files[$k]['url'] == '/public/icons/new/pdfSmall.svg') {
                $document = \App\Models\File::find($file['id']);
                $media = $document->addMediaFromUrl($file['file'])->toMediaCollection();
                $path = $media->getPath('thumb');
                $pdf_file_path[$k] = $path;
            }
        }
        //info($files);
        sleep(60);
        if(count($pdf_file_path)) {
            // info('OBJECT '.$object->id);
            // info($pdf_file_path);
            foreach($pdf_file_path as $k => $path) {
                // $i = 0;
                // while(!file_exists($path)) {
                //     info('1not exist '.$path);
                //     //sleep(10);
                //     $i++;
                // };

                if($tenant)
                    $thumbnail = str_replace(base_path(), 'https://'.$tenant.'.compas.pro', $path);
                else
                    $thumbnail = str_replace(base_path(), 'https://compas.pro', $path);

                //if($i < 5) {
                // info($i);
                //     info('time '.now());
                    $files[$k]['url'] = $thumbnail;
                    $object->{$field->field} = json_encode($files, JSON_UNESCAPED_UNICODE);
        
                    $object->saveQuietly();
                //}
            }
        }
    }
}
*/
