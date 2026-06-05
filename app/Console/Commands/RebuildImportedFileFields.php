<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Превращает значения файловых полей, импортированных как массив id
 * (например `[1000241,1000242]`), в полноценные объекты, которые ждёт фронт:
 *   [{"id":..,"name":..,"url":<превью>,"file":<полный URL>,"extension":..,"sort":..,"ext":..}]
 *
 * Логика 1-в-1 как в App\Services\FileService::upload (включая \Thumbnail для
 * картинок и svg-иконки для остальных типов), поэтому превью генерируются тем же
 * пакетом rolandstarke/laravel-thumbnail — в чистом SQL это невоспроизводимо.
 *
 * Запускать ПОСЛЕ импорта данных и (желательно) после скачивания файлов:
 *   php artisan import:rebuild-file-fields logistopt6
 *
 * Идемпотентно: значения, которые уже являются объектами, пропускаются.
 * По умолчанию трогает только импортированные записи (id >= --min-id).
 */
class RebuildImportedFileFields extends Command
{
    protected $signature = 'import:rebuild-file-fields
        {tenant : id портала (например logistopt6) или имя БД admin_logistopt6}
        {--entities=cars,employees : сущности через запятую}
        {--min-id=1000000 : обрабатывать только записи с id >= этого значения (импортированные)}';

    protected $description = 'Пересобрать файловые поля (массив id -> объекты файлов с превью) после импорта';

    public function handle(): int
    {
        $target = $this->argument('tenant');
        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден (id без префикса '".config('tenancy.database.prefix')."', напр. logistopt6)");
            return self::FAILURE;
        }

        $entities = array_filter(array_map('trim', explode(',', $this->option('entities'))));
        $minId = (int) $this->option('min-id');

        $tenant->run(function () use ($entities, $minId) {
            $tid = tenant('id');
            foreach ($entities as $slug) {
                $dt = \DB::table('data_types')->where('slug', $slug)->first();
                if (!$dt) {
                    $this->warn("  пропуск: сущность {$slug} не найдена");
                    continue;
                }
                $fileFields = \DB::table('data_rows')
                    ->where('data_type_id', $dt->id)->where('type', 'file')
                    ->pluck('field')->toArray();
                if (!$fileFields) {
                    $this->line("  {$slug}: файловых полей нет");
                    continue;
                }
                $model = $dt->model_name;
                $touched = 0;
                // часть импортированных записей помечена deleted_at (из источника) —
                // обрабатываем и их (withTrashed), иначе их файловые поля останутся «сырыми».
                $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))
                    ? $model::withTrashed()
                    : $model::query();
                $query->where('id', '>=', $minId)->chunkById(200, function ($rows) use ($fileFields, $tid, &$touched) {
                    foreach ($rows as $obj) {
                        $changed = false;
                        foreach ($fileFields as $field) {
                            $built = $this->buildObjects($obj->{$field}, $tid);
                            if ($built !== null) {
                                $obj->{$field} = json_encode($built, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                $changed = true;
                            }
                        }
                        if ($changed) {
                            $obj->saveQuietly();
                            $touched++;
                        }
                    }
                });
                $this->info("  {$slug}: обновлено записей — {$touched} (поля: ".implode(', ', $fileFields).")");
            }
        });

        $this->info('Готово.');
        return self::SUCCESS;
    }

    /**
     * Возвращает массив объектов файлов, если значение — массив id; иначе null
     * (пусто / уже объекты / неразбираемо) — такие значения не трогаем.
     */
    private function buildObjects($value, $tid): ?array
    {
        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }
        $data = is_array($value) ? $value : json_decode($value, true);
        if (!is_array($data) || count($data) === 0) {
            return null;
        }
        // уже объекты -> пропускаем (идемпотентность)
        if (is_array($data[0] ?? null) || is_object($data[0] ?? null)) {
            return null;
        }
        $ids = [];
        foreach ($data as $v) {
            if (is_numeric($v)) {
                $ids[] = (int) $v;
            }
        }
        if (!$ids) {
            return null;
        }

        $files = \DB::table('files')->whereIn('id', $ids)->get()->keyBy('id');
        $out = [];
        $sort = 0;
        foreach ($ids as $id) {
            $f = $files->get($id);
            if (!$f || !$f->path) {
                continue; // файла нет в files — пропускаем
            }
            $out[] = $this->fileObject($f, $sort++, $tid);
        }
        return $out ?: null;
    }

    private function fileObject($f, int $sort, $tid): array
    {
        $path = $f->path;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: pathinfo((string) $f->name, PATHINFO_EXTENSION));

        $fileUrl = $tid
            ? 'https://'.$tid.'.compas.pro/storage/tenant'.$tid.'/app/public/'.$path
            : 'https://compas.pro/storage/app/public/'.$path;

        if (in_array($ext, ['xls', 'xlsx'])) {
            $url = '/files/xls.svg';
        } elseif (in_array($ext, ['doc', 'docx'])) {
            $url = '/files/word.svg';
        } elseif ($ext === 'pdf') {
            $url = '/files/pdfSmall.svg';
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            try {
                $url = \Thumbnail::src($fileUrl)->heighten(200)->url();
            } catch (\Throwable $e) {
                $url = $fileUrl;
            }
        } elseif ($ext === 'svg') {
            $url = $fileUrl;
        } else {
            $url = '/files/undefinedFile.svg';
        }

        return [
            'id'        => (int) $f->id,
            'name'      => $f->name,
            'url'       => $url,
            'file'      => $fileUrl,
            'extension' => $ext,
            'sort'      => $sort,
            'ext'       => $ext,
        ];
    }
}
