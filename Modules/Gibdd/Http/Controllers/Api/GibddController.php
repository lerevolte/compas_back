<?php

namespace Modules\Gibdd\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GibddController extends Controller
{
    public function check()
    {
        $tenant = \App\Models\Tenant::find(tenant('id'));
        \Modules\Gibdd\Entities\Module::update_fines($tenant);
    }

    public function findByNum($num)
    {
        $data = \Modules\Gibdd\Entities\Module::findByNum($num);

        return response()->json($data);
    }

    
}
