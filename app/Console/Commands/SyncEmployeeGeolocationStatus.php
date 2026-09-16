<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\GeolocationStatusService;
use Illuminate\Console\Command;

class SyncEmployeeGeolocationStatus extends Command
{
    protected $signature = 'employees:sync-geolocation-status
        {target=all-tenants : all-tenants | <tenant_id>}';

    protected $description = 'Обновить статус «Геолокация» у сотрудников по свежести геопозиции связанных пользователей';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        if (!GeolocationStatusService::ready()) {
                            return;
                        }
                        $updated = GeolocationStatusService::refresh();
                        if ($updated) {
                            $this->line("  {$tenant->id}: обновлено {$updated}");
                        }
                    });
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($target) {
            $updated = GeolocationStatusService::refresh();
            $this->info("{$target}: обновлено {$updated}");
        });

        return self::SUCCESS;
    }
}
