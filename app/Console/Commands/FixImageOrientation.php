<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Intervention\Image\ImageManagerStatic as Image;

class FixImageOrientation extends Command
{
    protected $signature = 'files:fix-orientation
        {tenant? : id портала (например logistopt6); без аргумента — все порталы}
        {--dry-run : только показать, какие файлы будут развёрнуты}';

    protected $description = 'Применить EXIF-поворот к пикселям уже загруженных фото (превью генерятся из пикселей и иначе остаются повёрнутыми)';

    public function handle(): int
    {
        if (!function_exists('exif_read_data')) {
            $this->error('Расширение ext-exif не установлено — определить поворот невозможно.');
            return self::FAILURE;
        }

        $target = $this->argument('tenant');
        $tenants = $target ? collect([Tenant::find($target)])->filter() : Tenant::all();
        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден.");
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $totalFixed = 0;

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($dry, &$totalFixed) {
                $tid = tenant('id');
                $fixed = 0;
                $checked = 0;
                \DB::table('files')->orderBy('id')->chunkById(500, function ($files) use ($dry, &$fixed, &$checked) {
                    foreach ($files as $f) {
                        $ext = strtolower(pathinfo((string) $f->path, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg'])) {
                            continue;
                        }
                        $path = storage_path('app/public/'.$f->path);
                        if (!is_file($path)) {
                            continue;
                        }
                        $checked++;
                        $exif = @exif_read_data($path);
                        $orientation = (int) ($exif['Orientation'] ?? 1);
                        if ($orientation <= 1) {
                            continue;
                        }
                        if ($dry) {
                            $this->line("  [dry] {$f->id} {$f->path} (orientation {$orientation})");
                            $fixed++;
                            continue;
                        }
                        try {
                            $img = Image::make($path);
                            $img->orientate();
                            $img->save($path);
                            $fixed++;
                        } catch (\Throwable $e) {
                            $this->warn("  ошибка {$f->path}: ".$e->getMessage());
                        }
                    }
                });
                $this->info("{$tid}: проверено {$checked}, развёрнуто {$fixed}");
                $totalFixed += $fixed;
            });
        }

        $this->info(($dry ? 'Будет развёрнуто: ' : 'Развёрнуто: ').$totalFixed);
        if (!$dry && $totalFixed > 0) {
            $this->warn('Старые превью закешированы. Очистите кеш, чтобы они перегенерировались:');
            $this->warn('  rm -rf storage/app/public/thumbnails/*');
        }
        return self::SUCCESS;
    }
}
