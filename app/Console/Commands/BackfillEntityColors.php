<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ColorPalette;
use App\Models\Tenant;

/**
 * Заполняет пустой color случайным градиентом у записей всех сущностей,
 * в таблицах которых есть колонка color (аватарки-плейсхолдеры в relation-полях, 8878).
 *
 * Записи с уже назначенным цветом (включая числовые ссылки на field_values
 * у маршрутов) не трогаются. Команда идемпотентна.
 *   php artisan colors:backfill avixo
 */
class BackfillEntityColors extends Command
{
    protected $signature = 'colors:backfill
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Заполнить пустой color случайным градиентом у всех сущностей (8878)';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->applyTo(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function applyTo($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $tables = $db->table('data_types')->pluck('slug')->unique();

        foreach ($tables as $table) {
            if (!$table || !$sb->hasTable($table) || !$sb->hasColumn($table, 'color')) {
                continue;
            }
            $ids = $db->table($table)
                ->where(function ($q) {
                    $q->whereNull('color')->orWhere('color', '');
                })
                ->pluck('id');
            foreach ($ids as $id) {
                $db->table($table)->where('id', $id)->update(['color' => ColorPalette::random()]);
            }
            if (count($ids)) {
                $this->line("  {$label}: {$table} — {$ids->count()}");
            }
        }

        if ($label !== 'admin_seeds') {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
