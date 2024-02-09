<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $account = \App\Models\Account::find(Auth::user()->account_id);

        return view('settings.index', compact('account'));
    }

    public function zones()
    {
        $account = \App\Models\Account::find(Auth::user()->account_id);

        return view('settings.zones', compact('account'));
    }

    public function users()
    {
        $first = \App\Models\User::orderBy('sort')->first();
        
        return redirect()->route('voyager.users.edit', ['id' => $first->id]);
    }

    public function roles()
    {
        $first = \App\Models\Role::orderBy('sort')->first();

        return redirect()->route('roles.edit', ['id' => $first->id]);
    }

    public function update(Request $request, \App\Models\Account $account)
    {
        $account->update($request->all());

        cache()->flush();

        return redirect()->back();
    }

    public function table_update(Request $request)
    {
        $user = \Auth::user();
        $tables = array();
        if($user->tables)
            $tables = json_decode($user->tables, true);
        $tables[$request->table] = $request->settings;
        $user->tables = json_encode($tables);
        $user->save();

        cache()->flush();

        return redirect()->back();
    }
}