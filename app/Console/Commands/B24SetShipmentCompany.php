<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class B24SetShipmentCompany extends Command
{
    public const ENTITIES = ['deals', 'payment_invoices', 'expense_invoices', 'product_returns', 'logistic_tasks', 'addresses', 'warehouses'];

    protected $signature = 'b24:set-shipment-company
        {target=avixo : tenant id портала}
        {company_id? : id компании отгрузки по умолчанию}
        {--backfill : проставить компанию всем записям сущностей с пустым значением}
        {--show : показать текущую настройку}';

    protected $description = 'Компания отгрузки по умолчанию для заказов из Bitrix24 (+ бэкфилл пустых значений по сущностям портала)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }

        $code = self::SUCCESS;
        $tenant->run(function () use (&$code) {
            if ($this->option('show')) {
                $value = DB::table('settings')->where('type', 'b24_default_shipment_company')->value('value');
                $this->line('b24_default_shipment_company: ' . ($value ?: '—'));
                return;
            }

            $companyId = (int) $this->argument('company_id');
            if (!$companyId) {
                $this->error('Укажите company_id');
                $code = self::FAILURE;
                return;
            }
            if (!Schema::hasTable('companies') || !DB::table('companies')->where('id', $companyId)->whereNull('deleted_at')->exists()) {
                $this->error("Компания {$companyId} не найдена");
                $code = self::FAILURE;
                return;
            }

            DB::table('settings')->updateOrInsert(
                ['type' => 'b24_default_shipment_company', 'entity' => null, 'user_id' => null],
                ['key' => 'b24_default_shipment_company', 'value' => (string) $companyId]
            );
            $this->info("Компания отгрузки по умолчанию: {$companyId}");

            if (!$this->option('backfill')) {
                return;
            }
            foreach (self::ENTITIES as $table) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'shipment_company_id')) {
                    continue;
                }
                $updated = DB::table($table)
                    ->whereNull('deleted_at')
                    ->where(fn ($q) => $q->whereNull('shipment_company_id')->orWhere('shipment_company_id', '')->orWhere('shipment_company_id', '0')->orWhere('shipment_company_id', '[]'))
                    ->update(['shipment_company_id' => $companyId]);
                $this->line("  {$table}: заполнено {$updated}");
            }
        });

        return $code;
    }
}
