<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use App\Models\Tenant;

class BackfillStatusDefaults extends Command
{
    protected $signature = 'statuses:backfill
        {target=all-tenants : seeds | all-tenants | <tenant_id> (например avixo)}';

    protected $description = 'Проставить первое видимое значение статуса вместо NULL у всех объектов всех сущностей';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->info('Обновление базы-шаблона admin_seeds (connection: seeds)…');
            $this->backfill(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $tenants = Tenant::get();
            $this->info('Обновление всех порталов: '.$tenants->count());
            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        $this->backfill(\DB::connection(), (string) $tenant->id);
                    });
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: ".$e->getMessage());
                }
            }
            $this->info('Готово: all-tenants');
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

        $this->info("Обновление портала {$target}…");
        $tenant->run(function () use ($target) {
            $this->backfill(\DB::connection(), (string) $target);
        });
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function backfill(ConnectionInterface $db, string $label): void
    {
        $schema = $db->getSchemaBuilder();
        $types = $db->table('data_types')->where('enable', 1)->get(['id', 'slug']);

        foreach ($types as $type) {
            if (!$schema->hasTable($type->slug)) {
                continue;
            }

            $fields = $db->table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('type', 'status')
                ->where('is_remove', 0)
                ->get(['id', 'field']);

            foreach ($fields as $field) {
                if (!$schema->hasColumn($type->slug, $field->field)) {
                    continue;
                }

                $first = $db->table('field_values')
                    ->where('field_id', $field->id)
                    ->where('is_hidden', '!=', 1)
                    ->orderBy('is_hidden')
                    ->orderBy('sort')
                    ->first();
                if (!$first) {
                    continue;
                }

                $updated = $db->table($type->slug)
                    ->whereNull($field->field)
                    ->update([$field->field => $first->id]);

                if ($updated) {
                    $this->line("    {$label}: {$type->slug}.{$field->field} → «{$first->value}» (id {$first->id}), строк: {$updated}");
                }
            }
        }
    }
}
