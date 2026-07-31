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
 * Обработка одного события вебхука Bitrix24 (deal/contact/company) в очереди —
 * сам вебхук отвечает мгновенно, работа уходит в queue:work.
 */
class ProcessEntityHook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 120;
    public $tries = 1;

    public function __construct(
        public string $tenantId,
        public string $type,
        public string $entityId,
        public bool $isDelete = false
    ) {
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }
        $tenant->run(function () {
            $svc = B24EntitySync::make();
            if (!$svc) {
                return;
            }
            try {
                switch ($this->type) {
                    case 'deal':
                        if ($this->isDelete) {
                            \App\Models\Deal::where('b24_id', $this->entityId)->first()?->delete();
                        } else {
                            $svc->pullDealById($this->entityId);
                        }
                        break;
                    case 'contact':
                        if ($this->isDelete) {
                            \App\Models\Contact::where('b24_id', $this->entityId)->first()?->delete();
                        } else {
                            $svc->pullContactById($this->entityId);
                        }
                        break;
                    case 'company':
                        if (!$this->isDelete) {
                            $svc->pullCompanyById($this->entityId);
                        }
                        break;
                }
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->error('entity-hook job: failed', [
                    'tenant' => $this->tenantId,
                    'type'   => $this->type,
                    'id'     => $this->entityId,
                    'error'  => $e->getMessage(),
                ]);
            }
        });
    }
}
