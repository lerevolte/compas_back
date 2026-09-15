<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ModuleLayoutService;
use App\Services\RelationFieldsService;
use Illuminate\Console\Command;

class InstallRelationsModule extends Command
{
    protected $signature = 'relations:install-module
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--no-backfill : не заполнять поля из существующих связей}';

    protected $description = 'Установить модуль «Связанные документы»: раздел и поля-связи у документов (заказы, задачи, самовывозы, счета, отгрузки, оприходования, адреса, быстрые задачи), вкладка «Модули», заполнение из object_relations';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    public function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        RelationFieldsService::forgetCache();

        $present = [];
        foreach (RelationFieldsService::ENTITIES as $slug) {
            $type = $db->table('data_types')->where('slug', $slug)->first(['id', 'title_singular', 'title_plural']);
            if ($type && $sb->hasTable($slug)) {
                $present[$slug] = $type;
            }
        }
        if (count($present) < 2) {
            $this->line("    [{$label}] документных сущностей меньше двух, пропуск");
            return;
        }

        ModuleLayoutService::ensureModuleRow($db, RelationFieldsService::MODULE, RelationFieldsService::MODULE_TITLE);

        foreach ($present as $slug => $type) {
            $sectionId = ModuleLayoutService::ensureSection($db, $slug, RelationFieldsService::MODULE, RelationFieldsService::SECTION_NAME, 1, 0);
            $ordered = [];
            $created = 0;
            foreach (RelationFieldsService::ENTITIES as $target) {
                if ($target === $slug || !isset($present[$target])) {
                    continue;
                }
                $field = RelationFieldsService::field($slug, $target);
                $single = RelationFieldsService::isSingle($slug, $target);
                if (!$sb->hasColumn($slug, $field)) {
                    $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$field}` " . ($single ? 'INT NULL' : 'TEXT NULL'));
                }
                $title = $single ? (string) $present[$target]->title_singular : (string) $present[$target]->title_plural;
                if ($slug === 'logistic_tasks' || $slug === 'pickups') {
                    if ($target === 'deals') {
                        $title = 'Заказ покупателя';
                    }
                }
                $attrs = [
                    'type' => 'relation',
                    'title' => $title,
                    'relation_table' => $target,
                    'details' => json_encode(['table' => $target]),
                    'is_plural' => $single ? 0 : 1,
                    'is_link' => 1,
                    'is_remove' => 0,
                    'hide' => 0,
                    'only_read' => 0,
                    'is_permanent' => 1,
                    'section_id' => null,
                    'group_id' => null,
                ];
                $row = $db->table('data_rows')->where('data_type_id', $type->id)->where('field', $field)->first();
                if ($row) {
                    $db->table('data_rows')->where('id', $row->id)->update($attrs);
                    $db->table('section_fields_sort')->where('field_id', $row->id)->where('section_id', '!=', $sectionId)->delete();
                } else {
                    $maxSort = (int) $db->table('data_rows')->where('data_type_id', $type->id)->max('sort');
                    $id = $db->table('data_rows')->insertGetId($attrs + [
                        'data_type_id' => $type->id,
                        'field' => $field,
                        'required' => 0,
                        'visible_always' => 1,
                        'sort' => $maxSort + 1,
                        'roles_read' => '',
                        'roles_write' => '',
                        'related_field' => '',
                        'is_default' => 0,
                        'is_program' => 0,
                        'label_color' => '',
                        'button_name' => 'Загрузить',
                        'show_file_image' => 0,
                        'mobile_pages' => '',
                        'show_file_name' => 0,
                        'external_link' => '',
                        'is_external_link' => 0,
                        'unit' => '',
                        'is_inactive' => 0,
                        'blocked_changes' => 0,
                        'permanent_required' => 0,
                        'permanent_name' => 0,
                        'set_color' => 0,
                        'is_unique' => 0,
                    ]);
                    $row = $db->table('data_rows')->where('id', $id)->first();
                    $created++;
                }
                ModuleLayoutService::attachField($db, $row, RelationFieldsService::MODULE, $sectionId, true);
                $ordered[] = (int) $row->id;
            }
            ModuleLayoutService::setSectionOrder($db, $sectionId, $ordered);
            ModuleLayoutService::ensureMenuChild($db, $slug, RelationFieldsService::MODULE, RelationFieldsService::MODULE_TITLE);
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
            $this->line("    [{$label}] {$slug}: раздел #{$sectionId}, полей связей " . count($ordered) . ", новых {$created}");
        }

        RelationFieldsService::forgetCache();

        if (!$this->option('no-backfill') && $sb->hasTable('object_relations')) {
            $objects = RelationFieldsService::objectsWithRelations($db);
            foreach ($objects as [$slug, $id]) {
                RelationFieldsService::refresh($slug, $id, $db);
            }
            $this->line("    [{$label}] заполнено объектов из связей: " . count($objects));
        }

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
