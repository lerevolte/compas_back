<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CreateDatabaseCommand;
use App\Console\Commands\GibddUpdateCommand;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CreateDatabaseCommand::class,
        GibddUpdateCommand::class
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('gps:fetch')->everyMinute();
        //$schedule->command('gibdd:update')->everyMinute();
        //$schedule->command('bitrix24:sync-entities all-tenants')->everyTenMinutes()->withoutOverlapping();
        $tenants = \App\Models\Tenant::get();
        //info('SCHEDULER');
        foreach ($tenants as $tenant) {
            $time = $tenant->run(function () use ($tenant) {
                $local_module = \DB::table('modules')->where('slug', 'gibdd')->first();
                if($local_module) {
                    $data = json_decode($local_module->config, true);
                    if(isset($data['fields'])) {
                        foreach($data['fields'] as $item) {
                            if($item['key'] == 'update_rate') {
                                return $item['value'];
                            }
                        }
                    }
                }
            });
            if(!$time) {
                $time = 1;
            }
           // $tenant->run(function () use ($tenant, $schedule) {
                $schedule->call(function () use ($tenant) {
                    // \Modules\Gibdd\Entities\Module::importDrivers($tenant);
                    // \Modules\Gibdd\Entities\Module::importCars($tenant);
                    $tenant->run(function () {
                        \Modules\Gibdd\Entities\Module::findFines();
                        //\Modules\Gibdd\Entities\Module::updateFines();
                    });
                })->cron("30 6 */$time * *");
                $schedule->call(function () use ($tenant) {
                    info('calculateDaily '.$tenant);
                    // \Modules\Gibdd\Entities\Module::importDrivers($tenant);
                    // \Modules\Gibdd\Entities\Module::importCars($tenant);
                    $tenant->run(function () {
                        $balance = \App\Models\Balance::first();
                        if($balance)
                            $balance->calculateDaily();
                    });
                })->cron("5 0 * * *");
                // $local_module = \DB::table('modules')->where('slug', 'gibdd')->first();
                // if($local_module && $local_module->config) {
                //     $config = json_decode($local_module->config, true);
                    
                //     $schedule->call(function () use ($tenant) {
                        
                //         \Modules\Gibdd\Entities\Module::update_fines($tenant);
                //     })->cron("0 0 */1 * *");
                //     //})->cron("0 0 */".$config[0]['value']." * *");
                // }
            //});
        }
        
        
        // echo '<pre>';
        // print_r($data[0]['value']);
        // echo '</pre>';
        // $schedule->call(function () {
        //     info('CRON');
        //     \DB::table('fields')->delete();
        // })->everyMinute();

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
