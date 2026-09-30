<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Bitrix24\Services\B24ProductSync;

class Bitrix24BackfillProductProps extends Command
{
    protected $signature = 'bitrix24:backfill-product-props
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Заполнить у товаров «Штрихкод поставщиков» (PROPERTY_143), «Срок пополнения» (PROPERTY_171), «ID 1С» (XML_ID) и «Состав набора» (FACT_PRODUCT) из товаров Bitrix24';

    public function handle(): int
    {
        $target = $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : Tenant::where('id', $target)->get();
        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    if (!B24ProductSync::ready()) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    if (Schema::hasColumn('products', 'kit_products') && !Schema::hasColumn('products', B24ProductSync::FACT_COLUMN)) {
                        DB::statement('ALTER TABLE `products` ADD COLUMN `' . B24ProductSync::FACT_COLUMN . '` VARCHAR(32) NULL');
                    }
                    $svc = B24ProductSync::make();
                    $muted = \App\Services\SiteProductSync::$muted;
                    \App\Services\SiteProductSync::$muted = true;
                    try {
                        $result = \Modules\Bitrix24\Services\B24EntitySync::asModuleUser(fn () => $svc->pullExtraProps());
                    } finally {
                        \App\Services\SiteProductSync::$muted = $muted;
                    }
                    $this->info("  ✓ {$tenant->id}: получено {$result['fetched']}, обновлено товаров {$result['updated']}, изменено наборов {$result['kits']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
