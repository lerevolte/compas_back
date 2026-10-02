<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallProductCharacteristics extends Command
{
    protected $signature = 'products:install-characteristics
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Характеристики товара: габариты (см) с автообъёмом (л), ID 1С, ед. изм. хранения, штрихкод поставщиков, кол-во на паллете, срок/способ пополнения, артикул (id на сайте), состав набора и обратная связь «Фактически отгружаемый товар» с заполнением по наборам, условия хранения — в основной раздел полей';

    public const BEFORE_VOLUME = ['length', 'width', 'height'];

    public const FIELDS = [
        'length' => ['type' => 'number', 'title' => 'Длина', 'unit' => 'см'],
        'width' => ['type' => 'number', 'title' => 'Ширина', 'unit' => 'см'],
        'height' => ['type' => 'number', 'title' => 'Высота', 'unit' => 'см'],
        'id_1c' => ['type' => 'text', 'title' => 'ID 1С'],
        'storage_unit' => ['type' => 'select_dropdown', 'title' => 'Ед. изм. хранения', 'options' => ['шт', 'м3', 'л', 'кг', 'рул', 'м2', 'упак', 'м', 'тн']],
        'supplier_barcode' => ['type' => 'text', 'title' => 'Штрихкод поставщиков'],
        'pallet_quantity' => ['type' => 'number', 'title' => 'Кол-во на паллете', 'unit' => 'шт'],
        'replenishment_period' => ['type' => 'number', 'title' => 'Срок пополнения', 'unit' => 'дн.'],
        'replenishment_method' => ['type' => 'select_dropdown', 'title' => 'Способ пополнения', 'options' => ['Закупка', 'Производство']],
        'article' => ['type' => 'text', 'title' => 'Артикул'],
        'kit_products' => ['type' => 'relation', 'title' => 'Состав набора', 'relation_table' => 'products', 'is_plural' => 1, 'is_link' => 1],
        'fact_product_id' => ['type' => 'relation', 'title' => 'Фактически отгружаемый товар', 'relation_table' => 'products', 'is_link' => 1],
        'storage_conditions' => ['type' => 'select_dropdown', 'title' => 'Условия хранения', 'is_plural' => 1, 'options' => ['Беречь от влаги', 'Хрупкое', 'Боится солнца']],
    ];

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
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId || !$sb->hasTable('products')) {
            $this->warn("    [{$label}] сущность products не найдена, пропуск");
            return;
        }

        $volumeRow = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'volume')->first();
        $sectionId = $volumeRow && $volumeRow->section_id
            ? (int) $volumeRow->section_id
            : (int) $db->table('field_sections')
                ->where('page', 'products')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
        if ($volumeRow && trim((string) $volumeRow->unit) === '') {
            $db->table('data_rows')->where('id', $volumeRow->id)->update(['unit' => 'л']);
        }

        $created = [];
        foreach (self::FIELDS as $field => $def) {
            if (!$sb->hasColumn('products', $field)) {
                $db->statement('ALTER TABLE `products` ADD COLUMN `' . $field . '` TEXT NULL');
            }
            $attrs = [
                'type' => $def['type'],
                'title' => $def['title'],
                'unit' => $def['unit'] ?? '',
                'is_plural' => $def['is_plural'] ?? 0,
                'is_link' => $def['is_link'] ?? 0,
                'relation_table' => $def['relation_table'] ?? null,
                'related_field' => null,
                'is_remove' => 0,
                'hide' => 0,
                'visible_always' => 1,
            ];
            if (isset($def['options'])) {
                $attrs['details'] = json_encode(['options' => array_map(
                    fn ($label, $value) => ['label' => $label, 'value' => $value],
                    $def['options'],
                    array_keys($def['options'])
                )], JSON_UNESCAPED_UNICODE);
            } elseif (isset($def['relation_table'])) {
                $attrs['details'] = json_encode(['table' => $def['relation_table']], JSON_UNESCAPED_UNICODE);
            }
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
            if ($existing) {
                unset($attrs['title']);
                $db->table('data_rows')->where('id', $existing->id)->update($attrs);
                continue;
            }
            $db->table('data_rows')->insert($attrs + [
                'data_type_id' => $typeId,
                'field' => $field,
                'required' => 0,
                'section_id' => $sectionId,
                'sort' => 0,
                'is_permanent' => 0,
                'is_default' => 0,
                'only_read' => 0,
                'roles_read' => '',
                'roles_write' => '',
                'mobile_pages' => '',
                'button_name' => 'Загрузить',
                'label_color' => '',
                'external_link' => '',
                'module' => '',
            ]);
            $created[] = $field;
        }

        $this->reorder($db, (int) $typeId, $sectionId);

        \App\Services\ProductKitService::forget();
        $kits = \App\Services\ProductKitService::backfill($db);
        $this->line("    [{$label}] products: наборов {$kits['kits']}, «Фактически отгружаемый товар» заполнен у {$kits['filled']} товаров" . ($kits['conflicts'] ? ", товаров в нескольких наборах: {$kits['conflicts']}" : ''));

        if (count($created)) {
            $this->line("    [{$label}] products: созданы поля " . implode(', ', $created));
        } else {
            $this->line("    [{$label}] products: поля уже есть, обновлены определения");
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/products')->update(['updated_at' => now()]);
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

    private function reorder($db, int $typeId, int $sectionId): void
    {
        $rows = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('section_id', $sectionId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'field']);
        $new = array_keys(self::FIELDS);
        $before = self::BEFORE_VOLUME;
        $after = array_values(array_diff($new, $before));
        $order = [];
        $hasVolume = $rows->contains(fn ($r) => $r->field === 'volume');
        foreach ($rows as $row) {
            if (in_array($row->field, $new, true)) {
                continue;
            }
            if ($row->field === 'volume') {
                $order = array_merge($order, $before, ['volume'], $after);
                continue;
            }
            $order[] = $row->field;
        }
        if (!$hasVolume) {
            $order = array_merge($order, $before, $after);
        }
        $byField = $rows->keyBy('field');
        $sort = 0;
        foreach ($order as $field) {
            if (isset($byField[$field])) {
                $db->table('data_rows')->where('id', $byField[$field]->id)->update(['sort' => $sort++]);
            }
        }
    }
}
