<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SaleDocumentService;
use Illuminate\Console\Command;

class RegenerateSaleDocs extends Command
{
    protected $signature = 'sale-docs:regenerate
        {target=all-tenants : all-tenants | <tenant_id>}
        {--slug=payment_invoices : сущность (payment_invoices)}
        {--sync : выполнить сразу, без очереди}';

    protected $description = 'Перегенерировать PDF документов продаж (счета на оплату) по новому шаблону';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $slug = (string) $this->option('slug');
        if (!isset(SaleDocumentService::TARGETS[$slug]) || !(SaleDocumentService::TARGETS[$slug]['pdf'] ?? true)) {
            $this->error("Для '{$slug}' PDF не формируется");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->regenerate((string) $tenant->id, $slug));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function regenerate(string $label, string $slug): void
    {
        if (!\Schema::hasTable($slug)) {
            return;
        }
        $ids = \DB::table($slug)->whereNull('deleted_at')->orderBy('id')->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (!count($ids)) {
            $this->line("    [{$label}] {$slug}: документов нет");
            return;
        }
        if ($this->option('sync')) {
            $done = 0;
            foreach ($ids as $id) {
                if (SaleDocumentService::regenerate($slug, $id)) {
                    $done++;
                }
            }
            $this->info("  ✓ {$label}: {$slug} перегенерировано {$done} из " . count($ids));
            return;
        }
        SaleDocumentService::queue(array_map(fn ($id) => [$slug, $id], $ids));
        $this->info("  ✓ {$label}: {$slug} поставлено в очередь " . count($ids));
    }
}
