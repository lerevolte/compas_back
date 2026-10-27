<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ProductCompositionService;
use Illuminate\Console\Command;

class SyncCompositionProductNames extends Command
{
    protected $signature = 'products:sync-composition-names {target=all-tenants : all-tenants | <tenant_id>} {--dry-run}';

    protected $description = 'Подставить текущие названия товаров в составы всех сущностей';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $dryRun = (bool) $this->option('dry-run');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant, $dryRun) {
                    $result = array_filter(ProductCompositionService::syncAllNames($dryRun));
                    $total = array_sum($result);
                    $details = collect($result)->map(fn ($n, $t) => "{$t}: {$n}")->implode(', ');
                    $this->line("  [{$tenant->id}] " . ($dryRun ? 'будет обновлено' : 'обновлено') . " записей: {$total}" . ($details ? " ({$details})" : ''));
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
