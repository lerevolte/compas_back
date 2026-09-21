<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class UninstallTaskNumbers extends Command
{
    protected $signature = 'logistic:uninstall-task-numbers
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--keep-column : не удалять колонку number из таблиц}';

    protected $description = 'Убрать поле «Номер» и сквозную нумерацию у задач логистики и самовывозов';

    public const FIELD = 'number';
    public const ENTITIES = ['logistic_tasks', 'pickups'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->uninstallFrom(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), (string) $tenant->id));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->uninstallFrom(\DB::connection(), $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function uninstallFrom($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();

        foreach (self::ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if ($typeId) {
                $ids = $db->table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', self::FIELD)
                    ->pluck('id')
                    ->all();
                if ($ids) {
                    if ($sb->hasTable('section_fields_sort')) {
                        $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                    }
                    if ($sb->hasTable('field_values')) {
                        $db->table('field_values')->whereIn('field_id', $ids)->delete();
                    }
                    $db->table('data_rows')->whereIn('id', $ids)->delete();
                    $this->line("    [{$label}] {$slug}: поле удалено");
                }
            }

            if ($sb->hasTable('histories')) {
                $db->table('histories')->where('entity', $slug)->where('field', self::FIELD)->delete();
            }

            $this->stripTableColumn($db, $slug);

            if (!$this->option('keep-column') && $sb->hasTable($slug) && $sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` DROP COLUMN `" . self::FIELD . '`');
            }

            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                $db->table('local_cache')->where('url', "tables/{$slug}")->update(['updated_at' => now()]);
            }
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }

    private function stripTableColumn($db, string $slug): void
    {
        $rewrite = function ($raw) use ($slug) {
            $tables = $raw ? json_decode($raw, true) : null;
            if (!is_array($tables) || !isset($tables[$slug]['fields']) || !is_array($tables[$slug]['fields'])) {
                return null;
            }
            $fields = array_values(array_filter($tables[$slug]['fields'], fn ($col) => !(is_array($col) && ($col['key'] ?? null) === self::FIELD)));
            if (count($fields) === count($tables[$slug]['fields'])) {
                return null;
            }
            foreach ($fields as $i => $col) {
                $fields[$i]['index'] = $i;
            }
            $tables[$slug]['fields'] = $fields;
            if (($tables[$slug]['sort_field'] ?? null) === self::FIELD) {
                $tables[$slug]['sort_field'] = null;
            }
            return json_encode($tables, JSON_UNESCAPED_UNICODE);
        };

        $sb = $db->getSchemaBuilder();
        foreach (['users', 'roles'] as $table) {
            if (!$sb->hasTable($table) || !$sb->hasColumn($table, 'tables')) {
                continue;
            }
            foreach ($db->table($table)->whereNotNull('tables')->get(['id', 'tables']) as $row) {
                $json = $rewrite($row->tables);
                if ($json !== null) {
                    $db->table($table)->where('id', $row->id)->update(['tables' => $json]);
                }
            }
        }
        if ($sb->hasTable('settings')) {
            foreach ($db->table('settings')->where('key', 'tables')->get(['id', 'value']) as $row) {
                $json = $rewrite($row->value);
                if ($json !== null) {
                    $db->table('settings')->where('id', $row->id)->update(['value' => $json]);
                }
            }
        }
    }
}
