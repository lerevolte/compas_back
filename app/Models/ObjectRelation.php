<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ObjectRelation extends Model
{
    protected $table = 'object_relations';
    protected $guarded = ['id'];

    public const TAB = 'relations';
    public const TAB_TITLE = 'Связанные документы';

    public static function ready(): bool
    {
        try {
            return Schema::hasTable('object_relations');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function link(string $sourceSlug, $sourceId, string $targetSlug, $targetId): void
    {
        if (!self::ready() || !$sourceId || !$targetId) {
            return;
        }

        if ($sourceSlug === $targetSlug && (int) $sourceId === (int) $targetId) {
            return;
        }

        try {
            self::firstOrCreate([
                'source_slug' => $sourceSlug,
                'source_id' => (int) $sourceId,
                'target_slug' => $targetSlug,
                'target_id' => (int) $targetId,
            ], [
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
        }

        self::ensureTab($sourceSlug);
        self::ensureTab($targetSlug);
    }

    public static function ensureTab(string $slug, $db = null, bool $remove = false): bool
    {
        $db = $db ?: \DB::connection();
        $changed = false;

        try {
            if (!$db->table('data_types')->where('slug', $slug)->exists()) {
                return false;
            }

            $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->get();
            foreach ($menus as $menu) {
                $tabs = json_decode($menu->value, true);
                if (!is_array($tabs)) {
                    continue;
                }

                $has = collect($tabs)->contains(fn ($tab) => ($tab['tab'] ?? null) === self::TAB);
                if ($has === !$remove) {
                    continue;
                }

                $filtered = array_values(array_filter($tabs, fn ($tab) => ($tab['tab'] ?? null) !== self::TAB));

                if (!$remove) {
                    $maxSort = 0;
                    $maxId = 0;
                    foreach ($filtered as $tab) {
                        $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                        $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                    }
                    $filtered[] = [
                        'title' => self::TAB_TITLE,
                        'tab' => self::TAB,
                        'sort' => $maxSort + 1,
                        'enabled' => 1,
                        'id' => $maxId + 1,
                        'has_roles_read' => false,
                        'roles_read' => null,
                    ];
                }

                $db->table('settings')->where('id', $menu->id)->update([
                    'value' => json_encode(array_values($filtered), JSON_UNESCAPED_SLASHES),
                ]);
                $changed = true;
            }

            if ($changed) {
                Settings::clear_cache();
            }
        } catch (\Throwable $e) {
        }

        return $changed;
    }
}
