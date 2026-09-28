<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class Bitrix24BackfillArticle extends Command
{
    protected $signature = 'bitrix24:backfill-article
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Заполнить «Артикул» товаров (id на сайте) из свойства PROPERTY_131 товаров Bitrix24';

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
                    $svc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if (!$svc) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    $stat = $svc->pullArticles();
                    $this->info("  ✓ {$tenant->id}: получено {$stat['fetched']}, обновлено {$stat['updated']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
