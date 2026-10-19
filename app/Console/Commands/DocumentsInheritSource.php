<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use App\Services\SaleDocumentService;
use App\Services\ShipmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DocumentsInheritSource extends Command
{
    protected $signature = 'documents:inherit-source
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Заполнить пустые «Компания получатель», «Контакт» и «Компания отгрузки» расходных накладных и возвратов из задачи или самовывоза-основания';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");

            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->backfill((string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function backfill(string $label): void
    {
        if (!ObjectRelation::ready()) {
            $this->line("  {$label}: нет связей — пропуск");

            return;
        }
        $dry = (bool) $this->option('dry-run');
        $relations = ObjectRelation::whereIn('source_slug', ShipmentService::SOURCES)
            ->whereIn('target_slug', array_keys(SaleDocumentService::INHERIT_MODELS))
            ->orderBy('target_slug')
            ->orderBy('target_id')
            ->get(['source_slug', 'source_id', 'target_slug', 'target_id']);
        $invoices = 0;
        $byField = [];
        foreach ($relations as $relation) {
            if (!DB::getSchemaBuilder()->hasTable((string) $relation->target_slug)) {
                continue;
            }
            $filled = SaleDocumentService::inheritFromSource((string) $relation->target_slug, (string) $relation->source_slug, (int) $relation->source_id, (int) $relation->target_id, $dry);
            if (!count($filled)) {
                continue;
            }
            $invoices++;
            foreach ($filled as $field) {
                $byField[$field] = ($byField[$field] ?? 0) + 1;
            }
            $this->line("    {$relation->target_slug} #{$relation->target_id} ← {$relation->source_slug} #{$relation->source_id}: " . implode(', ', $filled));
        }
        $fields = count($byField) ? ' (' . implode(', ', array_map(fn ($f, $c) => "{$f}: {$c}", array_keys($byField), $byField)) . ')' : '';
        $this->info("  ✓ {$label}: " . ($dry ? 'будет заполнено' : 'заполнено') . " документов {$invoices}{$fields}");
    }
}
