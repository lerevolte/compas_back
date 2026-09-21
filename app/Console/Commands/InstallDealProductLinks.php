<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ReverseLinkService;
use Illuminate\Console\Command;

class InstallDealProductLinks extends Command
{
    protected $signature = 'entity:install-deal-product-links
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Поле и вкладка «Заказы покупателей» у товаров: список заказов, в составе которых есть товар';

    public const FIELD = 'deal_id';
    public const TITLE = 'Заказы покупателей';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->installInto(\DB::connection(), $target, true));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    public static function install($db): ?string
    {
        if (!$db->table('data_types')->where('slug', 'deals')->exists() || !$db->getSchemaBuilder()->hasTable('deals')) {
            return null;
        }
        $result = ReverseLinkService::installField($db, 'products', self::FIELD, self::TITLE, 'deals');
        if ($result === null) {
            return null;
        }
        $map = [];
        foreach ($db->table('deals')->whereNull('deleted_at')->get(['id', 'products']) as $deal) {
            foreach (\App\Models\SupplierOrder::productIds($deal->products) as $pid) {
                $map[$pid][] = (int) $deal->id;
            }
        }
        ReverseLinkService::backfill($db, 'products', self::FIELD, $map);

        return $result;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $result = self::install($db);
        if ($result === null) {
            $this->line("    [{$label}] заказов покупателей или товаров нет, пропуск");
            return;
        }
        $this->line("    [{$label}] products: поле «" . self::TITLE . '» ' . ($result === 'created' ? 'добавлено' : 'обновлено'));
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
