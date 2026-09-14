<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class Bitrix24PruneInactiveProducts extends Command
{
    protected $signature = 'bitrix24:prune-inactive-products
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : только посчитать, ничего не удалять}';

    protected $description = 'Удалить (soft delete) локальные товары, у которых в Bitrix24 снята Активность';

    public function handle(): int
    {
        $target = $this->argument('target');
        $dryRun = (bool) $this->option('dry-run');

        $tenants = $target === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $target)->get();

        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant, $dryRun) {
                    $svc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if (!$svc) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    $result = $svc->pruneInactive($dryRun);
                    $verb = $dryRun ? 'к удалению' : 'удалено';
                    $this->info("  ✓ {$tenant->id}: неактивных в B24={$result['inactive']}, {$verb} локально={$result['deleted']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
