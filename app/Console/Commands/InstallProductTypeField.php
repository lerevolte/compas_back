<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallProductTypeField extends Command
{
    protected $signature = 'products:install-type-field
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Добавить обязательное поле «Тип товара» (Товар/Услуга, дефолт Товар) в сущность products и проставить «Товар» всем товарам без типа';

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
        $dry = (bool) $this->option('dry-run');

        $dataType = $db->table('data_types')->where('slug', 'products')->first();
        if (!$dataType || !$sb->hasTable('products')) {
            $this->warn("    [{$label}] сущность products не найдена, пропуск");
            return;
        }

        if ($dry) {
            $this->line("    [{$label}] будет создано поле products.product_type + бэкфилл «Товар»");
            return;
        }

        if (!$sb->hasColumn('products', 'product_type')) {
            $db->statement("ALTER TABLE `products` ADD COLUMN `product_type` TEXT NULL");
        }

        $details = json_encode([
            'options' => [
                ['value' => 0, 'label' => 'Товар'],
                ['value' => 1, 'label' => 'Услуга'],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', 'product_type')
            ->first();

        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update([
                'type' => 'select_dropdown',
                'title' => 'Тип товара',
                'details' => $details,
                'required' => 1,
                'default_value' => '0',
                'set_default' => 1,
                'hide' => 0,
            ]);
            $this->line("    [{$label}] поле product_type обновлено (id {$existing->id})");
        } else {
            $sectionId = $db->table('field_sections')
                ->where('page', 'products')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');

            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->max('sort');

            $id = $db->table('data_rows')->insertGetId([
                'data_type_id' => $dataType->id,
                'field' => 'product_type',
                'type' => 'select_dropdown',
                'title' => 'Тип товара',
                'required' => 1,
                'visible_always' => 1,
                'section_id' => $sectionId,
                'hide' => $sectionId ? 0 : 1,
                'sort' => $maxSort + 1,
                'is_plural' => 0,
                'is_permanent' => 1,
                'default_value' => '0',
                'set_default' => 1,
                'details' => $details,
            ]);
            $this->line("    [{$label}] создано поле product_type (id {$id})");
        }

        $filled = $db->table('products')
            ->where(fn ($q) => $q->whereNull('product_type')
                ->orWhere('product_type', '')
                ->orWhere('product_type', '[]')
                ->orWhere('product_type', 'null'))
            ->update(['product_type' => '0']);
        if ($filled) {
            $this->line("    [{$label}] тип «Товар» проставлен {$filled} товарам");
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
}
