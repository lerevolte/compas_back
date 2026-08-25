<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\Bitrix24\Services\B24EntitySync;

class BackfillB24CompanyRequisites extends Command
{
    protected $signature = 'b24:backfill-company-requisites
        {target=avixo : all-tenants | <tenant_id>}
        {--force : выгрузить реквизиты всех компаний с b24_id, перезаписывая заполненные поля значениями из Bitrix24}';

    protected $description = 'Выгрузить реквизиты компаний (ИНН/КПП/ОГРН/адреса/руководители) и банковские реквизиты из Bitrix24';

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
        $stat = $svc->backfillCompanyRequisites((bool) $this->option('force'));
        $this->line("  {$label}: companies — проверено {$stat['checked']}, обновлено {$stat['updated']}; банковские реквизиты — создано {$stat['bank_created']}, обновлено {$stat['bank_updated']}, удалено {$stat['bank_deleted']}");
    }
}
