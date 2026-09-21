<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReverseLinkService
{
    public static function ids($value): array
    {
        return RelationFieldsService::ids($value);
    }

    public static function sync(string $table, string $column, int $ownerId, array $oldIds, array $newIds): void
    {
        try {
            if (!$ownerId || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return;
            }
            $oldIds = array_values(array_unique(array_map('intval', $oldIds)));
            $newIds = array_values(array_unique(array_map('intval', $newIds)));
            $removed = array_diff($oldIds, $newIds);
            $added = array_diff($newIds, $oldIds);
            $touched = array_values(array_unique(array_merge($removed, $added)));
            if (!count($touched)) {
                return;
            }
            foreach (DB::table($table)->whereIn('id', $touched)->get(['id', $column]) as $row) {
                $current = self::ids($row->{$column});
                $next = in_array((int) $row->id, $added, true)
                    ? array_values(array_unique(array_merge($current, [$ownerId])))
                    : array_values(array_filter($current, fn ($v) => (int) $v !== $ownerId));
                if ($next !== $current) {
                    DB::table($table)->where('id', $row->id)->update([$column => json_encode($next)]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('reverse link sync failed', ['table' => $table, 'column' => $column, 'owner' => $ownerId, 'error' => $e->getMessage()]);
        }
    }

    public static function installField($db, string $entity, string $field, string $title, string $targetSlug): ?string
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
        if (!$typeId || !$sb->hasTable($entity)) {
            return null;
        }
        if (!$sb->hasColumn($entity, $field)) {
            $db->statement("ALTER TABLE `{$entity}` ADD COLUMN `{$field}` TEXT NULL");
        }

        $attrs = [
            'type' => 'relation', 'title' => $title, 'details' => '{"table":"' . $targetSlug . '"}',
            'is_link' => 1, 'is_plural' => 1, 'relation_table' => $targetSlug, 'only_read' => 1,
            'is_permanent' => 1, 'is_remove' => 0, 'hide' => 0,
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        $result = 'updated';
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
        } else {
            $sectionId = (int) ($db->table('field_sections')
                ->where('page', $entity)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id') ?: 0);
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(\App\Console\Commands\InstallSaleDocsEntities::baseRow((int) $typeId, $sectionId), $attrs, [
                'field' => $field, 'sort' => $maxSort + 1,
            ]));
            $result = 'created';
        }

        foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs) || collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === $field)) {
                continue;
            }
            $maxId = 0;
            foreach ($tabs as $tab) {
                $maxId = max($maxId, (int) ($tab['id'] ?? 0));
            }
            $newTab = ['title' => $title, 'tab' => $field, 'slug' => $targetSlug, 'sort' => 0, 'enabled' => 1, 'id' => $maxId + 1, 'roles_read' => null];
            $ordered = [];
            $inserted = false;
            foreach ($tabs as $tab) {
                if (!$inserted && in_array($tab['tab'] ?? null, ['history', 'modules'], true)) {
                    $ordered[] = $newTab;
                    $inserted = true;
                }
                $ordered[] = $tab;
            }
            if (!$inserted) {
                $ordered[] = $newTab;
            }
            foreach ($ordered as $i => $tab) {
                $ordered[$i]['sort'] = $i;
            }
            $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($ordered, JSON_UNESCAPED_SLASHES)]);
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $entity)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $result;
    }

    public static function removeField($db, string $entity, string $field): int
    {
        $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
        $removed = 0;
        if ($typeId) {
            $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->pluck('id');
            if ($ids->count()) {
                $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                $db->table('data_rows')->whereIn('id', $ids)->delete();
                $removed = $ids->count();
            }
        }
        foreach ($db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get() as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }
            $kept = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== $field));
            if (count($kept) !== count($tabs)) {
                $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($kept, JSON_UNESCAPED_SLASHES)]);
            }
        }
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $entity)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        return $removed;
    }

    public static function backfill($db, string $table, string $column, array $map): int
    {
        if (!$db->getSchemaBuilder()->hasColumn($table, $column)) {
            return 0;
        }
        foreach ($map as $id => $ids) {
            $db->table($table)->where('id', $id)->update([$column => json_encode(array_values(array_unique(array_map('intval', $ids))))]);
        }

        return count($map);
    }
}
