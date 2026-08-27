<?php

namespace Modules\Bitrix24\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Bitrix24\Services\B24EntitySync;

class BackfillB24Entities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 1800;
    public $tries = 1;

    public function __construct(
        public string $tenantId,
        public array $entities,
        public int $chunk = 500
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
            if (!$svc) {
                return;
            }
            foreach ($this->entities as $entity) {
                if (B24EntitySync::backfillState($entity)['done']) {
                    continue;
                }
                try {
                    $result = $svc->backfill($entity, $this->chunk);
                } catch (\Throwable $e) {
                    Log::channel('bitrix24')->error('entity-backfill: chunk failed', [
                        'tenant' => $this->tenantId, 'entity' => $entity, 'error' => $e->getMessage(),
                    ]);
                    return;
                }
                Log::channel('bitrix24')->info('entity-backfill: chunk done', [
                    'tenant' => $this->tenantId, 'entity' => $entity,
                ] + $result);
                self::dispatch($this->tenantId, $this->entities, $this->chunk);
                return;
            }
            Log::channel('bitrix24')->info('entity-backfill: finished', [
                'tenant' => $this->tenantId, 'entities' => $this->entities,
            ]);
        });
    }
}
