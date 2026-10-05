<?php

namespace App\Services;

use App\Models\History;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProductKitService
{
    public const KIT_FIELD = 'kit_products';
    public const FACT_FIELD = 'fact_product_id';
    private const B24_COLUMN = 'b24_fact_product';

    private static array $ready = [];
    private static array $b24Column = [];

    private static function scope(): string
    {
        return (string) (function_exists('tenant') ? tenant('id') : '');
    }

    public static function forget(): void
    {
        self::$ready = [];
        self::$b24Column = [];
    }

    public static function ready(): bool
    {
        $key = self::scope();
        if (!array_key_exists($key, self::$ready)) {
            try {
                self::$ready[$key] = Schema::hasColumn('products', self::KIT_FIELD) && Schema::hasColumn('products', self::FACT_FIELD);
            } catch (\Throwable $e) {
                self::$ready[$key] = false;
            }
        }

        return self::$ready[$key];
    }

    private static function hasB24Column(): bool
    {
        $key = self::scope();
        if (!array_key_exists($key, self::$b24Column)) {
            try {
                self::$b24Column[$key] = Schema::hasColumn('products', self::B24_COLUMN);
            } catch (\Throwable $e) {
                self::$b24Column[$key] = false;
            }
        }

        return self::$b24Column[$key];
    }

    public static function ids($raw): array
    {
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $decoded = is_numeric($raw) ? [$raw] : [];
        }

        return array_values(array_unique(array_filter(array_map('intval', array_filter($decoded, 'is_numeric')))));
    }

    public static function onSaved(Product $product): void
    {
        if (!self::ready()) {
            return;
        }
        $id = (int) $product->id;
        $factOld = self::ids($product->getOriginal(self::FACT_FIELD))[0] ?? 0;
        $factNew = self::ids($product->getAttribute(self::FACT_FIELD))[0] ?? 0;
        $kitOld = self::ids($product->getOriginal(self::KIT_FIELD));
        $kitNew = self::ids($product->getAttribute(self::KIT_FIELD));

        if ($factOld !== $factNew && $factNew !== $id) {
            if ($factOld) {
                self::detach($factOld, $id);
            }
            if ($factNew) {
                self::attach($factNew, $id);
            }
            if (self::hasB24Column()) {
                DB::table('products')->where('id', $id)->update([
                    self::B24_COLUMN => $factNew ? DB::table('products')->where('id', $factNew)->value('id_b24') : null,
                ]);
            }
        }

        foreach (array_diff($kitOld, $kitNew) as $memberId) {
            if (self::factOf($memberId) === $id) {
                self::setFact($memberId, null);
            }
        }
        foreach (array_diff($kitNew, $kitOld) as $memberId) {
            if ($memberId === $id) {
                continue;
            }
            $previous = self::factOf($memberId);
            if ($previous === $id) {
                continue;
            }
            if ($previous) {
                self::detach($previous, $memberId);
            }
            self::setFact($memberId, $id);
        }
    }

    public static function factOf(int $productId): int
    {
        return self::ids(DB::table('products')->where('id', $productId)->value(self::FACT_FIELD))[0] ?? 0;
    }

    public static function setFact(int $productId, ?int $kitId, bool $external = false): void
    {
        try {
            History::saveForObject('products', [['id' => $productId, self::FACT_FIELD => $kitId]]);
        } catch (\Throwable $e) {
        }
        $update = [self::FACT_FIELD => $kitId, 'updated_at' => now()];
        if (!$external && self::hasB24Column()) {
            $update[self::B24_COLUMN] = $kitId ? DB::table('products')->where('id', $kitId)->value('id_b24') : null;
        }
        DB::table('products')->where('id', $productId)->update($update);
        if (!$external) {
            self::pushFact($productId);
        }
    }

    private static function attach(int $kitId, int $productId): void
    {
        $kit = DB::table('products')->where('id', $kitId)->first(['id', self::KIT_FIELD]);
        if (!$kit) {
            return;
        }
        $ids = self::ids($kit->{self::KIT_FIELD});
        if (in_array($productId, $ids, true)) {
            return;
        }
        $ids[] = $productId;
        self::writeKit($kitId, $ids);
    }

    private static function detach(int $kitId, int $productId): void
    {
        $kit = DB::table('products')->where('id', $kitId)->first(['id', self::KIT_FIELD]);
        if (!$kit) {
            return;
        }
        $ids = self::ids($kit->{self::KIT_FIELD});
        if (!in_array($productId, $ids, true)) {
            return;
        }
        self::writeKit($kitId, array_values(array_diff($ids, [$productId])));
    }

    private static function writeKit(int $kitId, array $ids): void
    {
        $value = json_encode(array_values($ids));
        try {
            History::saveForObject('products', [['id' => $kitId, self::KIT_FIELD => $value]]);
        } catch (\Throwable $e) {
        }
        DB::table('products')->where('id', $kitId)->update([self::KIT_FIELD => $value, 'updated_at' => now()]);
    }

    private static function pushFact(int $productId): void
    {
        if (!class_exists(\Modules\Bitrix24\Services\B24ProductSync::class)
            || \Modules\Bitrix24\Services\B24ProductSync::$muted
            || \Modules\Bitrix24\Services\B24EntitySync::$muted) {
            return;
        }
        try {
            $product = Product::find($productId);
            if ($product && $product->id_b24) {
                \Modules\Bitrix24\Services\B24ProductSync::make()?->pushProduct($product, [self::FACT_FIELD]);
            }
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('product push failed', ['product_id' => $productId, 'error' => $e->getMessage()]);
        }
    }

    public static function backfill($db): array
    {
        $stat = ['kits' => 0, 'filled' => 0, 'conflicts' => 0];
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasColumn('products', self::KIT_FIELD) || !$sb->hasColumn('products', self::FACT_FIELD)) {
            return $stat;
        }
        $hasB24 = $sb->hasColumn('products', self::B24_COLUMN) && $sb->hasColumn('products', 'id_b24');
        $kits = $db->table('products')
            ->whereNull('deleted_at')
            ->whereNotNull(self::KIT_FIELD)
            ->whereNotIn(self::KIT_FIELD, ['', '[]'])
            ->orderBy('id')
            ->get($hasB24 ? ['id', 'id_b24', self::KIT_FIELD] : ['id', self::KIT_FIELD]);
        $owner = [];
        foreach ($kits as $kit) {
            $members = array_values(array_diff(self::ids($kit->{self::KIT_FIELD}), [(int) $kit->id]));
            if (!count($members)) {
                continue;
            }
            $stat['kits']++;
            foreach ($members as $memberId) {
                $owner[$memberId][] = $kit;
            }
        }
        foreach ($owner as $memberId => $candidates) {
            $member = $db->table('products')->where('id', $memberId)->first($hasB24 ? ['id', self::FACT_FIELD, self::B24_COLUMN] : ['id', self::FACT_FIELD]);
            if (!$member) {
                continue;
            }
            $kit = $candidates[0];
            if (count($candidates) > 1) {
                $stat['conflicts']++;
                foreach ($candidates as $candidate) {
                    if ($hasB24 && $member->{self::B24_COLUMN} && (string) $candidate->id_b24 === (string) $member->{self::B24_COLUMN}) {
                        $kit = $candidate;
                        break;
                    }
                }
            }
            if ((self::ids($member->{self::FACT_FIELD})[0] ?? 0) === (int) $kit->id) {
                continue;
            }
            $db->table('products')->where('id', $memberId)->update([self::FACT_FIELD => (int) $kit->id]);
            $stat['filled']++;
        }

        if (!$hasB24) {
            return $stat;
        }
        $pending = $db->table('products')
            ->whereNull('deleted_at')
            ->whereNotNull(self::B24_COLUMN)
            ->where(self::B24_COLUMN, '!=', '')
            ->whereNotIn('id', array_keys($owner) ?: [0])
            ->get(['id', self::B24_COLUMN]);
        foreach ($pending as $member) {
            $kit = $db->table('products')
                ->where('id_b24', (string) $member->{self::B24_COLUMN})
                ->whereNull('deleted_at')
                ->first(['id', self::KIT_FIELD]);
            if (!$kit || (int) $kit->id === (int) $member->id) {
                continue;
            }
            $ids = self::ids($kit->{self::KIT_FIELD});
            if (!in_array((int) $member->id, $ids, true)) {
                $ids[] = (int) $member->id;
                $db->table('products')->where('id', $kit->id)->update([self::KIT_FIELD => json_encode(array_values($ids))]);
            }
            $db->table('products')->where('id', $member->id)->update([self::FACT_FIELD => (int) $kit->id]);
            $stat['filled']++;
        }

        return $stat;
    }
}
