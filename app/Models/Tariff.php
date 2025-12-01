<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CrudService;

class Tariff extends Model
{
    protected $guarded = ['id'];
    
    public static function list() 
    {
        $data = array();
        $items = Tariff::get();
        foreach ($items as $item) {
            $data[] = array(
                'label' => $item->name,
                'value' => $item->id
            );
        }

        return $data;
    }

    public static function set($id)
    {
        $tariff = Tariff::where('id', $id)->firstOrFail();

        \DB::table('settings')->where([
            'key' => 'tariff'
        ])->update([
            'value' => $tariff->id//json_encode(['id' => $tariff->id])
        ]);

        $tenant = tenant('id');

        tenancy()->central(function () use ($tariff, $tenant) {
            $crudService = new CrudService;

            $account = Account::where('tenant_id', $tenant)->first();


            $data = [
                'id' => $account->id,
                'tariff' => $tariff->name
            ];
            $result = $crudService->batch('accounts', [$data]);
        });

        return $tariff;
    }

    public static function current()
    {
        $tariff = \DB::table('settings')->where([
            'key' => 'tariff'
        ])->first();
        //info(tenant('id'));

        if($tariff) {
            $data = Tariff::find($tariff->value);



            return $data;
        }

        return;
    }
    
}
