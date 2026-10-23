<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\PurchaseTermService;
use Illuminate\Console\Command;

class RecalcPurchaseTerm extends Command
{
    protected $signature = 'products:recalc-purchase-term {target=all-tenants : all-tenants | <tenant_id>}';

    protected $description = 'Пересчитать «Средний срок закупки» товаров там, где поле установлено';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    PurchaseTermService::forget();
                    if (PurchaseTermService::ready()) {
                        $this->line("  [{$tenant->id}] обновлено товаров: " . PurchaseTermService::recalcAll());
                    }
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
