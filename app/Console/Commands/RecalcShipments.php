<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use App\Services\ShipmentService;
use Illuminate\Console\Command;

class RecalcShipments extends Command
{
    protected $signature = 'shipments:recalc
        {target=avixo : all-tenants | <tenant_id>}
        {--deal= : пересчитать только один заказ}';

    protected $description = 'Пересчитать колонку «Отгружено» в составе заказов покупателей по связанным расходным накладным';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->recalc((string) $tenant->id));
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

        $tenant->run(fn () => $this->recalc($target));

        return self::SUCCESS;
    }

    private function recalc(string $label): void
    {
        if (!ShipmentService::ready()) {
            $this->line("  [{$label}] модуль не установлен, пропуск");
            return;
        }

        $dealId = (int) $this->option('deal');
        $dealIds = $dealId
            ? [$dealId]
            : ObjectRelation::where('source_slug', ShipmentService::SOURCE)
                ->where('target_slug', ShipmentService::DOCUMENT)
                ->distinct()
                ->pluck('source_id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $changed = 0;
        foreach ($dealIds as $id) {
            if (ShipmentService::recalcForDeal($id)) {
                $changed++;
            }
        }

        $this->info("  [{$label}] заказов со связями: " . count($dealIds) . ", обновлено: {$changed}");
    }
}
