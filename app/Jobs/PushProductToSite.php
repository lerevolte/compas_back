<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\SiteProductSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PushProductToSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [60, 300];

    public function __construct(
        public string $tenantId,
        public int $productId
    ) {
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            return;
        }
        $tenant->run(function () {
            if (!SiteProductSync::ready()) {
                return;
            }
            $product = Product::withTrashed()->find($this->productId);
            if (!$product || $product->trashed()) {
                return;
            }
            $stat = SiteProductSync::pushProducts([$product]);
            if (count($stat['errors'])) {
                Log::channel('site_sync')->warning('site-sync: товар не обновлён на сайте', ['tenant' => $this->tenantId, 'product_id' => $this->productId, 'errors' => $stat['errors']]);
                throw new \RuntimeException(implode('; ', $stat['errors']));
            }
        });
    }
}
