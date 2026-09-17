<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ProductPriceService;
use Illuminate\Console\Command;

class InstallProductPriceFields extends Command
{
    public const SALE_TITLE = 'Цена продажи';
    public const OLD_SALE_TITLES = ['Цена', 'Цена, руб', 'Цена, руб.'];
    public const PURCHASE_FIELD = 'purchase_price';
    public const PURCHASE_TITLE = 'Цена закупки';

    protected $signature = 'products:install-price-fields
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--recalc : пересчитать «Цену продажи» по последним 30 задачам логистики и самовывозам}';

    protected $description = 'Поля цен у товаров: «Цена» → «Цена продажи», новое поле «Цена закупки»; опционально пересчёт цены продажи по документам';

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

        $dataType = $db->table('data_types')->where('slug', 'products')->first();
        if (!$dataType || !$sb->hasTable('products')) {
            $this->warn("    [{$label}] сущность products не найдена, пропуск");
            return;
        }

        $priceRow = $db->table('data_rows')->where('data_type_id', $dataType->id)->where('field', 'price')->first();
        if ($priceRow && in_array(trim((string) $priceRow->title), self::OLD_SALE_TITLES, true)) {
            $db->table('data_rows')->where('id', $priceRow->id)->update(['title' => self::SALE_TITLE]);
            $this->line("    [{$label}] поле price переименовано в «" . self::SALE_TITLE . '»');
        }

        if (!$sb->hasColumn('products', self::PURCHASE_FIELD)) {
            $db->statement('ALTER TABLE `products` ADD COLUMN `' . self::PURCHASE_FIELD . '` TEXT NULL');
        }

        $existing = $db->table('data_rows')->where('data_type_id', $dataType->id)->where('field', self::PURCHASE_FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update([
                'type' => 'number',
                'title' => self::PURCHASE_TITLE,
                'hide' => 0,
            ]);
            $this->line("    [{$label}] поле " . self::PURCHASE_FIELD . " обновлено (id {$existing->id})");
        } else {
            $sectionId = $priceRow && $priceRow->section_id
                ? $priceRow->section_id
                : $db->table('field_sections')
                    ->where('page', 'products')
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id');
            $sort = $priceRow ? (int) $priceRow->sort : (int) $db->table('data_rows')->where('data_type_id', $dataType->id)->max('sort');
            $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->where('section_id', $sectionId)
                ->where('sort', '>', $sort)
                ->increment('sort');
            $id = $db->table('data_rows')->insertGetId([
                'data_type_id' => $dataType->id,
                'field' => self::PURCHASE_FIELD,
                'type' => 'number',
                'title' => self::PURCHASE_TITLE,
                'required' => 0,
                'visible_always' => 1,
                'section_id' => $sectionId,
                'hide' => $sectionId ? 0 : 1,
                'sort' => $sort + 1,
                'is_plural' => 0,
                'is_permanent' => 1,
                'is_default' => 1,
                'only_read' => 0,
                'unit' => $priceRow->unit ?? '',
            ]);
            $this->line("    [{$label}] создано поле " . self::PURCHASE_FIELD . " (id {$id})");
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/products')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }

        if (!$inTenant) {
            return;
        }

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        if ($this->option('recalc')) {
            $ids = $db->table('products')->whereNull('deleted_at')->pluck('id')->map(fn ($v) => (int) $v)->all();
            $updated = 0;
            foreach (array_chunk($ids, 200) as $chunk) {
                $updated += ProductPriceService::recalc($chunk);
            }
            $this->line("    [{$label}] цена продажи пересчитана по документам: обновлено товаров {$updated} из " . count($ids));
        }
    }
}
