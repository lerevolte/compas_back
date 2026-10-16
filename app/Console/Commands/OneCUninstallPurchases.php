<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCExportService;
use App\Services\OneC\OneCSupplierOrderService;
use Illuminate\Console\Command;

class OneCUninstallPurchases extends Command
{
    protected $signature = 'onec:uninstall-purchases {target=avixo : <tenant_id>} {--purge : удалить колонку «Состояние» и состояние выгрузки}';

    protected $description = 'Снять обмен с 1С по закупкам: выгрузку заказов поставщикам и приходных, поле «Состояние»';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            if ($sb->hasTable(OneCExportService::CONFIG_TABLE) && $sb->hasColumn(OneCExportService::CONFIG_TABLE, OneCExportService::SLUG_SINCE_FIELD)) {
                foreach ($db->table(OneCExportService::CONFIG_TABLE)->get() as $config) {
                    $bySlug = array_diff_key(OneCExportService::slugSince($config), array_flip(OneCInstallPurchases::SLUGS));
                    $db->table(OneCExportService::CONFIG_TABLE)->where('id', $config->id)->update([
                        OneCExportService::SLUG_SINCE_FIELD => count($bySlug) ? json_encode($bySlug) : null,
                    ]);
                }
            }
            $typeId = $db->table('data_types')->where('slug', OneCSupplierOrderService::SLUG)->value('id');
            if ($typeId) {
                $fieldIds = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCSupplierOrderService::STATE_FIELD)->pluck('id')->all();
                if (count($fieldIds)) {
                    $db->table('field_values')->whereIn('field_id', $fieldIds)->delete();
                    $db->table('section_fields_sort')->whereIn('field_id', $fieldIds)->delete();
                    $db->table('data_rows')->whereIn('id', $fieldIds)->delete();
                }
                $this->line("    [{$tenant->id}] supplier_orders: поле «" . OneCSupplierOrderService::STATE_TITLE . '» снято');
            }
            if ($this->option('purge')) {
                if ($sb->hasTable(OneCSupplierOrderService::SLUG) && $sb->hasColumn(OneCSupplierOrderService::SLUG, OneCSupplierOrderService::STATE_FIELD)) {
                    $sb->table(OneCSupplierOrderService::SLUG, fn ($table) => $table->dropColumn(OneCSupplierOrderService::STATE_FIELD));
                }
                if ($sb->hasTable(OneCExportService::STATE_TABLE)) {
                    $db->table(OneCExportService::STATE_TABLE)->where('slug', OneCSupplierOrderService::SLUG)->delete();
                }
                $this->line("    [{$tenant->id}] колонка «Состояние» и состояние выгрузки удалены");
            }
            OneCExportService::forget();
            OneCSupplierOrderService::forget();
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . OneCSupplierOrderService::SLUG)->update(['updated_at' => now()]);
                }
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            $this->info("  ✓ {$tenant->id}: выгрузка закупок в 1С снята");
        });

        return self::SUCCESS;
    }
}
