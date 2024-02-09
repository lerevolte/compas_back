<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Permission;

class PageController extends Controller
{   
    public function home(Request $request)
    {
        // echo '<pre>';
        // print_r(\Auth::user()->roles_all());
        // echo '</pre>';
        // die();
        // $side_items = \App\Models\SidebarItem::orderBy('sort')->get();
        // $route = \Route::current();
        // $route_name = $route->getName();
        // foreach ($side_items as $item) {
        //     //if ('O' == Auth::user()->getPermission('read_'.$item->code) || 'settings' == $item->code && ('O' == Auth::user()->getPermission('read_users') || 'O' == Auth::user()->getPermission('read_roles')) || Auth::user()->hasPermission('browse_admin')) {
        //         if('/' != $item->link && !isset($request->id)) {
        //             return redirect($item->link);
        //         }
        //     //}
        // }
        // die();
        
        return view('home');
    }

    public function privacy(Request $request)
    {
        return view('privacy');
    }
   
}
