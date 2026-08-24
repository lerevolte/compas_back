<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallGeoposition extends Command
{
    public const MODULE_SLUG = 'geoposition';
    public const MODULE_NAME = 'Геопозиция';

    protected $signature = 'geo:install
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--remove : убрать модуль и поле из data_rows (таблица и данные сохраняются)}';

    protected $description = 'Установить модуль геопозиции: таблица user_geopositions, колонка users.geoposition, поле «Геопозиция» и вкладка «Модули» в карточке пользователя';

    public function handle(): int
    {
        $target = $this->argument('target');

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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();

        $dataType = $db->table('data_types')->where('slug', 'users')->first();
        if (!$dataType || !$sb->hasTable('users')) {
            $this->warn("    [{$label}] сущность users не найдена, пропуск");
            return;
        }

        if ($this->option('remove')) {
            $this->removeModuleTab($db, $label);
            $deleted = $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->where('field', 'geoposition')
                ->delete();
            if ($deleted) {
                $this->line("    [{$label}] поле geoposition убрано из data_rows");
            }
            $this->clearCache($db, $inTenant);
            return;
        }

        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `user_geopositions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `accuracy` double DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `heading` double DEFAULT NULL,
  `provider` varchar(32) DEFAULT NULL,
  `is_mock` tinyint(1) DEFAULT NULL,
  `satellites` int(11) DEFAULT NULL,
  `gps_time` bigint(20) DEFAULT NULL,
  `client_time` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_geopositions_user_id_id_index` (`user_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        if (!$sb->hasColumn('users', 'geoposition')) {
            $db->statement("ALTER TABLE `users` ADD COLUMN `geoposition` TEXT NULL");
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', 'geoposition')
            ->first();

        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update([
                'type' => 'geoposition',
                'title' => 'Геопозиция',
                'only_read' => 1,
                'hide' => 0,
            ]);
            $fieldId = (int) $existing->id;
            $this->line("    [{$label}] поле geoposition обновлено (id {$existing->id})");
        } else {
            $sectionId = $db->table('field_sections')
                ->where('page', 'users')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');

            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->max('sort');

            $id = $db->table('data_rows')->insertGetId([
                'data_type_id' => $dataType->id,
                'field' => 'geoposition',
                'type' => 'geoposition',
                'title' => 'Геопозиция',
                'required' => 0,
                'visible_always' => 1,
                'section_id' => $sectionId,
                'hide' => $sectionId ? 0 : 1,
                'sort' => $maxSort + 1,
                'only_read' => 1,
                'is_permanent' => 1,
            ]);
            $fieldId = (int) $id;
            $this->line("    [{$label}] создано поле geoposition (id {$id})");
        }

        $this->installModuleTab($db, $label, $fieldId);

        $this->clearCache($db, $inTenant);
    }

    private function installModuleTab($db, string $label, int $fieldId): void
    {
        $now = now();

        if (!$db->table('modules')->where('slug', self::MODULE_SLUG)->exists()) {
            $db->table('modules')->insert([
                'name' => self::MODULE_NAME,
                'config' => '',
                'entities' => '',
                'slug' => self::MODULE_SLUG,
                'enabled' => 1,
            ]);
            $this->line("    [{$label}] добавлена запись модуля " . self::MODULE_SLUG . " в modules");
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'users')
            ->where('module', self::MODULE_SLUG)
            ->value('id');

        if (!$sectionId) {
            $sectionId = $db->table('field_sections')->insertGetId([
                'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => 'users',
                'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
                'column_id' => 1, 'module' => self::MODULE_SLUG, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
            ]);
        }

        $row = $db->table('data_rows')->where('id', $fieldId)->first(['id', 'module', 'module_section_id']);
        if ($row) {
            $modules = $this->decodeJsonList($row->module);
            $sections = array_map('intval', $this->decodeJsonList($row->module_section_id));

            $changed = false;
            if (!in_array(self::MODULE_SLUG, $modules, true)) {
                $modules[] = self::MODULE_SLUG;
                $changed = true;
            }
            if (!in_array((int) $sectionId, $sections, true)) {
                $sections[] = (int) $sectionId;
                $changed = true;
            }

            if ($changed) {
                $db->table('data_rows')->where('id', $row->id)->update([
                    'module' => json_encode(array_values($modules)),
                    'module_section_id' => json_encode(array_values($sections)),
                ]);
            }
        }

        $db->table('section_fields_sort')->where('section_id', $sectionId)->delete();
        $db->table('section_fields_sort')->insert([
            'section_id' => $sectionId, 'field_id' => $fieldId, 'sort' => 0,
        ]);

        $this->syncMenu($db);

        $this->line("    [{$label}] секция модуля #{$sectionId}, вкладка «Модули» в users");
    }

    private function syncMenu($db): void
    {
        $child = ['title' => self::MODULE_NAME, 'sort' => 2, 'enabled' => 1, 'id' => 0, 'alias' => self::MODULE_SLUG];

        $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => 'users'])->get();

        if ($menus->isEmpty()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    [
                        'title' => 'Модули', 'tab' => 'modules', 'sort' => 1, 'enabled' => 1, 'id' => 1,
                        'childs' => [$child],
                        'component' => ['name' => 'AsyncComponentWrapper'],
                        'roles_read' => [], 'has_roles_read' => false,
                    ],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => 'users', 'user_id' => null,
            ]);
            return;
        }

        foreach ($menus as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }

            $modulesKey = null;
            foreach ($tabs as $k => $tab) {
                if (($tab['tab'] ?? null) === 'modules') {
                    $modulesKey = $k;
                    break;
                }
            }

            if ($modulesKey === null) {
                $maxSort = 0;
                $maxId = 0;
                foreach ($tabs as $tab) {
                    $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                    $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                }
                $tabs[] = [
                    'title' => 'Модули', 'tab' => 'modules', 'sort' => $maxSort + 1, 'enabled' => 1, 'id' => $maxId + 1,
                    'childs' => [$child],
                    'component' => ['name' => 'AsyncComponentWrapper'],
                    'roles_read' => [], 'has_roles_read' => false,
                ];
            } else {
                $tabs[$modulesKey]['enabled'] = 1;
                $childs = $tabs[$modulesKey]['childs'] ?? [];
                $exists = false;
                foreach ($childs as $ck => $item) {
                    if (($item['alias'] ?? null) === self::MODULE_SLUG) {
                        $childs[$ck]['enabled'] = 1;
                        $childs[$ck]['title'] = self::MODULE_NAME;
                        $exists = true;
                    }
                }
                if (!$exists) {
                    $childs[] = $child;
                }
                $tabs[$modulesKey]['childs'] = array_values($childs);
            }

            $db->table('settings')->where('id', $menu->id)->update([
                'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    private function removeModuleTab($db, string $label): void
    {
        $slug = self::MODULE_SLUG;

        $sections = $db->table('field_sections')->where('module', $slug)->get(['id', 'page']);
        $sectionIds = $sections->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($sectionIds)) {
            $rows = $db->table('data_rows')
                ->where(function ($q) use ($slug) {
                    $q->where('module', 'LIKE', "%\"{$slug}\"%")->orWhere('module', $slug);
                })
                ->orWhere(function ($q) use ($sectionIds) {
                    foreach ($sectionIds as $id) {
                        $q->orWhere('module_section_id', 'LIKE', "%{$id}%");
                    }
                })
                ->get(['id', 'module', 'module_section_id']);

            $detached = 0;
            foreach ($rows as $row) {
                $modules = $this->decodeJsonList($row->module);
                $rowSections = array_map('intval', $this->decodeJsonList($row->module_section_id));

                $newSections = array_values(array_diff($rowSections, $sectionIds));
                $newModules = array_values(array_filter($modules, fn ($m) => $m !== $slug));

                if ($newSections === $rowSections && $newModules === $modules) {
                    continue;
                }

                $db->table('data_rows')->where('id', $row->id)->update([
                    'module' => $newModules ? json_encode($newModules) : null,
                    'module_section_id' => $newSections ? json_encode($newSections) : null,
                ]);
                $detached++;
            }

            $db->table('section_fields_sort')->whereIn('section_id', $sectionIds)->delete();
            $db->table('field_sections')->whereIn('id', $sectionIds)->delete();

            $this->line("    [{$label}] отвязано полей: {$detached}, удалено секций модуля: " . count($sectionIds));
        }

        foreach ($db->table('settings')->where('type', 'menu')->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }

            $changed = false;
            foreach ($tabs as $k => $tab) {
                if (($tab['tab'] ?? null) !== 'modules' || !isset($tab['childs'])) {
                    continue;
                }
                $childs = array_values(array_filter($tab['childs'], fn ($item) => ($item['alias'] ?? null) !== $slug));
                if (count($childs) !== count($tab['childs'])) {
                    $tabs[$k]['childs'] = $childs;
                    $changed = true;
                }
            }

            if ($changed) {
                $db->table('settings')->where('id', $menu->id)->update([
                    'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        $db->table('modules')->where('slug', $slug)->delete();
    }

    private function decodeJsonList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($v) => $v !== null && $v !== ''));
        }

        return [(string) $value];
    }

    private function clearCache($db, bool $inTenant): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/users')->update(['updated_at' => now()]);
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
