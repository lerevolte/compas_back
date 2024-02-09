<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use App\Models\SidebarItem;

class MenuController extends Controller
{
    public function builder(Request $request)
    {
        SidebarItem::whereIn('id', [67, 68, 69])->update(['parent_id' => 66]);
        
        return view('menu.builder');
    }

}