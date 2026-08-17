<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ObjectRelation extends Model
{
    protected $table = 'object_relations';
    protected $guarded = ['id'];

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
    }
}
