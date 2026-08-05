<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\Bitrix24\Services\B24EntitySync;

/**
 * Подтягивает ответственных (ASSIGNED_BY_ID) из Bitrix24 для уже
 * синхронизированных сделок/контактов/компаний без ответственного (8874).
 *
 * Работает только там, где настроен B24EntitySync (вебхук + сущности).
 * Команда идемпотентна: трогает лишь записи с пустым user_id и b24_id.
 *   php artisan b24:backfill-responsible avixo
 */
class BackfillB24Responsibles extends Command
{
    protected $signature = 'b24:backfill-responsible
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Заполнить «Ответственный» из Bitrix24 у сделок/контактов/компаний без него (8874)';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->applyTo((string) $tenant->id));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->applyTo((string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function applyTo(string $label): void
    {
        $svc = B24EntitySync::make();
        if (!$svc) {
            $this->line("  {$label}: B24EntitySync не настроен, пропуск");
            return;
        }
        foreach ($svc->backfillResponsibles() as $table => $stat) {
            $this->line("  {$label}: {$table} — без ответственного {$stat['empty']}, заполнено {$stat['updated']}");
        }
    }
}
