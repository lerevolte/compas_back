<?php

namespace App\Console\Commands;

use App\Models\SupplierOrder;
use App\Models\Tenant;
use App\Services\ShipmentService;
use Illuminate\Console\Command;

class RecalcSupplierOrderSums extends Command
{
    protected $signature = 'supplier-orders:recalc-sums
        {target=all-tenants : all-tenants | <tenant_id>}';

    protected $description = 'Пересчитать сумму заказов поставщикам по цене закупки с учётом НДС';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->recalc((string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function recalc(string $label): void
    {
        if (!\Schema::hasTable('supplier_orders')) {
            return;
        }
        $changed = 0;
        foreach (SupplierOrder::withTrashed()->orderBy('id')->get() as $order) {
            $products = array_values(array_filter(ShipmentService::decode($order->products), 'is_array'));
            $total = 0.0;
            foreach ($products as $product) {
                $total += ShipmentService::lineTotal($product, 'purchase_price');
            }
            $sum = $total > 0 ? rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') : null;
            if ((string) $order->sum === (string) $sum) {
                continue;
            }
            $order->sum = $sum;
            $order->timestamps = false;
            $order->saveQuietly();
            $order->timestamps = true;
            $changed++;
        }
        $this->info("  ✓ {$label}: пересчитано заказов {$changed}");
    }
}
