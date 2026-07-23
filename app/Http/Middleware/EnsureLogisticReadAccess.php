<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLogisticReadAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = \Auth::user();
        if ($user && !$user->isAdmin() && $user->role_id) {
            $dataTypeId = \DB::table('data_types')->where('slug', 'logistic')->value('id');
            if ($dataTypeId && $user->role) {
                $permission = $user->role->permissions()
                    ->where('entity_id', $dataTypeId)
                    ->first();
                if ($permission && $permission->read_p == 'N') {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }
        }

        return $next($request);
    }
}
