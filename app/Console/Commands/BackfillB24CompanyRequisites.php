<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\Bitrix24\Services\B24EntitySync;

/**
 * Подтягивает ИНН/КПП/юридический адрес из реквизитов Bitrix24 для уже
 * синхронизированных компаний с пустыми полями.
 *
 * Работает только там, где настроен B24EntitySync и у companies есть
 * колонки inn/kpp/address (ставит saby:install).
 * Команда идемпотентна: заполняет только пустые поля у компаний с b24_id.
 *   php artisan b24:backfill-company-requisites avixo
 */
class BackfillB24CompanyRequisites extends Command
{
    protected $signature = 'b24:backfill-company-requisites
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Заполнить ИНН/КПП/юр. адрес из реквизитов Bitrix24 у компаний без них';

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
        $stat = $svc->backfillCompanyRequisites();
        $this->line("  {$label}: companies — с пустыми реквизитами {$stat['checked']}, заполнено {$stat['updated']}");
    }
}
