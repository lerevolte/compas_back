<?php

namespace App\Services;

use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionService
{
    public const ORDER = 'production_orders';
    public const DOC = 'productions';
    public const MATERIALS_FIELD = 'materials';
    public const MATERIALS_SUFFIX = '_materials';
    public const SPECIFICATIONS = 'specifications';
    public const SPEC_KEY = 'product_specification_id';
    public const SPEC_TITLE = 'Спецификация';
    public const PRODUCED_KEY = 'product_produced';
    public const PRODUCED_TITLE = 'Фактическое кол-во';
    public const STATUS_FIELD = 'production_status';
    public const STATUS_TITLE = 'Статус';
    public const STATUS_VALUES = [
        ['value' => 'Не выполнен', 'color' => '#A8A8A8'],
        ['value' => 'Выполнен частично', 'color' => '#FF9500'],
        ['value' => 'Выполнен полностью', 'color' => '#34C759'],
    ];
    public const MODELS = [
        self::ORDER => \App\Models\ProductionOrder::class,
        self::DOC => \App\Models\Production::class,
    ];
    public const MATERIAL_MODELS = [
        self::ORDER => \App\Models\ProductionOrderMaterials::class,
        self::DOC => \App\Models\ProductionMaterials::class,
    ];

    private static array $specNames = [];

    public static function isEntity(?string $slug): bool
    {
        return isset(self::MODELS[(string) $slug]);
    }

    public static function materialsSlug(string $slug): string
    {
        return $slug . self::MATERIALS_SUFFIX;
    }

    public static function ownerOfMaterials(?string $slug): ?string
    {
        $slug = (string) $slug;
        if (!str_ends_with($slug, self::MATERIALS_SUFFIX)) {
            return null;
        }
        $owner = substr($slug, 0, -strlen(self::MATERIALS_SUFFIX));

        return self::isEntity($owner) ? $owner : null;
    }

    public static function isComposition(?string $parentSlug): bool
    {
        return self::isEntity($parentSlug) || self::ownerOfMaterials($parentSlug) !== null;
    }

    public static function decode($value): array
    {
        return array_values(array_filter(ShipmentService::decode($value), 'is_array'));
    }

    public static function specificationId($value): ?int
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        return ShipmentService::normalizeDealId($value);
    }

    public static function specificationName(int $id): string
    {
        if (!array_key_exists($id, self::$specNames)) {
            $name = Schema::hasTable(self::SPECIFICATIONS) ? DB::table(self::SPECIFICATIONS)->where('id', $id)->value('name') : null;
            self::$specNames[$id] = ShipmentService::plainName($name ?? '') ?: (self::SPEC_TITLE . ' #' . $id);
        }

        return self::$specNames[$id];
    }

    public static function specificationCell($value): array
    {
        $id = self::specificationId($value);
        if (!$id) {
            return ['value' => [null], 'localOptions' => []];
        }

        return [
            'value' => [$id],
            'localOptions' => [[
                'value' => $id,
                'label' => ['id' => $id, 'sort' => 0, 'file' => '', 'is_hidden' => 0, 'field_id' => 0, 'color' => '', 'text' => self::specificationName($id)],
            ]],
        ];
    }

    public static function specificationOptions(int $limit = 30): array
    {
        if (!Schema::hasTable(self::SPECIFICATIONS)) {
            return [];
        }
        $options = [];
        $query = DB::table(self::SPECIFICATIONS)->whereNull('deleted_at')->orderBy('choosed_at', 'DESC')->orderBy('id', 'DESC')->limit($limit);
        foreach ($query->get(['id', 'name']) as $i => $row) {
            $name = ShipmentService::plainName($row->name ?? '') ?: (self::SPEC_TITLE . ' #' . $row->id);
            self::$specNames[(int) $row->id] = $name;
            $options[] = [
                'value' => (int) $row->id,
                'label' => ['id' => (int) $row->id, 'sort' => $i, 'file' => '', 'is_hidden' => 0, 'field_id' => 0, 'color' => '', 'text' => $name],
            ];
        }

        return $options;
    }

    public static function requiredMaterials(array $products): array
    {
        $required = [];
        $specs = [];
        foreach ($products as $product) {
            $specId = self::specificationId($product['specification_id'] ?? null);
            $count = (float) str_replace(',', '.', (string) ($product['count'] ?? 0));
            if (!$specId || $count <= 0) {
                continue;
            }
            if (!array_key_exists($specId, $specs)) {
                $spec = Schema::hasTable(self::SPECIFICATIONS)
                    ? DB::table(self::SPECIFICATIONS)->where('id', $specId)->whereNull('deleted_at')->first(['products'])
                    : null;
                $specs[$specId] = $spec ? self::decode($spec->products) : [];
            }
            foreach ($specs[$specId] as $component) {
                $need = (float) str_replace(',', '.', (string) ($component['count'] ?? 0));
                if ($need <= 0) {
                    continue;
                }
                $output = (float) str_replace(',', '.', (string) ($component['output_count'] ?? 0));
                $amount = $need * $count / ($output > 0 ? $output : 1);
                $id = (int) ($component['id'] ?? 0);
                $name = ShipmentService::plainName($component['name'] ?? '');
                $key = $id ? 'id:' . $id : 'name:' . ShipmentService::nameKey($name);
                if (!isset($required[$key])) {
                    $required[$key] = ['id' => $id ?: null, 'name' => $name, 'count' => 0.0];
                }
                $required[$key]['count'] += $amount;
            }
        }
        foreach ($required as $key => $line) {
            $required[$key]['count'] = round($line['count'], 6);
        }

        return $required;
    }

    public static function materialLines(array $required): array
    {
        $ids = array_values(array_filter(array_map(fn ($line) => (int) $line['id'], $required)));
        $products = count($ids) ? DB::table('products')->whereIn('id', $ids)->get(['id', 'name', 'weight', 'volume'])->keyBy('id') : collect();
        $lines = [];
        foreach ($required as $line) {
            $product = $line['id'] ? ($products[$line['id']] ?? null) : null;
            $lines[] = [
                'id' => $line['id'],
                'name' => $product ? (ShipmentService::plainName($product->name ?? '') ?: $line['name']) : $line['name'],
                'price' => null,
                'purchase_price' => null,
                'count' => self::format($line['count']),
                'weight' => $product->weight ?? null,
                'volume' => $product->volume ?? 0,
                'sum' => 0,
                'nds' => null,
                'nds_included' => null,
            ];
        }

        return $lines;
    }

    public static function fillMaterials(string $slug, int $id): ?array
    {
        $class = self::MODELS[$slug] ?? null;
        $object = $class ? $class::find($id) : null;
        if (!$object) {
            return null;
        }
        $lines = self::materialLines(self::requiredMaterials(self::decode($object->products)));
        $object->setMaterials($lines);

        return $lines;
    }

    public static function materialErrors(string $slug, int $id, array $materials): array
    {
        if ($slug !== self::DOC || !count($materials)) {
            return [];
        }
        $object = DB::table($slug)->where('id', $id)->first(['products']);
        if (!$object) {
            return [];
        }
        $required = self::requiredMaterials(self::decode($object->products));
        $errors = [];
        $used = [];
        foreach ($materials as $material) {
            $id = (int) ($material['id'] ?? 0);
            $name = ShipmentService::plainName($material['name'] ?? '') ?: 'Товар';
            $key = $id ? 'id:' . $id : 'name:' . ShipmentService::nameKey($name);
            $used[$key] = ($used[$key] ?? 0) + (float) str_replace(',', '.', (string) ($material['count'] ?? 0));
            if (!isset($required[$key])) {
                $errors[$key] = "«{$name}»: нет в спецификациях продукции";
                continue;
            }
            if ($used[$key] > $required[$key]['count'] + 0.000001) {
                $errors[$key] = "«{$name}»: указано " . self::format($used[$key]) . ', по спецификациям требуется ' . self::format($required[$key]['count']);
            }
        }

        return array_values($errors);
    }

    public static function producedFor(int $orderId): array
    {
        $result = ['id' => [], 'name' => []];
        if (!ObjectRelation::ready() || !Schema::hasTable(self::DOC)) {
            return $result;
        }
        $ids = ObjectRelation::where('source_slug', self::ORDER)->where('source_id', $orderId)->where('target_slug', self::DOC)
            ->pluck('target_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
        if (!count($ids)) {
            return $result;
        }
        foreach (DB::table(self::DOC)->whereIn('id', $ids)->whereNull('deleted_at')->pluck('products') as $raw) {
            foreach (self::decode($raw) as $product) {
                $count = (float) str_replace(',', '.', (string) ($product['count'] ?? 0));
                if ($count <= 0) {
                    continue;
                }
                $id = (int) ($product['id'] ?? 0);
                if ($id) {
                    $result['id'][$id] = ($result['id'][$id] ?? 0) + $count;
                    continue;
                }
                $name = ShipmentService::nameKey($product['name'] ?? '');
                if ($name !== '') {
                    $result['name'][$name] = ($result['name'][$name] ?? 0) + $count;
                }
            }
        }

        return $result;
    }

    public static function recalcOrder(int $orderId): void
    {
        if (!$orderId || !Schema::hasTable(self::ORDER)) {
            return;
        }
        try {
            $order = \App\Models\ProductionOrder::find($orderId);
            if (!$order) {
                return;
            }
            $produced = self::producedFor($orderId);
            $products = self::decode($order->products);
            $changed = false;
            foreach ($products as $i => $product) {
                $value = ShipmentService::lookup($produced, $product);
                if ((float) ($product['produced'] ?? 0) !== $value) {
                    $products[$i]['produced'] = $value;
                    $changed = true;
                }
            }
            $order->timestamps = false;
            if ($changed) {
                $order->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            }
            $statusChanged = self::applyStatus($order, $products);
            if ($changed || $statusChanged) {
                $order->saveQuietly();
                try {
                    \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $order->getData());
                } catch (\Throwable $e) {
                }
            }
            $order->timestamps = true;
        } catch (\Throwable $e) {
            \Log::warning('production: пересчёт заказа не выполнен', ['order' => $orderId, 'error' => $e->getMessage()]);
        }
    }

    public static function recalcForDocument(int $documentId): void
    {
        if (!$documentId || !ObjectRelation::ready()) {
            return;
        }
        $orders = ObjectRelation::where('source_slug', self::ORDER)->where('target_slug', self::DOC)->where('target_id', $documentId)
            ->pluck('source_id')->map(fn ($v) => (int) $v)->unique();
        foreach ($orders as $orderId) {
            self::recalcOrder($orderId);
        }
    }

    public static function afterLink(string $sourceSlug, int $sourceId, string $targetSlug, int $targetId): void
    {
        if ($sourceSlug !== self::ORDER || $targetSlug !== self::DOC) {
            return;
        }
        try {
            $production = DB::table(self::DOC)->where('id', $targetId)->first(['materials']);
            if ($production && !count(self::decode($production->materials))) {
                self::fillMaterials(self::DOC, $targetId);
            }
        } catch (\Throwable $e) {
        }
        self::recalcOrder($sourceId);
    }

    public static function afterUnlink(string $slugA, int $idA, string $slugB, int $idB): void
    {
        foreach ([[$slugA, $idA], [$slugB, $idB]] as [$slug, $id]) {
            if ($slug === self::ORDER) {
                self::recalcOrder($id);
            }
        }
    }

    private static function applyStatus($order, array $products): bool
    {
        if (!Schema::hasColumn(self::ORDER, self::STATUS_FIELD)) {
            return false;
        }
        $typeId = DB::table('data_types')->where('slug', self::ORDER)->value('id');
        $fieldId = $typeId ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', self::STATUS_FIELD)->value('id') : null;
        if (!$fieldId) {
            return false;
        }
        $lines = 0;
        $any = false;
        $full = true;
        foreach ($products as $product) {
            $count = (float) str_replace(',', '.', (string) ($product['count'] ?? 0));
            if ($count <= 0) {
                continue;
            }
            $lines++;
            $produced = (float) ($product['produced'] ?? 0);
            if ($produced > 0.000001) {
                $any = true;
            }
            if ($produced + 0.000001 < $count) {
                $full = false;
            }
        }
        $index = !$lines || !$any ? 0 : ($full ? 2 : 1);
        $valueId = DB::table('field_values')->where('field_id', $fieldId)->where('value', self::STATUS_VALUES[$index]['value'])->value('id');
        if (!$valueId) {
            $ordered = DB::table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)->orderBy('sort')->orderBy('id')->pluck('id')->all();
            $valueId = count($ordered) >= 3 ? $ordered[$index] : null;
        }
        if (!$valueId || (string) $order->{self::STATUS_FIELD} === (string) $valueId) {
            return false;
        }
        $order->{self::STATUS_FIELD} = (string) $valueId;

        return true;
    }

    public static function format(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        return $text === '-0' || $text === '' ? '0' : $text;
    }
}
