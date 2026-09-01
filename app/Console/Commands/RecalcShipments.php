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
        {--source= : пересчитать только один объект, формат slug:id (например logistic_tasks:2119)}';

    protected $description = 'Пересчитать колонку «Отгружено» в составе задач логистики и самовывозов по связанным расходным накладным';

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

        $only = (string) $this->option('source');
        if ($only !== '' && str_contains($only, ':')) {
            [$slug, $id] = explode(':', $only, 2);
            $sources = [[$slug, (int) $id]];
        } else {
            $sources = ObjectRelation::whereIn('source_slug', ShipmentService::SOURCES)
                ->where('target_slug', ShipmentService::DOCUMENT)
                ->get(['source_slug', 'source_id'])
                ->map(fn ($r) => [(string) $r->source_slug, (int) $r->source_id])
                ->unique(fn ($r) => $r[0] . ':' . $r[1])
                ->values()
                ->all();
        }

        $changed = 0;
        foreach ($sources as [$slug, $id]) {
            if (ShipmentService::recalcForSource($slug, $id)) {
                $changed++;
            }
        }

        $this->info("  [{$label}] объектов со связями: " . count($sources) . ", обновлено: {$changed}");
    }
}
