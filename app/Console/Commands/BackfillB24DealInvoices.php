<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\Bitrix24\Services\B24EntitySync;

class BackfillB24DealInvoices extends Command
{
    protected $signature = 'b24:backfill-deal-invoices
        {target=avixo : all-tenants | <tenant_id>}';

    protected $description = 'Подтянуть счета на оплату (с банковскими реквизитами) из Bitrix24 для всех заказов покупателей с b24_id';

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
        $stat = $svc->backfillDealInvoices(function (array $stat) use ($label) {
            $this->line("  {$label}: заказов {$stat['deals']}, счетов {$stat['invoices']}, ошибок {$stat['failed']}");
        });
        if (!empty($stat['skipped'])) {
            $this->line("  {$label}: сущность «Счета на оплату» не установлена, пропуск");
            return;
        }
        $this->line("  {$label}: итого заказов {$stat['deals']}, счетов {$stat['invoices']}, ошибок {$stat['failed']}");
    }
}
