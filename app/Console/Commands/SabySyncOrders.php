<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Saby\SabyOrderService;
use Illuminate\Console\Command;

class SabySyncOrders extends Command
{
    protected $signature = 'saby:sync-orders {target=all-tenants : all-tenants | <tenant_id>}';

    protected $description = 'Синхронизировать состояния заказов на перевозку Saby и связанные с ними ЭТрН';

    public function handle(): int
    {
        $tenants = $this->argument('target') === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $this->argument('target'))->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    if (!SabyOrderService::ready() || !SabyOrderService::tableReady()) {
                        return;
                    }
                    $service = SabyOrderService::make();
                    if (!$service) {
                        return;
                    }
                    $stat = $service->syncAll();
                    $this->info("  ✓ {$tenant->id}: orders={$stat['orders']}, waybills={$stat['waybills']}, linked={$stat['linked']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
