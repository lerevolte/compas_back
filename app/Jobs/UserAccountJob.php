<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CrudService;

class UserAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('user account job');
    	$tenant = tenant('id');
        $c1 = \DB::table('users')->whereNull('deleted_at')->count();
        $c2 = \DB::table('users')->whereNotNull('deleted_at')->count();
        if($tenant) {
            tenancy()->central(function () use ($tenant, $c1, $c2) {
                $crudService = new CrudService;
                $account = \App\Models\Account::where('tenant_id', $tenant)->first();
                $data = [
                    'id' => $account->id,
                    'count_users' => $c1,
                    'count_deleted_users' => $c2
                ];

                $result = $crudService->batch('accounts', [$data]);
            });
        }

    }
}
