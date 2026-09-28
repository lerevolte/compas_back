<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\SiteProductSync;
use Illuminate\Console\Command;

class SiteSyncPushProducts extends Command
{
    protected $signature = 'site-sync:push-products
        {target=avixo : <tenant_id>}
        {--ids= : id товаров Компаса через запятую (по умолчанию все с артикулом)}
        {--dry-run : показать, что будет отправлено, без запроса на сайт}';

    protected $description = 'Отправить характеристики товаров с артикулом на сайт (первичная заливка или выборочно)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            if (!SiteProductSync::ready()) {
                $this->error("  ✗ {$tenant->id}: синхронизация с сайтом не настроена (site-sync:install)");
                return;
            }
            $query = Product::query()->whereNotNull('article')->where('article', '!=', '');
            $ids = trim((string) $this->option('ids'));
            if ($ids !== '') {
                $query->whereIntegerInRaw('id', array_map('intval', explode(',', $ids)));
            }
            $total = ['sent' => 0, 'updated' => 0, 'missing' => 0, 'errors' => []];
            $query->orderBy('id')->chunk(SiteProductSync::BATCH, function ($products) use (&$total) {
                if ($this->option('dry-run')) {
                    foreach ($products as $product) {
                        $payload = SiteProductSync::payload($product);
                        if ($payload) {
                            $total['sent']++;
                            $this->line('    ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
                        }
                    }
                    return;
                }
                $stat = SiteProductSync::pushProducts($products);
                $total['sent'] += $stat['sent'];
                $total['updated'] += $stat['updated'];
                $total['missing'] += count($stat['missing']);
                $total['errors'] = array_merge($total['errors'], $stat['errors']);
                if (count($stat['missing'])) {
                    $this->warn('    нет на сайте: ' . implode(', ', array_slice($stat['missing'], 0, 50)) . (count($stat['missing']) > 50 ? '…' : ''));
                }
            });
            foreach (array_slice($total['errors'], 0, 20) as $error) {
                $this->error('    ' . $error);
            }
            $this->info("  ✓ {$tenant->id}: отправлено {$total['sent']}, обновлено {$total['updated']}, нет на сайте {$total['missing']}, ошибок " . count($total['errors']));
        });

        return self::SUCCESS;
    }
}
