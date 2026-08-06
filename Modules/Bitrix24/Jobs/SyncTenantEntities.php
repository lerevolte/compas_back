<?php

namespace Modules\Bitrix24\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Bitrix24\Services\B24EntitySync;

/**
 * Инкрементальный прогон синка сущностей Bitrix24 для одного тенанта.
 * Ставится кроном (bitrix24:sync-entities --queue) — тяжёлая работа уходит
 * из крон-процесса в queue:work.
 */
class SyncTenantEntities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 1800;
    public $tries = 1;

    public function __construct(
        public string $tenantId,
        public int $chunk = 200
    ) {
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }
        $tenant->run(function () {
            $svc = B24EntitySync::ready() ? B24EntitySync::make() : null;
            if ($svc) {
                $result = $svc->runIncremental($this->chunk);
                Log::channel('bitrix24')->info('entity-sync: tenant job done', ['tenant' => $this->tenantId] + $result);
            }

            $productSvc = \Modules\Bitrix24\Services\B24ProductSync::make();
            if ($productSvc) {
                $result = $productSvc->runIncremental($this->chunk);
                Log::channel('bitrix24')->info('product-sync: tenant job done', ['tenant' => $this->tenantId] + $result);
            }
        });
    }
}
