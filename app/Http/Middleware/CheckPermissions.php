<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CheckPermissions
{
    public function handle($request, Closure $next, $permission)
    {
        // $s = get_settings();
        // // echo Auth::user()->isAdmin();
        // // die();
        // if(!Auth::user()->hasRole('admin') && !Auth::user()->isAdmin()) {
        //     if (Auth::user() && isset($s['pages']['perms'][$permission]) && $s['pages']['perms'][$permission] == 'disabled' || !isset($s['pages']['perms'][$permission])) {
        //         if(\Auth::user()->hasRole('driver')) {
        //             $driver = \App\Models\Driver::where('user_id', \Auth::user()->id)->first();
        //             return redirect()->route('drivers.show', ['driver' => $driver->id]);
        //         } else if(\Auth::user()->hasRole('Shipping_Operators')) {
        //             return redirect()->route('shipments.index');
        //         } else {
        //             return abort(403);
        //         }
        //     }
        // }
            

        return $next($request);
    }
}