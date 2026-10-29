<?php

namespace App\Console\Commands;

use App\Models\Settings;
use App\Models\Tenant;
use App\Services\RelationFieldsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeTransferDealsPlural extends Command
{
    protected $signature = 'transfers:plural-deals {target=all : seeds | all-tenants | all | <tenant_id>}';

    protected $description = 'Сделать поле «Заказ покупателя» у заказов на перемещение и перемещений множественным';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $failed = false;

        if ($target === 'seeds' || $target === 'all') {
            try {
                $this->updatePortal(DB::connection('seeds'), 'admin_seeds', false);
            } catch (\Throwable $e) {
                $this->error('admin_seeds: ' . $e->getMessage());
                $failed = true;
            }
        }

        if ($target === 'seeds') {
            return $failed ? self::FAILURE : self::SUCCESS;
        }

        if ($target === 'all-tenants' || $target === 'all') {
            $tenants = Tenant::get();
        } else {
            $tenant = Tenant::find($target);
            if (!$tenant) {
                $this->error("Портал '{$target}' не найден");
                return self::FAILURE;
            }
            $tenants = collect([$tenant]);
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->updatePortal(DB::connection(), (string) $tenant->id, true));
            } catch (\Throwable $e) {
                $this->error("{$tenant->id}: " . $e->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function updatePortal($db, string $label, bool $inTenant): void
    {
        $updated = false;
        foreach (['transfer_orders', 'transfers'] as $slug) {
            $updated = $this->updateField($db, $label, $slug) || $updated;
        }
        if ($updated && $inTenant) {
            Settings::clear_cache();
        }
    }

    private function updateField($db, string $label, string $slug): bool
    {
        $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
        $field = $typeId ? $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'related_deals')
            ->where('type', 'relation')
            ->where('relation_table', 'deals')
            ->where('is_remove', 0)
            ->first() : null;

        if (!$field || !$db->getSchemaBuilder()->hasColumn($slug, 'related_deals')) {
            $this->line("{$label} {$slug}: поле не установлено, пропуск");
            return false;
        }

        $columnType = strtolower((string) $db->table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $slug)
            ->where('COLUMN_NAME', 'related_deals')
            ->value('DATA_TYPE'));

        if ($columnType !== 'text') {
            $db->statement("ALTER TABLE `{$slug}` MODIFY `related_deals` TEXT NULL");
        }

        $db->table($slug)->whereNotNull('related_deals')->orderBy('id')->chunkById(500, function ($rows) use ($db, $slug) {
            foreach ($rows as $row) {
                $ids = RelationFieldsService::ids($row->related_deals);
                $value = json_encode($ids);
                if ($row->related_deals !== $value) {
                    $db->table($slug)->where('id', $row->id)->update(['related_deals' => $value]);
                }
            }
        });

        $db->table('data_rows')->where('id', $field->id)->update(['is_plural' => 1]);
        if ($db->getSchemaBuilder()->hasTable('local_cache')) {
            $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
        }
        $this->info("{$label} {$slug}: поле «Заказ покупателя» множественное");

        return true;
    }
}
