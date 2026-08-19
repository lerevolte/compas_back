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

    public static function copyProducts(string $sourceSlug, $sourceId, string $targetSlug, $targetId, bool $dryRun = false): bool
    {
        if (!$sourceId || !$targetId) {
            return false;
        }

        try {
            $target = self::objectWithProducts($targetSlug, (int) $targetId);
            if (!$target || !method_exists($target, 'setProducts')) {
                return false;
            }
            if (self::decodeProducts($target->products)) {
                return false;
            }

            $source = self::objectWithProducts($sourceSlug, (int) $sourceId);
            if (!$source) {
                return false;
            }

            $products = self::decodeProducts($source->products);
            if (!$products) {
                return false;
            }

            if (!$dryRun) {
                $target->setProducts($products);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function copyB24Id(string $sourceSlug, $sourceId, string $targetSlug, $targetId, bool $dryRun = false): bool
    {
        if (!$sourceId || !$targetId) {
            return false;
        }

        try {
            if (!Schema::hasTable($sourceSlug) || !Schema::hasColumn($sourceSlug, 'b24_id')
                || !Schema::hasTable($targetSlug) || !Schema::hasColumn($targetSlug, 'b24_id')) {
                return false;
            }

            $current = \DB::table($targetSlug)->where('id', (int) $targetId)->value('b24_id');
            if ($current !== null && $current !== '') {
                return false;
            }

            $sourceColumns = ['b24_id'];
            if (Schema::hasColumn($sourceSlug, 'crm_link')) {
                $sourceColumns[] = 'crm_link';
            }
            $source = \DB::table($sourceSlug)->where('id', (int) $sourceId)->first($sourceColumns);
            if (!$source || $source->b24_id === null || $source->b24_id === '') {
                return false;
            }

            if (!$dryRun) {
                $update = ['b24_id' => $source->b24_id];
                if (Schema::hasColumn($targetSlug, 'crm_link') && ($source->crm_link ?? null)) {
                    $currentLink = \DB::table($targetSlug)->where('id', (int) $targetId)->value('crm_link');
                    if ($currentLink === null || $currentLink === '') {
                        $update['crm_link'] = $source->crm_link;
                    }
                }
                \DB::table($targetSlug)->where('id', (int) $targetId)->update($update);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function objectWithProducts(string $slug, int $id)
    {
        if (!Schema::hasTable($slug) || !Schema::hasColumn($slug, 'products')) {
            return null;
        }

        $class = \DB::table('data_types')->where('slug', $slug)->value('model_name');
        if (!$class || !class_exists($class)) {
            return null;
        }

        return $class::find($id);
    }

    private static function decodeProducts($value): array
    {
        if (is_array($value)) {
            $products = $value;
        } else {
            $products = json_decode((string) $value, true);
        }

        if (!is_array($products)) {
            return [];
        }

        return array_values(array_filter($products, 'is_array'));
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
