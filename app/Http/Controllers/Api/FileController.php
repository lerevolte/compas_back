<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\File;
use App\Http\Requests\StoreFileRequest;

class FileController extends Controller
{
	public function store(Request $request)
    {
    	$result = array();
    	$tenant = tenant('id');
        if ($files = $request->file('files')) {
        	// info('FILES');
        	// info($files);
        	foreach ($files as $k => $f) {
        		$filename = $f->getClientOriginalName();
        		$file = $f->store('files');
        		$ext = $f->extension();
        		if(in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'webp'))) {
        			$disk = Storage::disk('public');
	        		$img = \Image::make($f);
					$img->orientate();
					$img->resize(1024, null, function($constraint){ 
					    $constraint->upsize();
					    $constraint->aspectRatio();
					});
					$img->save($disk->path($file), 90);
					//info($img->save());
				}
				//die();
        		// info($f);
        		// info($file);
	            $document = new File();
	            $document->name = $filename;
	            $document->path = $file;
	            $document->save();
	            $thumbnail = null;
	            //files/bKXYDi2X7Q6fX2YFy4Ks2V6TxJMZ1I4YTCygMHQ5.png
	            ///home/admin/web/compas.pro/public_html/storage/tenantopt6/app/public/files/logo.png
	            //info($document->path);
	            //$thumbnail = \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/'.$file)->heighten(200)->url();
	            
	            // info('EXT');
	            // info($ext);
	            $media = $document->addMediaFromUrl('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file)->toMediaCollection();
	            if(in_array($ext, array('xls', 'xlsx')))
	            	$thumbnail = '/icons/new/xls.svg';
	            elseif(in_array($ext, array('doc', 'docx')))
	            	$thumbnail = '/icons/new/word.svg';
	            elseif($ext == 'pdf')
	            	$thumbnail = str_replace('/home/admin/web/compas.pro/public_html', 'https://'.$tenant.'.compas.pro', $media->getPath('thumb'));
	            elseif(in_array($ext, array('png', 'jpg', 'jpeg', 'gif', 'webp')))
	            	$thumbnail = \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file)->heighten(200)->url();
	            elseif($ext != 'svg')
	            	$thumbnail = '/icons/new/undefinedFile.svg';
	            //info($thumbnail);
	            
	            $result[] = array(
	            	//'file' => array(
		            	'id' => $document->id,
		            	'name' => $filename,
		            	'url' => $thumbnail ? $thumbnail : 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file,
		            	'file' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file,
		            	'extension' => $f->extension(),
		            	'sort' => $k,
		            	// 'thumb' => array(
			            // 	$media->getUrl(), 
						// 	$media->getUrl('thumb')
						// )
	            	//),
	            	//'uid' => $request->uid
	            	//str_replace('/public', '', Storage::disk('public')->url($file))
	            );
	            //info($result);
        	}
        	return response()->json($result);
        }
    }
}