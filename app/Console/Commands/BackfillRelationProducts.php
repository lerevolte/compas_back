<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class BackfillRelationProducts extends Command
{
    protected $signature = 'relations:backfill-products
        {target=avixo : all-tenants | <tenant_id>}
        {--source= : ограничить сущностью-источником (например deals)}
        {--target-slug= : ограничить сущностью-приёмником (например logistic_tasks)}
        {--dry-run : показать план без изменений}';

    protected $description = 'Перенести «Состав» из объектов-источников в созданные на их основании объекты с пустым составом';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->backfill((string) $tenant->id));
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
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");

            return self::FAILURE;
        }

        $tenant->run(fn () => $this->backfill((string) $target));

        return self::SUCCESS;
    }

    private function backfill(string $label): void
    {
        if (!ObjectRelation::ready()) {
            $this->line("{$label}: нет object_relations — пропуск");

            return;
        }

        $dry = (bool) $this->option('dry-run');
        $sourceFilter = $this->option('source');
        $targetFilter = $this->option('target-slug');

        $query = ObjectRelation::query()->orderBy('id');
        if ($sourceFilter) {
            $query->where('source_slug', $sourceFilter);
        }
        if ($targetFilter) {
            $query->where('target_slug', $targetFilter);
        }

        $total = 0;
        $copied = 0;

        $query->chunkById(200, function ($relations) use ($dry, &$total, &$copied) {
            foreach ($relations as $relation) {
                $total++;
                $done = ObjectRelation::copyProducts(
                    $relation->source_slug,
                    (int) $relation->source_id,
                    $relation->target_slug,
                    (int) $relation->target_id,
                    $dry
                );

                if ($done) {
                    $copied++;
                    $this->line(sprintf(
                        '  %s %s#%d → %s#%d',
                        $dry ? '[dry]' : '✓',
                        $relation->source_slug,
                        $relation->source_id,
                        $relation->target_slug,
                        $relation->target_id
                    ));
                }
            }
        });

        $this->info("{$label}: связей {$total}, состав " . ($dry ? 'будет перенесён' : 'перенесён') . " у {$copied}");
    }
}
