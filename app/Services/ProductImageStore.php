<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageStore
{
    public static function fromUrl(string $url, ?string $name = null, array $extra = []): ?array
    {
        $binary = Http::timeout(30)->get($url);
        $contentType = strtolower(trim(explode(';', (string) $binary->header('Content-Type'))[0]));
        if (!$binary->ok() || !str_starts_with($contentType, 'image/')) {
            return null;
        }
        $ext = explode('/', $contentType)[1] ?? 'jpg';
        $ext = in_array($ext, ['jpeg', 'png', 'gif', 'webp', 'jpg'], true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'jpg';
        $filename = trim((string) $name) ?: (basename(parse_url($url, PHP_URL_PATH) ?: '') ?: ('product.' . $ext));
        $path = 'files/' . Str::random(40) . '.' . $ext;
        Storage::disk('public')->put($path, $binary->body());

        $document = new \App\Models\File();
        $document->name = $filename;
        $document->path = $path;
        $document->save();

        $tenant = tenant('id');
        $publicPath = $tenant
            ? 'https://' . $tenant . '.compas.pro/storage/tenant' . $tenant . '/app/public/' . $path
            : 'https://compas.pro/storage/app/public/' . $path;
        try {
            $document->addMediaFromUrl($publicPath)->toMediaCollection();
        } catch (\Throwable $e) {
        }
        try {
            $thumbnail = \Thumbnail::src($publicPath)->heighten(200)->url();
        } catch (\Throwable $e) {
            $thumbnail = $publicPath;
        }

        return [
            'id' => $document->id,
            'name' => $filename,
            'url' => $thumbnail,
            'file' => $publicPath,
            'extension' => $ext,
            'sort' => 0,
            'ext' => $ext,
        ] + $extra;
    }
}
