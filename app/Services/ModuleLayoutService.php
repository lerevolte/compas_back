<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ModuleLayoutService
{
    public const SEEDS_TENANT = 'seeds';

    public static function isSeeds(): bool
    {
        return (string) tenant('id') === self::SEEDS_TENANT;
    }

    public static function decodeList($value): array
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

    public static function ensureModuleRow($db, string $slug, string $name): void
    {
        if ($db->table('modules')->where('slug', $slug)->exists()) {
            $db->table('modules')->where('slug', $slug)->update(['enabled' => 1]);
            return;
        }
        $db->table('modules')->insert([
            'name' => $name,
            'config' => '',
            'entities' => '',
            'slug' => $slug,
            'enabled' => 1,
        ]);
    }

    public static function ensureSection($db, string $page, string $module, string $name, int $columnId = 1, ?int $sort = null): int
    {
        $existing = $db->table('field_sections')
            ->where('page', $page)
            ->where('module', $module)
            ->where('name', $name)
            ->orderBy('id')
            ->first();
        if ($existing) {
            $patch = [];
            if ((int) $existing->column_id !== $columnId) {
                $patch['column_id'] = $columnId;
            }
            if ($sort !== null && (int) $existing->sort !== $sort) {
                $patch['sort'] = $sort;
            }
            if (count($patch)) {
                $db->table('field_sections')->where('id', $existing->id)->update($patch);
            }

            return (int) $existing->id;
        }
        $now = now();
        if ($sort === null) {
            $sort = (int) $db->table('field_sections')->where('page', $page)->where('module', $module)->where('column_id', $columnId)->max('sort') + 1;
        }

        return (int) $db->table('field_sections')->insertGetId([
            'sort' => $sort, 'name' => $name, 'domain_key' => null, 'page' => $page,
            'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
            'column_id' => $columnId, 'module' => $module, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => 0,
        ]);
    }

    public static function moduleSectionIds($db, string $page, string $module): array
    {
        return array_map('intval', $db->table('field_sections')->where('page', $page)->where('module', $module)->pluck('id')->all());
    }

    public static function attachField($db, $row, string $module, int $sectionId, bool $exclusiveInModule = true): bool
    {
        $modules = self::decodeList($row->module);
        $sections = array_map('intval', self::decodeList($row->module_section_id));
        $changed = false;

        if ($exclusiveInModule) {
            $typeSlug = $db->table('data_types')->where('id', $row->data_type_id)->value('slug');
            $own = $typeSlug ? self::moduleSectionIds($db, $typeSlug, $module) : [];
            $filtered = array_values(array_filter($sections, fn ($s) => $s === $sectionId || !in_array($s, $own, true)));
            if ($filtered !== $sections) {
                $sections = $filtered;
                $changed = true;
            }
        }
        if (!in_array($module, $modules, true)) {
            $modules[] = $module;
            $changed = true;
        }
        if (!in_array($sectionId, $sections, true)) {
            $sections[] = $sectionId;
            $changed = true;
        }
        if ($changed) {
            $db->table('data_rows')->where('id', $row->id)->update([
                'module' => json_encode(array_values($modules)),
                'module_section_id' => json_encode(array_values($sections)),
            ]);
        }

        return $changed;
    }

    public static function detachField($db, $row, string $module, ?int $sectionId = null): bool
    {
        $modules = self::decodeList($row->module);
        $sections = array_map('intval', self::decodeList($row->module_section_id));
        $typeSlug = $db->table('data_types')->where('id', $row->data_type_id)->value('slug');
        $own = $typeSlug ? self::moduleSectionIds($db, $typeSlug, $module) : [];

        $filtered = array_values(array_filter($sections, function ($s) use ($sectionId, $own) {
            if ($sectionId !== null) {
                return $s !== $sectionId;
            }

            return !in_array($s, $own, true);
        }));
        $stillInModule = (bool) array_intersect($filtered, $own);
        $newModules = $stillInModule ? $modules : array_values(array_filter($modules, fn ($m) => $m !== $module));
        if ($filtered === $sections && $newModules === $modules) {
            return false;
        }
        $db->table('data_rows')->where('id', $row->id)->update([
            'module' => count($newModules) ? json_encode($newModules) : null,
            'module_section_id' => count($filtered) ? json_encode($filtered) : null,
        ]);
        $db->table('section_fields_sort')->where('field_id', $row->id)->whereIn('section_id', $sectionId !== null ? [$sectionId] : $own)->delete();

        return true;
    }

    public static function setSectionOrder($db, int $sectionId, array $fieldIds): void
    {
        $db->table('section_fields_sort')->where('section_id', $sectionId)->delete();
        $insert = [];
        foreach (array_values($fieldIds) as $i => $fieldId) {
            $insert[] = ['section_id' => $sectionId, 'field_id' => (int) $fieldId, 'sort' => $i];
        }
        if (count($insert)) {
            $db->table('section_fields_sort')->insert($insert);
        }
    }

    public static function sectionFieldIds($db, int $sectionId): array
    {
        $sorted = array_map('intval', $db->table('section_fields_sort')->where('section_id', $sectionId)->orderBy('sort')->pluck('field_id')->all());
        $rows = $db->table('data_rows')->whereJsonContains('module_section_id', $sectionId)->where('is_remove', 0)->orderBy('sort')->get(['id', 'field']);
        $ids = array_map('intval', $rows->pluck('id')->all());
        $ordered = array_values(array_filter($sorted, fn ($id) => in_array($id, $ids, true)));
        foreach ($ids as $id) {
            if (!in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        return $ordered;
    }

    public static function ensureMenuChild($db, string $slug, string $alias, string $title): void
    {
        $child = ['title' => $title, 'sort' => 1, 'enabled' => 1, 'id' => 0, 'alias' => $alias];
        $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->get();

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
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 2, 'enabled' => true, 'id' => 2, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => $slug, 'user_id' => null,
            ]);
            return;
        }

        foreach ($menus as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                $tabs = [];
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
                $has = false;
                foreach ($childs as $ck => $item) {
                    if (($item['alias'] ?? null) === $alias) {
                        $childs[$ck]['enabled'] = 1;
                        $childs[$ck]['title'] = $title;
                        $has = true;
                    }
                }
                if (!$has) {
                    $child['sort'] = count($childs) + 1;
                    $childs[] = $child;
                }
                $tabs[$modulesKey]['childs'] = array_values($childs);
            }
            $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($tabs, JSON_UNESCAPED_SLASHES)]);
        }
    }

    public static function layout($db, string $page, string $module): array
    {
        $result = [];
        $sections = $db->table('field_sections')
            ->where('page', $page)
            ->where('module', $module)
            ->orderBy('column_id')
            ->orderBy('sort')
            ->get();
        $typeId = $db->table('data_types')->where('slug', $page)->value('id');
        foreach ($sections as $section) {
            $fieldIds = self::sectionFieldIds($db, (int) $section->id);
            $fields = [];
            if (count($fieldIds)) {
                $rows = $db->table('data_rows')->whereIn('id', $fieldIds)->where('data_type_id', $typeId)->get(['id', 'field'])->keyBy('id');
                foreach ($fieldIds as $fid) {
                    if (isset($rows[$fid])) {
                        $fields[] = (string) $rows[$fid]->field;
                    }
                }
            }
            $result[] = [
                'name' => (string) $section->name,
                'column_id' => (int) $section->column_id,
                'sort' => (int) $section->sort,
                'is_short' => (int) ($section->is_short ?? 0),
                'fields' => $fields,
            ];
        }

        return $result;
    }

    public static function applyLayout($db, string $page, string $module, array $layout): array
    {
        $typeId = $db->table('data_types')->where('slug', $page)->value('id');
        if (!$typeId) {
            return ['skipped' => 'нет сущности ' . $page];
        }
        $keptSections = [];
        $keptFields = [];
        $missing = [];
        foreach ($layout as $item) {
            $sectionId = self::ensureSection($db, $page, $module, $item['name'], (int) $item['column_id'], (int) $item['sort']);
            $db->table('field_sections')->where('id', $sectionId)->update(['is_short' => (int) ($item['is_short'] ?? 0)]);
            $keptSections[] = $sectionId;
            $ordered = [];
            foreach ($item['fields'] as $fieldName) {
                $row = $db->table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', $fieldName)
                    ->where('is_remove', 0)
                    ->first();
                if (!$row) {
                    $missing[] = $fieldName;
                    continue;
                }
                self::attachField($db, $row, $module, $sectionId, true);
                $ordered[] = (int) $row->id;
                $keptFields[] = (int) $row->id;
            }
            self::setSectionOrder($db, $sectionId, $ordered);
        }

        $extraSections = $db->table('field_sections')
            ->where('page', $page)
            ->where('module', $module)
            ->whereNotIn('id', $keptSections ?: [0])
            ->pluck('id');
        foreach ($extraSections as $sid) {
            foreach ($db->table('data_rows')->whereJsonContains('module_section_id', (int) $sid)->get() as $row) {
                self::detachField($db, $row, $module, (int) $sid);
            }
            $db->table('section_fields_sort')->where('section_id', $sid)->delete();
            $db->table('field_sections')->where('id', $sid)->delete();
        }

        foreach ($keptSections as $sid) {
            $rows = $db->table('data_rows')->whereJsonContains('module_section_id', $sid)->whereNotIn('id', $keptFields ?: [0])->get();
            foreach ($rows as $row) {
                self::detachField($db, $row, $module, $sid);
            }
        }

        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $page)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return ['sections' => count($keptSections), 'fields' => count($keptFields), 'missing' => array_values(array_unique($missing))];
    }

    public static function syncFromSeeds(string $module, string $page, ?array $tenantIds = null): array
    {
        $seeds = DB::connection('seeds');
        $layout = self::layout($seeds, $page, $module);
        $report = [];
        $tenants = $tenantIds === null ? Tenant::get() : Tenant::whereIn('id', $tenantIds)->get();
        foreach ($tenants as $tenant) {
            if ((string) $tenant->id === self::SEEDS_TENANT) {
                continue;
            }
            try {
                $tenant->run(function () use ($page, $module, $layout, &$report, $tenant) {
                    $report[(string) $tenant->id] = self::applyLayout(DB::connection(), $page, $module, $layout);
                    try {
                        \App\Models\Settings::clear_cache();
                    } catch (\Throwable $e) {
                    }
                });
            } catch (\Throwable $e) {
                $report[(string) $tenant->id] = ['error' => $e->getMessage()];
            }
        }

        return $report;
    }

    public static function queueSyncFromSeeds(string $module, string $page): void
    {
        if (!self::isSeeds()) {
            return;
        }
        try {
            \App\Jobs\SyncModuleLayoutFromSeeds::dispatch($module, $page);
        } catch (\Throwable $e) {
            \Log::warning('module layout: синхронизация из seeds не поставлена в очередь', ['module' => $module, 'page' => $page, 'error' => $e->getMessage()]);
        }
    }
}
