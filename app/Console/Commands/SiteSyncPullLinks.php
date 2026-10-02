<?php

namespace App\Console\Commands;

use App\Models\History;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\SiteProductSync;
use Illuminate\Console\Command;
use Modules\Bitrix24\Services\B24ProductSync;

class SiteSyncPullLinks extends Command
{
    protected $signature = 'site-sync:pull-links
        {target=avixo : <tenant_id>}
        {--ids= : id товаров Компаса через запятую (по умолчанию все с артикулом без ссылки)}
        {--all : обновить ссылку и у товаров, где она уже есть}
        {--dry-run : показать, что будет заполнено, без записи}';

    protected $description = 'Заполнить ссылку на сайт в названии товара (и в Bitrix24) по id товара на сайте';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            if (!SiteProductSync::ready()) {
                $this->error("  ✗ {$tenant->id}: синхронизация с сайтом не настроена");
                return;
            }
            $dry = (bool) $this->option('dry-run');
            $all = (bool) $this->option('all');
            $b24 = B24ProductSync::make();
            $query = Product::query()->whereNotNull('article')->where('article', '!=', '');
            $ids = trim((string) $this->option('ids'));
            if ($ids !== '') {
                $query->whereIn('id', array_map('intval', explode(',', $ids)));
            }
            $stat = ['checked' => 0, 'updated' => 0, 'b24' => 0, 'b24_errors' => 0, 'missing' => 0, 'no_url' => 0, 'errors' => 0];
            $query->orderBy('id')->chunkById(200, function ($products) use (&$stat, $dry, $all, $b24) {
                $byArticle = [];
                foreach ($products as $product) {
                    $article = trim((string) $product->article);
                    if (!ctype_digit($article)) {
                        continue;
                    }
                    if (!$all && B24ProductSync::linkFromName($product->name)) {
                        continue;
                    }
                    $byArticle[(int) $article][] = $product;
                }
                if (!count($byArticle)) {
                    return;
                }
                $result = SiteProductSync::links(array_keys($byArticle));
                if ($result['error']) {
                    $stat['errors']++;
                    $this->error('    ' . $result['error']);
                    return;
                }
                $stat['missing'] += count($result['missing']);
                $urls = [];
                foreach ($result['items'] as $item) {
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '') {
                        $urls[(int) ($item['id'] ?? 0)] = $url;
                    }
                }
                foreach ($byArticle as $article => $items) {
                    foreach ($items as $product) {
                        $stat['checked']++;
                        $url = $urls[$article] ?? null;
                        if (!$url) {
                            if (!in_array($article, array_map('intval', $result['missing']), true)) {
                                $stat['no_url']++;
                            }
                            continue;
                        }
                        if (B24ProductSync::linkFromName($product->name) === $url) {
                            continue;
                        }
                        $stat['updated']++;
                        if ($dry) {
                            if ($stat['updated'] <= 20) {
                                $this->line("    #{$product->id} (сайт {$article}): {$url}");
                            }
                            continue;
                        }
                        $name = json_encode(['value' => B24ProductSync::nameText($product->name), 'external_link' => $url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        B24ProductSync::$muted = true;
                        try {
                            try {
                                History::saveForObject('products', [['id' => $product->id, 'name' => $name]]);
                            } catch (\Throwable $e) {
                            }
                            $product->name = $name;
                            $product->save();
                        } finally {
                            B24ProductSync::$muted = false;
                        }
                        if ($b24 && $product->id_b24) {
                            try {
                                retry(3, fn () => $b24->pushProduct($product, ['name']), 2000);
                                $stat['b24']++;
                            } catch (\Throwable $e) {
                                $stat['b24_errors']++;
                                $this->warn("    #{$product->id}: Bitrix24 — " . $e->getMessage());
                            }
                        }
                    }
                }
            });
            $this->info("  ✓ {$tenant->id}: проверено {$stat['checked']}, ссылка заполнена у {$stat['updated']}, в Bitrix24 обновлено {$stat['b24']}"
                . ($stat['b24_errors'] ? ", ошибок Bitrix24 {$stat['b24_errors']}" : '')
                . ", нет на сайте {$stat['missing']}, без адреса страницы {$stat['no_url']}, ошибок сайта {$stat['errors']}" . ($dry ? ' (dry-run)' : ''));
        });

        return self::SUCCESS;
    }
}
