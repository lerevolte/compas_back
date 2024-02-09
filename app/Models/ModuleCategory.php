<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Providers\CRest;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nwidart\Modules\Facades\Module;
use Auth;

class ModuleCategory extends Model
{
    protected $guarded = ['id'];

    public function modules() {
    	$modules = Module::all();
        $data = array();
        
        foreach ($modules as $module) {
        	if($module->get('category') == $this->id) {
        		$logo_path = '';
        		$logo_res = $module->get('logo');
		        if($logo_res)
		            $logo_path = str_replace('storage/', '', \Storage::disk('public')->url('assets/modules/'.$module->getName().'/logo/'.$logo_res));
	            $data[] = array(
	                'name' => $module->get('display_name'),
	                'logo' => $logo_path,
	                'description' => $module->getDescription(),
	                'enabled' => $module->isEnabled() ? 1 : 0,
	                'alias' => strtolower($module->getName()),
	                'category' => $module->get('category')
	            );
            }

        }

        return $data;
    }

}
