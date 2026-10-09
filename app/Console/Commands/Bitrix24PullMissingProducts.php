<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class Bitrix24PullMissingProducts extends Command
{
    protected $signature = 'bitrix24:pull-missing-products
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : только показать, какие активные товары Bitrix24 отсутствуют в Компасе}';

    protected $description = 'Добрать активные товары Bitrix24, которых нет в Компасе (пропущенные инкрементальным синком)';

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
                $tenant->run(fn () => \Modules\Bitrix24\Services\B24EntitySync::asModuleUser(function () use ($tenant) {
                    $svc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if (!$svc) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    $result = $svc->pullMissing((bool) $this->option('dry-run'));
                    $this->info("  ✓ {$tenant->id}: в B24 активных {$result['total']}, нет в Компасе " . count($result['missing']) . ', удалённых локально ' . count($result['trashed']) . ($this->option('dry-run') ? '' : ", добрано {$result['pulled']}, ошибок " . count($result['failed'])));
                    if (count($result['missing'])) {
                        $this->line('    отсутствуют: ' . implode(', ', array_slice($result['missing'], 0, 100)) . (count($result['missing']) > 100 ? ', …' : ''));
                    }
                    if (count($result['failed'])) {
                        $this->line('    не удалось: ' . implode(', ', $result['failed']));
                    }
                }));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
