<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallProductNdsFields extends Command
{
    protected $signature = 'products:install-nds-fields
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Добавить поля «НДС» (Без НДС/5%/10%/20%/22%) и «НДС включен в цену» (Да/Нет) в сущность products';

    public const NDS_FIELD = 'nds';
    public const NDS_TITLE = 'НДС';
    public const NDS_OPTIONS = [
        ['value' => 'none', 'label' => 'Без НДС'],
        ['value' => '5', 'label' => '5%'],
        ['value' => '10', 'label' => '10%'],
        ['value' => '20', 'label' => '20%'],
        ['value' => '22', 'label' => '22%'],
    ];

    public const INCLUDED_FIELD = 'nds_included';
    public const INCLUDED_TITLE = 'НДС включен в цену';
    public const INCLUDED_OPTIONS = [
        ['value' => '1', 'label' => 'Да'],
        ['value' => '0', 'label' => 'Нет'],
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

        foreach ([self::NDS_FIELD, self::INCLUDED_FIELD] as $col) {
            if (!$sb->hasColumn('products', $col)) {
                $db->statement("ALTER TABLE `products` ADD COLUMN `{$col}` VARCHAR(16) NULL");
            }
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'products')
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id');
        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $dataType->id)->max('sort');

        $defs = [
            self::NDS_FIELD => [
                'title' => self::NDS_TITLE,
                'details' => json_encode(['options' => self::NDS_OPTIONS], JSON_UNESCAPED_UNICODE),
                'default_value' => null,
                'set_default' => 0,
            ],
            self::INCLUDED_FIELD => [
                'title' => self::INCLUDED_TITLE,
                'details' => json_encode(['options' => self::INCLUDED_OPTIONS], JSON_UNESCAPED_UNICODE),
                'default_value' => '1',
                'set_default' => 1,
            ],
        ];

        foreach ($defs as $field => $def) {
            $existing = $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->where('field', $field)
                ->first();

            if ($existing) {
                $db->table('data_rows')->where('id', $existing->id)->update([
                    'type' => 'select_dropdown',
                    'title' => $def['title'],
                    'details' => $def['details'],
                    'default_value' => $def['default_value'],
                    'set_default' => $def['set_default'],
                    'hide' => 0,
                ]);
                $this->line("    [{$label}] поле {$field} обновлено (id {$existing->id})");
            } else {
                $maxSort++;
                $id = $db->table('data_rows')->insertGetId([
                    'data_type_id' => $dataType->id,
                    'field' => $field,
                    'type' => 'select_dropdown',
                    'title' => $def['title'],
                    'required' => 0,
                    'visible_always' => 1,
                    'section_id' => $sectionId,
                    'hide' => $sectionId ? 0 : 1,
                    'sort' => $maxSort,
                    'is_plural' => 0,
                    'is_permanent' => 1,
                    'default_value' => $def['default_value'],
                    'set_default' => $def['set_default'],
                    'details' => $def['details'],
                ]);
                $this->line("    [{$label}] создано поле {$field} (id {$id})");
            }
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
