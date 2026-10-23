<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DeliveryCostService;
use Illuminate\Console\Command;

class InstallDeliveryCostField extends Command
{
    protected $signature = 'logistic:install-delivery-cost
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Поле «Себестоимость доставки» у задач логистики (доля стоимости маршрута по весу) и у заказов покупателей/поставщикам (сумма по задачам)';

    public const ANCHORS = ['delivery_price', 'weight'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds' || $target === 'all-tenants') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);
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
        $dry = (bool) $this->option('dry-run');
        $field = DeliveryCostService::FIELD;
        $installed = [];

        foreach (array_merge([DeliveryCostService::TASKS], DeliveryCostService::ORDERS) as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                continue;
            }
            $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
            if ($dry) {
                $this->line("    [{$label}] {$slug}: " . ($existing ? "поле уже есть (id {$existing->id})" : 'будет создано'));
                continue;
            }
            if (!$sb->hasColumn($slug, $field)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$field}` DECIMAL(12,2) NULL");
            }
            $attrs = [
                'type' => 'number',
                'title' => DeliveryCostService::TITLE,
                'only_read' => 1,
                'is_program' => 1,
                'is_remove' => 0,
                'is_permanent' => 1,
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
                    'set_color' => 0, 'related_field' => null, 'is_unique' => 0,
                ];
                $id = $db->table('data_rows')->insertGetId(array_intersect_key($row, array_flip($sb->getColumnListing('data_rows'))));
            }
            $installed[] = $slug;
            $this->line("    [{$label}] {$slug}: поле «" . DeliveryCostService::TITLE . "» id {$id}" . ($existing ? ' (обновлено)' : ' (создано)'));
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        if ($dry || !$inTenant || !count($installed)) {
            return;
        }
        DeliveryCostService::forget();
        $result = DeliveryCostService::backfill();
        $orders = [];
        foreach ($result['orders'] as $slug => $count) {
            $orders[] = "{$slug} {$count}";
        }
        $this->line("    [{$label}] пересчитано маршрутов: {$result['routes']}" . (count($orders) ? ', заказов: ' . implode(', ', $orders) : ''));
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
