<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Bitrix24\Services\B24EntitySync;

class B24FixInvoiceTimes extends Command
{
    protected $signature = 'b24:fix-invoice-times {target=avixo : tenant id портала}';

    protected $description = 'Восстановить время создания счетов из Bitrix24 (DATE_INSERT) у записей со временем 00:00:00';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }

        $tenant->run(function () {
            $svc = B24EntitySync::ready() ? B24EntitySync::make() : null;
            if (!$svc) {
                $this->line('Синк Bitrix24 не настроен');
                return;
            }
            $stat = $svc->fixInvoiceTimes(fn ($s) => $this->line("  проверено {$s['checked']}, исправлено {$s['fixed']}"));
            $this->info("Готово: проверено {$stat['checked']}, время восстановлено у {$stat['fixed']}");
        });

        return self::SUCCESS;
    }
}
