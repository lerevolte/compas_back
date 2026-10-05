<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Bitrix24BackfillProductPhotos extends Command
{
    protected $signature = 'bitrix24:backfill-product-photos
        {target=avixo : all-tenants | <tenant_id>}
        {--ids= : id товаров Компаса через запятую (по умолчанию все товары Bitrix24 без фото)}
        {--dry-run : только посчитать, у скольких товаров без фото картинка есть в Bitrix24}';

    protected $description = 'Подтянуть фото из Bitrix24 товарам, у которых в Компасе фото нет';

    public function handle(): int
    {
        $target = $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : Tenant::where('id', $target)->get();
        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant, $dry) {
                    $svc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if (!$svc) {
                        $this->line("  − {$tenant->id}: синк товаров не настроен, пропуск");
                        return;
                    }
                    $query = DB::table('products')
                        ->whereNull('deleted_at')
                        ->whereNotNull('id_b24')
                        ->where('id_b24', '!=', '')
                        ->where(fn ($q) => $q->whereNull('photo')->orWhereIn('photo', ['', '[]', 'null']));
                    $ids = trim((string) $this->option('ids'));
                    if ($ids !== '') {
                        $query->whereIn('id', array_map('intval', explode(',', $ids)));
                    }
                    $stat = ['checked' => 0, 'with_picture' => 0, 'filled' => 0, 'errors' => 0];
                    $query->orderBy('id')->select(['id', 'id_b24'])->chunkById(500, function ($rows) use ($svc, $dry, &$stat) {
                        $stat['checked'] += count($rows);
                        $pictures = retry(3, fn () => $svc->pictureIdsFor($rows->pluck('id_b24')->all()), 3000);
                        foreach ($rows as $row) {
                            if (!isset($pictures[(string) $row->id_b24])) {
                                continue;
                            }
                            $stat['with_picture']++;
                            if ($dry) {
                                if ($stat['with_picture'] <= 20) {
                                    $this->line("    #{$row->id} (B24 {$row->id_b24}): фото есть в Bitrix24");
                                }
                                continue;
                            }
                            try {
                                $product = retry(3, fn () => $svc->pullProductById($row->id_b24), 3000);
                                $photo = $product ? json_decode((string) $product->photo, true) : null;
                                if (is_array($photo) && count($photo)) {
                                    $stat['filled']++;
                                } else {
                                    $this->warn("    #{$row->id} (B24 {$row->id_b24}): картинку скачать не удалось");
                                }
                            } catch (\Throwable $e) {
                                $stat['errors']++;
                                $this->warn("    #{$row->id} (B24 {$row->id_b24}): " . $e->getMessage());
                            }
                        }
                    });
                    $this->info("  ✓ {$tenant->id}: без фото проверено {$stat['checked']}, картинка есть в Bitrix24 у {$stat['with_picture']}, подтянуто {$stat['filled']}, ошибок {$stat['errors']}" . ($dry ? ' (dry-run)' : ''));
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
