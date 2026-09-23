<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class RenameSupplierOrderReceiverField extends Command
{
    protected $signature = 'supplier-orders:rename-receiver-field
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Переименовать поле «Компания отгрузки» (shipment_company_id) у заказов поставщикам в «Компания получатель». Порталы без сущности пропускаются';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds' || $target === 'all-tenants') {
            $this->rename(\DB::connection('seeds'), 'admin_seeds', false);
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->rename(\DB::connection(), (string) $tenant->id, true));
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
        $tenant->run(fn () => $this->rename(\DB::connection(), (string) $tenant->id, true));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function rename($db, string $label, bool $inTenant): void
    {
        $slug = InstallSupplierOrdersEntity::SLUG;
        $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId) {
            $this->line("    [{$label}] {$slug}: сущности нет, пропуск");
            return;
        }

        $updated = InstallSupplierOrdersEntity::renameReceiverField($db, (int) $typeId);
        if (!$updated) {
            $this->line("    [{$label}] {$slug}: поле уже называется «" . InstallSupplierOrdersEntity::RECEIVER_FIELD_TITLE . "» или отсутствует");
            return;
        }

        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->line("    [{$label}] {$slug}: переименовано полей: {$updated}");
    }
}
