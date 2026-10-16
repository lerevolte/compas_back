<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCExportService;
use App\Services\OneC\OneCSupplierOrderService;
use Illuminate\Console\Command;

class OneCInstallPurchases extends Command
{
    protected $signature = 'onec:install-purchases {target=avixo : <tenant_id>} {--since= : выгружать документы, созданные с этой даты (по умолчанию — с момента первой установки)}';

    protected $description = 'Обмен с 1С по закупкам: выгрузка заказов поставщикам (поля «Состояние», «Договор», «Склад») и приходных накладных';

    public const SLUGS = [OneCSupplierOrderService::SLUG, 'receipt_invoices'];

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $since = $this->option('since');
        if ($since !== null && $since !== '' && !strtotime((string) $since)) {
            $this->error('Некорректная дата в --since');
            return self::FAILURE;
        }
        $result = self::SUCCESS;
        $tenant->run(function () use ($tenant, $since, &$result) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            if (!$sb->hasTable(OneCExportService::CONFIG_TABLE) || !$db->table(OneCExportService::CONFIG_TABLE)->exists()) {
                $this->error("  [{$tenant->id}] обмен с 1С не установлен — сначала onec:install");
                $result = self::FAILURE;
                return;
            }
            OneCExportService::ensureTables($db);
            OneCSupplierOrderService::install($db, $sb, fn (string $message) => $this->line("    [{$tenant->id}] supplier_orders: {$message}"));

            $config = $db->table(OneCExportService::CONFIG_TABLE)->orderBy('id')->first();
            $bySlug = OneCExportService::slugSince($config);
            foreach (self::SLUGS as $slug) {
                if ($since !== null && $since !== '') {
                    $bySlug[$slug] = date('Y-m-d H:i:s', strtotime((string) $since));
                } elseif (empty($bySlug[$slug])) {
                    $bySlug[$slug] = now()->format('Y-m-d H:i:s');
                }
                $this->line("    [{$tenant->id}] {$slug}: выгрузка документов с {$bySlug[$slug]}");
            }
            $db->table(OneCExportService::CONFIG_TABLE)->where('id', $config->id)->update([
                OneCExportService::SLUG_SINCE_FIELD => json_encode($bySlug),
                'updated_at' => now(),
            ]);

            OneCExportService::forget();
            OneCSupplierOrderService::forget();
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            $this->info("  ✓ {$tenant->id}: выгрузка закупок в 1С включена");
            $this->line("    GET  https://{$tenant->id}.compas.pro/api/1c/supplier-orders?token=<токен>");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/supplier-orders/confirm?token=<токен>  {\"documents\": [{\"number\": \"cmps-z1\", \"ref\": \"<GUID>\", \"state\": \"Оплатили\"}]}");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/supplier-orders/state?token=<токен>  {\"documents\": [{\"number\": \"cmps-z1\", \"state\": 6}]}");
            $this->line("    приходные накладные — в GET /api/1c/documents (type receipt_invoice)");
        });

        return $result;
    }
}
