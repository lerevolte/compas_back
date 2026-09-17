<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class RenameDeliveryPriceField extends Command
{
    protected $signature = 'logistic:rename-delivery-price
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Поле «Цена услуг» (delivery_price) у задач логистики и заказов покупателей: переименовать из «Цена доставки» и сделать только для чтения (считается из услуг состава)';

    public const OLD_TITLE = 'Цена доставки';
    public const NEW_TITLE = 'Цена услуг';
    public const SLUGS = ['logistic_tasks', 'deals'];

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
        $total = 0;
        foreach (self::SLUGS as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                $this->warn("    [{$label}] сущность {$slug} не найдена, пропуск");
                continue;
            }

            $updated = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'delivery_price')
                ->where('title', self::OLD_TITLE)
                ->update(['title' => self::NEW_TITLE]);
            $updated += $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'delivery_price')
                ->where(fn ($q) => $q->whereNull('only_read')->orWhere('only_read', 0))
                ->update(['only_read' => 1]);
            $total += $updated;

            if ($updated) {
                try {
                    if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                        $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if ($total && $inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->line("    [{$label}] обновлено полей: {$total}");
    }
}
