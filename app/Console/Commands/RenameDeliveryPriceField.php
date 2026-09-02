<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class RenameDeliveryPriceField extends Command
{
    protected $signature = 'logistic:rename-delivery-price
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Переименовать поле «Цена доставки» (logistic_tasks.delivery_price) в «Цена услуг»';

    public const OLD_TITLE = 'Цена доставки';
    public const NEW_TITLE = 'Цена услуг';

    public function handle(): int
    {
        $target = $this->argument('target');

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
        $tenant->run(fn () => $this->rename(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function rename($db, string $label, bool $inTenant): void
    {
        $typeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            $this->warn("    [{$label}] сущность logistic_tasks не найдена, пропуск");
            return;
        }

        $updated = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'delivery_price')
            ->where('title', self::OLD_TITLE)
            ->update(['title' => self::NEW_TITLE]);

        if ($updated) {
            try {
                if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/logistic_tasks')->update(['updated_at' => now()]);
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

        $this->line("    [{$label}] переименовано строк: {$updated}");
    }
}
