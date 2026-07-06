<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function check(Request $request) 
    {
        if(!$request->domain)
            return response()->json([
                'success' => false,
                'error' => 'Неправильный адрес портала'
            ], 200);
        $domain = strtolower(trim($request->domain));
        $domain = preg_replace('/\.compas\.pro$/', '', $domain);

        $tenant = Tenant::find($domain);
        if(!$tenant) {
            $record = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)
                ->orWhere('domain', $domain.'.compas.pro')
                ->first();
            if($record)
                $tenant = Tenant::find($record->tenant_id);
        }

        $account = null;
        if($tenant) {
            $account = \App\Models\Account::where('tenant_id', $tenant->id)->first();
            if(!$account)
                $account = \App\Models\Account::where('name', $tenant->id)->first();
            if(!$account)
                $account = \App\Models\Account::whereJsonContains('name->value', $tenant->id)->first();
            if(!$account)
                $account = \App\Models\Account::where('name', $domain)->first();
            if(!$account)
                $account = \App\Models\Account::whereJsonContains('name->value', $domain)->first();
        }

        if($tenant && $account || $domain == 'admin')
            return response()->json([
                'success' => true
            ], 200);


        return response()->json([
                'success' => false,
                'error' => 'Неправильный адрес портала'
            ], 200);
    }

    public function delete(Request $request) 
    {
        $tenant = tenant('id');
        \DB::table('users')->update(['api_token' => null]);

        tenancy()->central(function () use ($tenant) {
            $account = \App\Models\Account::where('name', $tenant)->first();
            if(!$account)
                $account = \App\Models\Account::whereJsonContains('name->value', $tenant)->first();
            $account->delete();
        });
        
    }

}