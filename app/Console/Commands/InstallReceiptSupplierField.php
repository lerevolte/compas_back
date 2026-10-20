<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\RelationFieldsService;
use App\Services\ShipmentService;
use Illuminate\Console\Command;

class InstallReceiptSupplierField extends Command
{
    public const FIELD = 'shipment_company_id';
    public const TITLE = 'Компания поставщик';

    protected $signature = 'receipts:install-supplier-field
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--fix-existing : у приходных на основании заказа поставщику переложить поставщика из «Компании получателя» в «Компанию поставщика», получателем поставить компанию из заказа}
        {--uninstall : снять поле (колонка и данные остаются)}';

    protected $description = 'Поле «Компания поставщик» (shipment_company_id) у приходных накладных';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        if ($target === 'seeds' || $target === 'all-tenants') {
            $this->apply(\DB::connection('seeds'), 'admin_seeds');
        }
        if ($target === 'seeds') {
            return self::SUCCESS;
        }
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function apply($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $slug = ShipmentService::RECEIPT_DOC;
        $typeId = (int) $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId || !$sb->hasTable($slug) || !$sb->hasTable('companies')) {
            $this->warn("    [{$label}] приходных накладных нет, пропуск");
            return;
        }

        if ($this->option('uninstall')) {
            $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->pluck('id')->all();
            if (count($ids)) {
                if ($sb->hasTable('section_fields_sort')) {
                    $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                }
                $db->table('data_rows')->whereIn('id', $ids)->delete();
            }
            $this->line("    [{$label}] поле «" . self::TITLE . '» снято');
            $this->refresh($db, $sb, $slug);
            return;
        }

        if (!$sb->hasColumn($slug, self::FIELD)) {
            $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . self::FIELD . '` TEXT NULL');
        }
        $attrs = [
            'type' => 'relation', 'title' => self::TITLE, 'details' => '{"table":"companies"}',
            'is_link' => 1, 'is_plural' => 0, 'relation_table' => 'companies', 'is_remove' => 0, 'hide' => 0,
        ];
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update(['type' => 'relation', 'relation_table' => 'companies', 'details' => $attrs['details'], 'is_remove' => 0]);
            $this->line("    [{$label}] поле уже есть (id {$existing->id})");
        } else {
            $company = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', 'company_id')->first();
            $sectionId = (int) ($company->section_id ?? 0) ?: (int) $db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $sort = $company ? (int) $company->sort + 1 : (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1;
            $id = $db->table('data_rows')->insertGetId(array_merge(InstallSaleDocsEntities::baseRow($typeId, $sectionId), $attrs, [
                'field' => self::FIELD, 'sort' => $sort, 'is_permanent' => 0,
            ]));
            $this->line("    [{$label}] создано поле «" . self::TITLE . "» (id {$id})");
        }

        if ($this->option('fix-existing')) {
            $this->fixExisting($db, $sb, $slug, $label);
        }
        $this->refresh($db, $sb, $slug);
    }

    private function fixExisting($db, $sb, string $slug, string $label): void
    {
        if (!$sb->hasTable('object_relations') || !$sb->hasTable(ShipmentService::SUPPLIER)) {
            return;
        }
        $links = $db->table('object_relations')
            ->where('source_slug', ShipmentService::SUPPLIER)
            ->where('target_slug', $slug)
            ->get(['source_id', 'target_id']);
        $fixed = 0;
        foreach ($links as $link) {
            $receipt = $db->table($slug)->where('id', $link->target_id)->first(['id', 'company_id', self::FIELD]);
            $order = $db->table(ShipmentService::SUPPLIER)->where('id', $link->source_id)->first(['id', 'company_id', 'shipment_company_id']);
            if (!$receipt || !$order || count(RelationFieldsService::ids($receipt->{self::FIELD}))) {
                continue;
            }
            $supplier = RelationFieldsService::ids($order->company_id)[0] ?? null;
            $current = RelationFieldsService::ids($receipt->company_id);
            if (!$supplier || (count($current) && !in_array($supplier, $current, true))) {
                continue;
            }
            $own = RelationFieldsService::ids($order->shipment_company_id ?? null);
            $db->table($slug)->where('id', $receipt->id)->update([
                self::FIELD => (string) $supplier,
                'company_id' => count($own) ? json_encode(array_values($own)) : null,
            ]);
            $fixed++;
        }
        $this->line("    [{$label}] исправлено приходных: {$fixed}");
    }

    private function refresh($db, $sb, string $slug): void
    {
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
