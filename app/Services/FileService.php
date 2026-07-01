<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\File;
use App\Http\Requests\StoreFileRequest;
use Maestroerror\HeicToJpg;
use Intervention\Image\ImageManagerStatic as Image;

class FileService
{
    public function upload($files): array
    {
        $result = array();
        $tenant = tenant('id');
        foreach ($files as $k => $f) {
            info($f);
            $filename = str_replace('.heic', '.jpeg', $f->getClientOriginalName());
            $file = $f->store('files');

            $ext = $f->extension();
            if($ext == 'heic') {
                $old_file = $file;
                $file = str_replace('.heic', '.jpg', $file);
                $old_path = storage_path('app/public/'.$old_file);
                $new_path = storage_path('app/public/'.$file);
   
                HeicToJpg::convert($old_path)->saveAs($new_path);

                $img = Image::make($new_path);
                $img->orientate();
                $img->save($new_path);
                
                $ext = 'jpg';
            } elseif(in_array($ext, array('jpg', 'jpeg'))) {
                // Раньше проверялся только 'jpg' — фото с телефона часто имеют
                // расширение 'jpeg', для них orientate() не вызывался: сохранялся
                // файл с EXIF-поворотом, полное фото браузер разворачивал сам, а
                // превью (генерится из этого файла) оставалось повёрнутым (8595).
                // ВАЖНО: orientate() применяет EXIF-поворот к пикселям только при
                // наличии расширения ext-exif; без него разворота не будет.
                $old_file = $file;
                $old_path = storage_path('app/public/'.$old_file);

                try {
                    $img = Image::make($old_path);
                    $img->orientate();
                    $img->save($old_path);
                } catch (\Throwable $e) {
                    \Log::warning('file orientate failed', ['path' => $old_path, 'error' => $e->getMessage()]);
                }
            }
            $document = new File();
            $document->name = $filename;
            $document->path = $file;
            $document->save();
            $thumbnail = null;
            if($tenant)
                $path = 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file;
            else
                $path = 'https://compas.pro/storage/app/public/'.$file;

            $media = $document->addMediaFromUrl($path)->toMediaCollection();
            if(in_array($ext, array('xls', 'xlsx')))
                $thumbnail = '/files/xls.svg';
            elseif(in_array($ext, array('doc', 'docx')))
                $thumbnail = '/files/word.svg';
            elseif($ext == 'pdf')
                $thumbnail = '/files/pdfSmall.svg';
            elseif(in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'webp'))) {
                $thumbnail = \Thumbnail::src($path)->heighten(200)->url();
            } elseif($ext != 'svg')
                $thumbnail = '/files/undefinedFile.svg';
            
            $result[] = array(
                'id' => $document->id,
                'name' => $filename,
                'url' => $thumbnail ? $thumbnail : $path,
                'file' => $path,
                'extension' => $f->extension(),
                'sort' => $k,
                'ext' => $ext
            );
        }
        return $result;
    }
}