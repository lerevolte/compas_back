<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class Bitrix24PullProducts extends Command
{
    protected $signature = 'bitrix24:pull-products
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Принудительно выгрузить все категории и товары из Bitrix24 (без фильтра по дате изменения)';

    public function handle(): int
    {
        $target = $this->argument('target');

        $tenants = $target === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $target)->get();

        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    $svc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if (!$svc) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    $categories = $svc->pullCategories();
                    $products = $svc->pullProducts(null);
                    $this->info("  ✓ {$tenant->id}: categories={$categories}, products={$products['count']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
