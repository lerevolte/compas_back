<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ProductImageStore;
use App\Services\SiteProductSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiteSyncPullPhotos extends Command
{
    protected $signature = 'site-sync:pull-photos
        {target=avixo : <tenant_id>}
        {--ids= : id товаров Компаса через запятую (по умолчанию все с артикулом без фото)}
        {--all : заменить фото и у товаров, где оно уже есть}
        {--dry-run : показать, у скольких товаров на сайте есть главное фото, без записи}';

    protected $description = 'Подтянуть главное фото товара с сайта по id товара на сайте (артикулу)';

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
            $query = DB::table('products')->whereNull('deleted_at')->whereNotNull('article')->where('article', '!=', '');
            if (!$all) {
                $query->where(fn ($q) => $q->whereNull('photo')->orWhereIn('photo', ['', '[]', 'null']));
            }
            $ids = trim((string) $this->option('ids'));
            if ($ids !== '') {
                $query->whereIn('id', array_map('intval', explode(',', $ids)));
            }
            $stat = ['checked' => 0, 'with_photo' => 0, 'filled' => 0, 'failed' => 0, 'missing' => 0, 'errors' => 0];
            $query->orderBy('id')->select(['id', 'article'])->chunkById(200, function ($rows) use (&$stat, $dry) {
                $byArticle = [];
                foreach ($rows as $row) {
                    if (ctype_digit(trim((string) $row->article))) {
                        $byArticle[(int) $row->article][] = $row;
                    }
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
                $photos = [];
                foreach ($result['items'] as $item) {
                    $photo = trim((string) ($item['photo'] ?? ''));
                    if ($photo !== '') {
                        $photos[(int) ($item['id'] ?? 0)] = $photo;
                    }
                }
                foreach ($byArticle as $article => $items) {
                    foreach ($items as $row) {
                        $stat['checked']++;
                        $url = $photos[$article] ?? null;
                        if (!$url) {
                            continue;
                        }
                        $stat['with_photo']++;
                        if ($dry) {
                            if ($stat['with_photo'] <= 20) {
                                $this->line("    #{$row->id} (сайт {$article}): {$url}");
                            }
                            continue;
                        }
                        try {
                            $photo = retry(3, fn () => ProductImageStore::fromUrl($url, null, ['site_product_id' => (string) $article]), 2000);
                        } catch (\Throwable $e) {
                            $photo = null;
                        }
                        if (!$photo) {
                            $stat['failed']++;
                            $this->warn("    #{$row->id} (сайт {$article}): не удалось скачать {$url}");
                            continue;
                        }
                        DB::table('products')->where('id', $row->id)->update([
                            'photo' => json_encode([$photo], JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                        $stat['filled']++;
                    }
                }
            });
            $this->info("  ✓ {$tenant->id}: проверено {$stat['checked']}, фото на сайте есть у {$stat['with_photo']}, подтянуто {$stat['filled']}, не скачалось {$stat['failed']}, нет на сайте {$stat['missing']}, ошибок сайта {$stat['errors']}" . ($dry ? ' (dry-run)' : ''));
        });

        return self::SUCCESS;
    }
}
