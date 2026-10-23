<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\RouteProfitService;
use Illuminate\Console\Command;

class InstallRouteProfitField extends Command
{
    protected $signature = 'routes:install-profit-field
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Добавить в маршруты поле «% прибыли» (Заложено на доставку vs Стоимость доставки) и заполнить его';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds' || $target === 'all-tenants') {
            $this->install(\DB::connection('seeds'), 'admin_seeds');
        }
        if ($target === 'seeds') {
            return self::SUCCESS;
        }
        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target));

        return self::SUCCESS;
    }

    private function install($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $dry = (bool) $this->option('dry-run');
        $field = RouteProfitService::FIELD;

        $typeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$typeId || !$sb->hasTable('routes') || !$sb->hasColumn('routes', RouteProfitService::PLANNED) || !$sb->hasColumn('routes', RouteProfitService::ACTUAL)) {
            $this->warn("    [{$label}] нет маршрутов или полей стоимости, пропуск");

            return;
        }

        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        if ($dry) {
            $this->line("    [{$label}] " . ($existing ? "поле уже есть (id {$existing->id}), будет обновлено и пересчитано" : 'будет создано поле routes.' . $field));

            return;
        }

        if (!$sb->hasColumn('routes', $field)) {
            $db->statement("ALTER TABLE `routes` ADD COLUMN `{$field}` DECIMAL(10,2) NULL");
        }

        $attrs = [
            'type' => 'number',
            'title' => RouteProfitService::TITLE,
            'unit' => '%',
            'only_read' => 1,
            'is_program' => 1,
            'is_remove' => 0,
            'is_permanent' => 1,
        ];
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            $id = $existing->id;
        } else {
            $actual = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', RouteProfitService::ACTUAL)->where('is_remove', 0)->first(['section_id', 'group_id', 'sort']);
            $sort = $actual ? (int) $actual->sort + 1 : (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1;
            $id = $db->table('data_rows')->insertGetId($attrs + [
                'data_type_id' => $typeId, 'field' => $field, 'required' => 0, 'details' => '', 'visible_always' => 1,
                'label_color' => '', 'section_id' => $actual->section_id ?? null, 'group_id' => $actual->group_id ?? null,
                'sort' => $sort, 'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0,
                'roles_read' => '', 'roles_write' => '', 'mobile_pages' => '', 'show_file_name' => 0, 'external_link' => '',
                'is_external_link' => 0, 'module' => '', 'is_link' => 0, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0, 'relation_table' => null,
                'set_color' => 0, 'related_field' => null, 'is_unique' => 0,
            ]);
        }

        $filled = $db->update(RouteProfitService::backfillSql());
        $this->line("    [{$label}] поле «" . RouteProfitService::TITLE . "» id {$id}" . ($existing ? ' (обновлено)' : ' (создано)') . ", пересчитано маршрутов: {$filled}");

        RouteProfitService::forget();
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/routes')->update(['updated_at' => now()]);
            }
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
