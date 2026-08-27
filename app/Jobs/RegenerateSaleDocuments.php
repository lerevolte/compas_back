<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\SaleDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RegenerateSaleDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 600;
    public $tries = 1;

    public function __construct(
        public string $tenantId,
        public array $docs
    ) {
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }
        $tenant->run(function () {
            foreach ($this->docs as $doc) {
                try {
                    SaleDocumentService::regenerate((string) $doc[0], (int) $doc[1]);
                } catch (\Throwable $e) {
                    Log::warning('sale-doc: регенерация в очереди не удалась', [
                        'tenant' => $this->tenantId,
                        'doc' => $doc[0] . '#' . $doc[1],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
