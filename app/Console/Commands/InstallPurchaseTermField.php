<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\PurchaseTermService;
use Illuminate\Console\Command;

class InstallPurchaseTermField extends Command
{
    protected $signature = 'products:install-purchase-term
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Поле товара «Средний срок закупки» (заказ поставщику → приходная, спецификации, фактически отгружаемый товар) и его заполнение';

    public const ANCHORS = ['replenishment_period', PurchaseTermService::FACT_FIELD];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);

            return self::SUCCESS;
        }
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");

            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $field = PurchaseTermService::FIELD;
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId || !$sb->hasTable(PurchaseTermService::ORDERS) || !$sb->hasTable(PurchaseTermService::RECEIPTS)) {
            $this->line("    [{$label}] нет заказов поставщикам или приходных, пропуск");

            return;
        }
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        if ($this->option('dry-run')) {
            $this->line("    [{$label}] " . ($existing ? "поле уже есть (id {$existing->id})" : 'будет создано поле products.' . $field));

            return;
        }
        if (!$sb->hasColumn('products', $field)) {
            $db->statement("ALTER TABLE `products` ADD COLUMN `{$field}` INT NULL");
        }
        $attrs = [
            'type' => 'number',
            'title' => PurchaseTermService::TITLE,
            'unit' => 'дн.',
            'only_read' => 1,
            'is_program' => 1,
            'is_remove' => 0,
        ];
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            $id = $existing->id;
        } else {
            $anchor = null;
            foreach (self::ANCHORS as $anchorField) {
                $anchor = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $anchorField)->where('is_remove', 0)->first(['section_id', 'group_id', 'sort']);
                if ($anchor) {
                    break;
                }
            }
            $sort = $anchor ? (int) $anchor->sort + 1 : (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1;
            $row = $attrs + [
                'data_type_id' => $typeId, 'field' => $field, 'required' => 0, 'details' => '', 'visible_always' => 1,
                'label_color' => '', 'section_id' => $anchor->section_id ?? null, 'group_id' => $anchor->group_id ?? null,
                'sort' => $sort, 'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0,
                'roles_read' => '', 'roles_write' => '', 'mobile_pages' => '', 'show_file_name' => 0, 'external_link' => '',
                'is_external_link' => 0, 'module' => '', 'is_link' => 0, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0, 'relation_table' => null,
                'set_color' => 0, 'related_field' => null, 'is_unique' => 0, 'is_permanent' => 0,
            ];
            $id = $db->table('data_rows')->insertGetId(array_intersect_key($row, array_flip($sb->getColumnListing('data_rows'))));
        }
        $this->line("    [{$label}] поле «" . PurchaseTermService::TITLE . "» id {$id}" . ($existing ? ' (обновлено)' : ' (создано)'));
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/products')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if (!$inTenant) {
            return;
        }
        PurchaseTermService::forget();
        $this->line("    [{$label}] обновлено товаров: " . PurchaseTermService::recalcAll());
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
