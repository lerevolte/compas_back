<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\StorehouseService;
use Illuminate\Console\Command;

class InstallStorehouses extends Command
{
    protected $signature = 'entity:install-storehouses
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план конвертации без изменений}';

    protected $description = 'Сущность «Склады»: «Склад отгрузки» (список) → relation «Склад списания» со складами из вариантов списка; поля «Склад списания»/«Склад прихода» у задач, самовывозов, накладных и возвратов';

    public const SLUG = StorehouseService::TABLE;
    public const MODEL = 'App\\Models\\Storehouse';
    public const LEGACY_TITLES = ['Склад отгрузки', 'Склад списания'];

    public static function ensureTable($db): void
    {
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `storehouses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `name` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `color` varchar(191) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
                $this->info("  ✓ {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $dry = (bool) $this->option('dry-run');
        if (!$dry) {
            self::ensureTable($db);
            $this->installEntity($db, $label);
        }

        $this->convertLegacy($db, $label, $dry);

        if (!$dry) {
            foreach (StorehouseService::FIELDS as $slug => $fields) {
                foreach ($fields as $field) {
                    $this->ensureField($db, $label, $slug, $field);
                }
            }
            $this->clearCache($db, $inTenant);
        }
    }

    private function installEntity($db, string $label): void
    {
        $now = now();
        $type = $db->table('data_types')->where('slug', self::SLUG)->first();
        $attrs = [
            'name' => self::SLUG,
            'slug' => self::SLUG,
            'title_singular' => 'Склад',
            'title_plural' => 'Склады',
            'model_name' => self::MODEL,
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => '#8E7CC3',
            'enable' => 1,
            'slug_singular' => 'storehouse',
            'hidden' => 0,
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($attrs);
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) $db->table('data_types')->insertGetId($attrs + ['created_at' => $now]);
        }

        $sectionId = $db->table('field_sections')->where('page', self::SLUG)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')->value('id');
        if (!$sectionId) {
            $sectionId = $db->table('field_sections')->insertGetId([
                'sort' => 0, 'name' => 'Информация', 'page' => self::SLUG,
                'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
                'column_id' => 1, '_lft' => 0, '_rgt' => 0,
            ]);
        }

        $base = $this->baseRow($typeId, (int) $sectionId);
        $rows = [
            ['field' => 'id', 'type' => 'number', 'title' => 'ID', 'sort' => 0, 'only_read' => 1, 'is_default' => 1, 'is_program' => 1, 'is_permanent' => 1],
            ['field' => 'created_at', 'type' => 'date', 'title' => 'Дата создания', 'sort' => 1, 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0', 'is_permanent' => 1],
            ['field' => 'updated_at', 'type' => 'date', 'title' => 'Дата изменения', 'sort' => 2, 'only_read' => 1, 'is_default' => 1, 'mobile_pages' => '0', 'is_permanent' => 1],
            ['field' => 'name', 'type' => 'text', 'title' => 'Название', 'sort' => 3, 'is_default' => 1, 'permanent_name' => 1, 'is_permanent' => 1],
            ['field' => 'address', 'type' => 'text', 'title' => 'Адрес', 'sort' => 4],
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->pluck('field')->all();
        foreach ($rows as $row) {
            if (!in_array($row['field'], $existing, true)) {
                $db->table('data_rows')->insert(array_merge($base, $row));
            }
        }

        if (!$db->table('settings')->where(['type' => 'menu', 'entity' => self::SLUG])->exists()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => self::SLUG, 'user_id' => null,
            ]);
        }

        if ($db->getSchemaBuilder()->hasTable('sidebar_items') && !$db->table('sidebar_items')->where('slug', self::SLUG)->exists()) {
            $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
            $db->table('sidebar_items')->insert([
                'created_at' => $now, 'updated_at' => $now,
                'name' => 'Склады', 'slug' => self::SLUG,
                'sort' => 0, 'link' => '/objects/' . self::SLUG,
                '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                'is_hidden' => 0, 'enabled' => 1,
            ]);
        }

        $this->line("    [{$label}] storehouses: data_type={$typeId}");
    }

    private function convertLegacy($db, string $label, bool $dry): void
    {
        $sb = $db->getSchemaBuilder();
        $types = $db->table('data_types')->where('slug', '!=', self::SLUG)->get(['id', 'slug'])->keyBy('id');
        $legacyRows = $db->table('data_rows')
            ->whereIn('title', self::LEGACY_TITLES)
            ->whereIn('type', ['select_dropdown', 'status'])
            ->where('is_remove', 0)
            ->get();

        foreach ($legacyRows as $row) {
            $slug = $types[$row->data_type_id]->slug ?? null;
            if (!$slug || !$sb->hasTable($slug)) {
                continue;
            }
            $hasTarget = $db->table('data_rows')->where('data_type_id', $row->data_type_id)
                ->where('field', StorehouseService::WRITE_OFF)->where('id', '!=', $row->id)->exists();
            if ($hasTarget) {
                $this->warn("    [{$label}] {$slug}: уже есть поле " . StorehouseService::WRITE_OFF . ", «{$row->title}» (id {$row->id}) пропущено");
                continue;
            }

            $options = $this->legacyOptions($db, $row);
            if ($dry) {
                $this->line("    [{$label}] {$slug}.{$row->field} ({$row->type}): складов " . count($options) . ' — ' . implode(', ', array_values($options)));
                continue;
            }

            $map = [];
            foreach ($options as $value => $name) {
                $map[(string) $value] = $this->storehouseId($db, $name);
            }

            if (!$sb->hasColumn($slug, StorehouseService::WRITE_OFF)) {
                $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . StorehouseService::WRITE_OFF . '` TEXT NULL');
            }
            $filled = 0;
            if ($row->field !== StorehouseService::WRITE_OFF && $sb->hasColumn($slug, $row->field)) {
                $db->table($slug)->whereNotNull($row->field)->where($row->field, '!=', '')->orderBy('id')
                    ->select(['id', $row->field])
                    ->chunk(500, function ($items) use ($db, $slug, $row, $map, &$filled) {
                        foreach ($items as $item) {
                            $raw = $item->{$row->field};
                            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                            if (is_array($decoded)) {
                                $raw = array_key_exists('value', $decoded) ? $decoded['value'] : (array_values($decoded)[0] ?? null);
                            }
                            $storehouseId = $map[(string) $raw] ?? null;
                            if (!$storehouseId) {
                                continue;
                            }
                            $filled += $db->table($slug)->where('id', $item->id)
                                ->where(fn ($q) => $q->whereNull(StorehouseService::WRITE_OFF)->orWhere(StorehouseService::WRITE_OFF, ''))
                                ->update([StorehouseService::WRITE_OFF => $storehouseId]);
                        }
                    });
            }

            $db->table('data_rows')->where('id', $row->id)->update([
                'field' => StorehouseService::WRITE_OFF,
                'type' => 'relation',
                'title' => StorehouseService::TITLES[StorehouseService::WRITE_OFF],
                'details' => '{"table":"' . self::SLUG . '"}',
                'relation_table' => self::SLUG,
                'related_field' => null,
                'is_plural' => 0,
                'is_link' => 1,
            ]);
            if ($row->type === 'status') {
                $db->table('field_values')->where('field_id', $row->id)->delete();
            }
            $this->line("    [{$label}] {$slug}: «{$row->title}» ({$row->field}) → relation " . StorehouseService::WRITE_OFF . ', складов ' . count($map) . ", заполнено {$filled}");
        }
    }

    private function legacyOptions($db, object $row): array
    {
        $options = [];
        if ($row->type === 'status') {
            foreach ($db->table('field_values')->where('field_id', $row->id)->orderBy('sort')->get(['id', 'value']) as $value) {
                $name = trim((string) $value->value);
                if ($name !== '') {
                    $options[(string) $value->id] = $name;
                }
            }
            return $options;
        }
        $details = json_decode((string) $row->details, true);
        foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $option) {
            if (!is_array($option) || !array_key_exists('value', $option)) {
                continue;
            }
            $text = $option['label'] ?? '';
            $text = is_array($text) ? ($text['text'] ?? '') : $text;
            $name = trim((string) $text);
            if ($name !== '') {
                $options[(string) $option['value']] = $name;
            }
        }
        return $options;
    }

    private function storehouseId($db, string $name): int
    {
        $id = $db->table(self::SLUG)->whereNull('deleted_at')->where('name', $name)->value('id');
        if ($id) {
            return (int) $id;
        }
        return (int) $db->table(self::SLUG)->insertGetId([
            'name' => $name,
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureField($db, string $label, string $slug, string $field): void
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId || !$sb->hasTable($slug)) {
            return;
        }
        if (!$sb->hasColumn($slug, $field)) {
            $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . $field . '` TEXT NULL');
        }
        $attrs = [
            'type' => 'relation',
            'title' => StorehouseService::TITLES[$field],
            'details' => '{"table":"' . self::SLUG . '"}',
            'relation_table' => self::SLUG,
            'related_field' => null,
            'is_plural' => 0,
            'is_link' => 1,
            'is_remove' => 0,
            'hide' => 0,
            'visible_always' => 1,
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            return;
        }
        $sectionId = (int) $db->table('field_sections')->where('page', $slug)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')->value('id');
        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
        $id = $db->table('data_rows')->insertGetId(array_merge($this->baseRow((int) $typeId, $sectionId), $attrs, [
            'field' => $field,
            'sort' => $maxSort + 1,
        ]));
        $this->line("    [{$label}] {$slug}: добавлено поле «" . StorehouseService::TITLES[$field] . "» (id {$id})");
    }

    private function baseRow(int $typeId, int $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => 0,
            'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 0, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 0, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0,
        ];
    }

    private function clearCache($db, bool $inTenant): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'like', 'fields/%')->update(['updated_at' => now()]);
                $db->table('local_cache')->where('url', 'sidebar')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
