<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PrintAttachmentService
{
    public const TABLE = 'print_attachments';
    public const SLUGS = ['logistic_tasks', 'pickups'];
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    public const MAX_COPIES = 20;
    public const DIR = 'print_attachments';

    public static function ensureTable(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `print_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) NOT NULL,
  `object_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  `ext` varchar(16) NOT NULL,
  `copies` int(11) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `print_attachments_object` (`slug`, `object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public static function ready(): bool
    {
        try {
            return Schema::hasTable(self::TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function copies($value, int $default = 1): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }

        return max(1, min(self::MAX_COPIES, (int) $value));
    }

    public static function forObject(string $slug, int $id): array
    {
        if (!self::ready()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('slug', $slug)
            ->where('object_id', $id)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => self::present($row))
            ->all();
    }

    public static function present($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'ext' => (string) $row->ext,
            'copies' => (int) $row->copies,
            'url' => self::url((string) $row->path),
            'created_at' => $row->created_at ? date('d.m.Y H:i', strtotime($row->created_at)) : null,
        ];
    }

    public static function url(string $path): string
    {
        $tenant = tenant('id');

        return $tenant
            ? 'https://' . $tenant . '.compas.pro/storage/tenant' . $tenant . '/app/public/' . $path
            : 'https://compas.pro/storage/app/public/' . $path;
    }

    public static function store(string $slug, int $id, UploadedFile $file, int $copies): array
    {
        self::ensureTable();
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->store(self::DIR, 'public');
        $now = now();
        $rowId = DB::table(self::TABLE)->insertGetId([
            'slug' => $slug,
            'object_id' => $id,
            'name' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
            'path' => $path,
            'ext' => $ext,
            'copies' => self::copies($copies),
            'user_id' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return self::present(DB::table(self::TABLE)->where('id', $rowId)->first());
    }

    public static function find(string $slug, int $id, int $attachmentId)
    {
        if (!self::ready()) {
            return null;
        }

        return DB::table(self::TABLE)
            ->where('id', $attachmentId)
            ->where('slug', $slug)
            ->where('object_id', $id)
            ->first();
    }

    public static function updateCopies($row, int $copies): array
    {
        DB::table(self::TABLE)->where('id', $row->id)->update(['copies' => self::copies($copies), 'updated_at' => now()]);

        return self::present(DB::table(self::TABLE)->where('id', $row->id)->first());
    }

    public static function delete($row): void
    {
        DB::table(self::TABLE)->where('id', $row->id)->delete();
        try {
            Storage::disk('public')->delete((string) $row->path);
        } catch (\Throwable $e) {
        }
    }

    public static function rows(string $slug, array $ids): array
    {
        if (!self::ready() || !count($ids)) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('slug', $slug)
            ->whereIn('object_id', $ids)
            ->orderBy('id')
            ->get()
            ->all();
    }

    public static function pdfPath($row, callable $writeTemp, array &$temp): ?string
    {
        $path = Storage::disk('public')->path((string) $row->path);
        if (!is_file($path)) {
            return null;
        }
        if ($row->ext === 'pdf') {
            return $path;
        }
        try {
            $data = base64_encode((string) file_get_contents($path));
            $mime = $row->ext === 'png' ? 'image/png' : 'image/jpeg';
            $html = '<html><head><style>@page{margin:20px}body{margin:0;text-align:center}img{max-width:100%;max-height:1080px}</style></head><body><img src="data:' . $mime . ';base64,' . $data . '"></body></html>';
            $bytes = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
            $out = $writeTemp($bytes);
            if ($out !== null) {
                $temp[] = $out;
            }

            return $out;
        } catch (\Throwable $e) {
            \Log::warning('print attachment: не удалось сконвертировать изображение', ['id' => $row->id, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
