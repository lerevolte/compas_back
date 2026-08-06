<?php

namespace Modules\Bitrix24\Services;

use App\Models\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Product;
use Modules\Bitrix24\Entities\Config;

class B24ProductSync
{
    public static bool $muted = false;

    private string $base;
    private array $params;
    private ?bool $catalogScope = null;

    public const PUSH_PRODUCT_FIELDS = ['name', 'price', 'weight', 'link', 'category_id'];
    public const PUSH_CATEGORY_FIELDS = ['name', 'parent_id'];

    private const LINK_PROPERTY = 'PROPERTY_132';
    private const WEIGHT_PROPERTY = 'PROPERTY_134';

    public static function make(): ?self
    {
        if (!self::ready()) {
            return null;
        }
        $svc = new self();
        $config = Config::first();
        $svc->base = $config->webhook;
        $svc->params = $config->getParams() ?: [];
        return $svc;
    }

    public static function ready(): bool
    {
        try {
            if (!Schema::hasTable('products') || !Schema::hasTable('categories')) {
                return false;
            }
            if (!Schema::hasColumn('products', 'link')
                || !Schema::hasColumn('products', 'id_b24')
                || !Schema::hasColumn('categories', 'id_b24')) {
                return false;
            }
            $config = Config::first();
            return $config && $config->webhook;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function b24(string $method, array $params = [])
    {
        return Http::timeout(20)->post($this->base . $method, $params)->collect();
    }

    private function b24All(string $method, array $params = [], int $max = 0): array
    {
        $result = [];
        $start = 0;
        $guard = 0;
        do {
            $resp = $this->b24($method, $params + ['start' => $start]);
            $batch = $resp['result'] ?? [];
            if (!is_array($batch)) {
                break;
            }
            $result = array_merge($result, $batch);
            $start = $resp['next'] ?? null;
            $guard++;
            if ($max > 0 && count($result) >= $max) {
                break;
            }
        } while ($start !== null && count($batch) && $guard < 200);

        return $result;
    }

    private function catalogId(): int
    {
        if (!empty($this->params['catalog_id'])) {
            return (int) $this->params['catalog_id'];
        }
        $stored = DB::table('settings')->where('type', 'b24_catalog_id')->value('value');
        if ($stored) {
            return (int) $stored;
        }
        $catalogId = 0;
        try {
            $resp = $this->b24('crm.product.list', [
                'order'  => ['ID' => 'ASC'],
                'select' => ['ID', 'CATALOG_ID'],
            ]);
            $catalogId = (int) ($resp['result'][0]['CATALOG_ID'] ?? 0);
        } catch (\Throwable $e) {
        }
        if ($catalogId) {
            DB::table('settings')->updateOrInsert(
                ['type' => 'b24_catalog_id', 'entity' => null, 'user_id' => null],
                ['key' => 'b24_catalog_id', 'value' => (string) $catalogId]
            );
        }
        return $catalogId;
    }

    public function runIncremental(int $chunk = 200): array
    {
        $read = fn (string $type) => DB::table('settings')->where('type', $type)->value('value');
        $write = function (string $type, string $value) {
            DB::table('settings')->updateOrInsert(
                ['type' => $type, 'entity' => null, 'user_id' => null],
                ['key' => $type, 'value' => $value]
            );
        };

        $categories = $this->pullCategories();

        $since = $read('b24_products_synced_at');
        $started = now()->format('Y-m-d\TH:i:sP');

        $products = $this->pullProducts($since, $since ? $chunk : 0);
        $write('b24_products_synced_at', ($products['more'] && $products['last_modify']) ? $products['last_modify'] : $started);

        return [
            'categories' => $categories,
            'products'   => $products['count'],
            'more'       => $products['more'],
        ];
    }

    public function pullCategories(): int
    {
        $catalogId = $this->catalogId();
        $sections = $this->b24All('crm.productsection.list', [
            'filter' => $catalogId ? ['CATALOG_ID' => $catalogId] : [],
            'select' => ['ID', 'NAME', 'SECTION_ID', 'CATALOG_ID'],
            'order'  => ['ID' => 'ASC'],
        ]);
        if (!count($sections)) {
            return 0;
        }

        self::$muted = true;
        try {
            $count = 0;
            $localByB24 = [];
            foreach ($sections as $section) {
                $b24Id = (string) $section['ID'];
                $model = Category::withTrashed()->where('id_b24', $b24Id)->first();
                if (!$model) {
                    $model = Category::where('name', trim((string) ($section['NAME'] ?? '')))
                        ->whereNull('id_b24')->first() ?: new Category();
                }
                if ($model->exists && $model->trashed()) {
                    $model->deleted_at = null;
                }
                $model->id_b24 = $b24Id;
                $model->name = trim((string) ($section['NAME'] ?? '')) ?: ('Раздел #' . $b24Id);
                $model->save();
                $localByB24[$b24Id] = $model;
                $count++;
            }

            foreach ($sections as $section) {
                $b24Id = (string) $section['ID'];
                $model = $localByB24[$b24Id] ?? null;
                if (!$model) {
                    continue;
                }
                $parentB24 = $section['SECTION_ID'] ?? null;
                $parentId = ($parentB24 && isset($localByB24[(string) $parentB24]))
                    ? $localByB24[(string) $parentB24]->id
                    : null;
                if ((int) $model->parent_id !== (int) $parentId && $model->id !== $parentId) {
                    $model->parent_id = $parentId;
                    $model->save();
                }
            }

            Category::whereNotNull('id_b24')
                ->whereNotIn('id_b24', array_keys($localByB24))
                ->get()
                ->each(fn ($cat) => $cat->delete());

            return $count;
        } finally {
            self::$muted = false;
        }
    }

    public function pullProducts(?string $since = null, int $limit = 0): array
    {
        $catalogId = $this->catalogId();
        $filter = $catalogId ? ['CATALOG_ID' => $catalogId] : [];
        if ($since) {
            $filter['>TIMESTAMP_X'] = $since;
        }
        $rows = $this->b24All('crm.product.list', [
            'filter' => $filter,
            'select' => [
                'ID', 'NAME', 'PRICE', 'SECTION_ID', 'CATALOG_ID', 'TIMESTAMP_X',
                'PREVIEW_PICTURE', 'DETAIL_PICTURE',
                self::LINK_PROPERTY, self::WEIGHT_PROPERTY,
            ],
            'order'  => $since ? ['TIMESTAMP_X' => 'ASC'] : ['ID' => 'ASC'],
        ], $limit);
        $more = $limit > 0 && count($rows) >= $limit;
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $count = 0;
        $lastModify = null;
        foreach ($rows as $row) {
            try {
                $this->upsertProductFromB24($row);
                $count++;
                $lastModify = $row['TIMESTAMP_X'] ?? $lastModify;
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('product-sync: upsert failed', [
                    'product_id' => $row['ID'] ?? null,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
        return ['count' => $count, 'last_modify' => $lastModify, 'more' => $more];
    }

    public function pullProductById($productId): ?Product
    {
        $resp = $this->b24('crm.product.get', ['id' => $productId]);
        $row = $resp['result'] ?? null;
        return $row ? $this->upsertProductFromB24($row) : null;
    }

    public function deleteByB24Id($productId): void
    {
        self::$muted = true;
        try {
            Product::where('id_b24', (string) $productId)->first()?->delete();
        } finally {
            self::$muted = false;
        }
    }

    private function propertyValue($raw): ?string
    {
        if (is_array($raw)) {
            $raw = $raw['value'] ?? null;
        }
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }
        return (string) $raw;
    }

    public function upsertProductFromB24(array $row): Product
    {
        $b24Id = (string) $row['ID'];

        self::$muted = true;
        try {
            $model = Product::withTrashed()->where('id_b24', $b24Id)->first();
            $name = trim((string) ($row['NAME'] ?? ''));
            if (!$model && $name !== '') {
                $model = Product::where('name', $name)->whereNull('id_b24')->first();
            }
            if (!$model) {
                $model = new Product();
            }
            $isNew = !$model->exists;
            if (!$isNew && $model->trashed()) {
                $model->deleted_at = null;
            }

            $model->id_b24 = $b24Id;
            $model->name = $name !== '' ? $name : ('Товар #' . $b24Id);
            if (array_key_exists('PRICE', $row)) {
                $model->price = (float) $row['PRICE'];
            }
            if (array_key_exists(self::WEIGHT_PROPERTY, $row)) {
                $model->weight = (float) $this->propertyValue($row[self::WEIGHT_PROPERTY]);
            }
            if (array_key_exists(self::LINK_PROPERTY, $row)) {
                $linkUrl = $this->propertyValue($row[self::LINK_PROPERTY]);
                $model->link = $linkUrl
                    ? json_encode(['value' => 'Перейти на сайт', 'external_link' => $linkUrl], JSON_UNESCAPED_UNICODE)
                    : null;
            }
            if (array_key_exists('SECTION_ID', $row)) {
                $model->category_id = $row['SECTION_ID']
                    ? Category::where('id_b24', (string) $row['SECTION_ID'])->value('id')
                    : null;
            }

            $this->applyProductPicture($model, $row);

            if ($isNew) {
                $model->save();
                $this->writeSyncCreatedHistory($model->id);
            } elseif (count($model->getDirty())) {
                $this->writeSyncFieldHistory($model->id, $model->getDirty());
                $model->save();
            }

            return $model;
        } finally {
            self::$muted = false;
        }
    }

    private function applyProductPicture(Product $model, array $row): void
    {
        $picture = $row['DETAIL_PICTURE'] ?? $row['PREVIEW_PICTURE'] ?? null;
        $pictureId = is_array($picture) ? ($picture['id'] ?? null) : $picture;

        $current = json_decode((string) $model->photo, true);
        $currentB24FileId = is_array($current) ? ($current[0]['b24_file_id'] ?? null) : null;

        if (!$pictureId) {
            if ($currentB24FileId) {
                $model->photo = '[]';
            }
            return;
        }
        if ((string) $currentB24FileId === (string) $pictureId) {
            return;
        }

        $item = $this->downloadProductImage($row['ID'], $pictureId);
        if ($item) {
            $model->photo = json_encode([$item], JSON_UNESCAPED_UNICODE);
        }
    }

    private function hasCatalogScope(): bool
    {
        if ($this->catalogScope !== null) {
            return $this->catalogScope;
        }
        try {
            $resp = $this->b24('scope', []);
            $scopes = $resp['result'] ?? [];
            $this->catalogScope = is_array($scopes) && in_array('catalog', $scopes, true);
        } catch (\Throwable $e) {
            $this->catalogScope = false;
        }
        if (!$this->catalogScope) {
            Log::channel('bitrix24')->info('product-sync: no catalog scope, photos skipped');
        }
        return $this->catalogScope;
    }

    private function downloadProductImage($productB24Id, $pictureId): ?array
    {
        if (!$this->hasCatalogScope()) {
            return null;
        }
        try {
            $resp = $this->b24('catalog.productImage.list', ['productId' => (int) $productB24Id]);
            $images = $resp['result']['productImages'] ?? [];
            $image = null;
            foreach ($images as $img) {
                if ((string) ($img['fileId'] ?? $img['id'] ?? '') === (string) $pictureId) {
                    $image = $img;
                    break;
                }
            }
            $image = $image ?: ($images[0] ?? null);
            if (!$image) {
                return null;
            }
            $url = $image['detailUrl'] ?? $image['downloadUrl'] ?? null;
            if (!$url) {
                return null;
            }
            if (!preg_match('~^https?://~i', $url)) {
                $url = rtrim($this->portalRoot(), '/') . '/' . ltrim($url, '/');
            }
            $binary = Http::timeout(30)->get($url);
            $contentType = (string) $binary->header('Content-Type');
            if (!$binary->ok() || !str_starts_with($contentType, 'image/')) {
                return null;
            }

            $ext = explode('/', $contentType)[1] ?? 'jpg';
            $ext = in_array($ext, ['jpeg', 'png', 'gif', 'webp', 'jpg']) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'jpg';
            $filename = trim((string) ($image['name'] ?? '')) ?: ('b24_product_' . $productB24Id . '.' . $ext);
            $path = 'files/' . \Illuminate\Support\Str::random(40) . '.' . $ext;
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
                'id'          => $document->id,
                'name'        => $filename,
                'url'         => $thumbnail,
                'file'        => $publicPath,
                'extension'   => $ext,
                'sort'        => 0,
                'ext'         => $ext,
                'b24_file_id' => (string) $pictureId,
            ];
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('product-sync: image download failed', [
                'product_b24_id' => $productB24Id,
                'error'          => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function portalRoot(): string
    {
        return parse_url($this->base, PHP_URL_SCHEME) . '://' . parse_url($this->base, PHP_URL_HOST);
    }

    public function pushProduct(Product $product, array $changed = []): void
    {
        $fields = [];
        $all = !count($changed) || !$product->id_b24;

        if ($all || in_array('name', $changed, true)) {
            $fields['NAME'] = (string) $product->name;
        }
        if ($all || in_array('price', $changed, true)) {
            $fields['PRICE'] = (float) $product->price;
            $fields['CURRENCY_ID'] = 'RUB';
        }
        if ($all || in_array('weight', $changed, true)) {
            $fields[self::WEIGHT_PROPERTY] = $product->weight !== null ? (string) $product->weight : '';
        }
        if ($all || in_array('link', $changed, true)) {
            $rawLink = (string) $product->link;
            $decodedLink = json_decode($rawLink, true);
            $fields[self::LINK_PROPERTY] = is_array($decodedLink)
                ? (string) ($decodedLink['external_link'] ?? '')
                : $rawLink;
        }
        if ($all || in_array('category_id', $changed, true)) {
            $sectionId = 0;
            if ($product->category_id) {
                $category = Category::find($product->category_id);
                if ($category) {
                    if (!$category->id_b24) {
                        $this->pushCategory($category);
                    }
                    $sectionId = (int) ($category->id_b24 ?: 0);
                }
            }
            $fields['SECTION_ID'] = $sectionId;
        }
        if (!count($fields)) {
            return;
        }

        if ($product->id_b24) {
            $resp = $this->b24('crm.product.update', ['id' => $product->id_b24, 'fields' => $fields]);
        } else {
            $fields['CATALOG_ID'] = $this->catalogId();
            $resp = $this->b24('crm.product.add', ['fields' => $fields]);
            $newId = $resp['result'] ?? null;
            if ($newId) {
                self::$muted = true;
                try {
                    $product->id_b24 = (string) $newId;
                    $product->saveQuietly();
                } finally {
                    self::$muted = false;
                }
            }
        }
        Log::channel('bitrix24')->info('product-sync: product pushed', [
            'product_id' => $product->id, 'b24_id' => $product->id_b24,
            'fields' => array_keys($fields), 'result' => $resp['result'] ?? null,
        ]);
    }

    public function pushCategory(Category $category, array $changed = []): void
    {
        $parentB24 = 0;
        if ($category->parent_id) {
            $parent = Category::find($category->parent_id);
            if ($parent) {
                if (!$parent->id_b24 && $parent->id !== $category->id) {
                    $this->pushCategory($parent);
                }
                $parentB24 = (int) ($parent->id_b24 ?: 0);
            }
        }

        $fields = [
            'NAME'       => (string) $category->name,
            'SECTION_ID' => $parentB24,
        ];

        if ($category->id_b24) {
            $resp = $this->b24('crm.productsection.update', ['id' => $category->id_b24, 'fields' => $fields]);
        } else {
            $fields['CATALOG_ID'] = $this->catalogId();
            $resp = $this->b24('crm.productsection.add', ['fields' => $fields]);
            $newId = $resp['result'] ?? null;
            if ($newId) {
                self::$muted = true;
                try {
                    $category->id_b24 = (string) $newId;
                    $category->saveQuietly();
                } finally {
                    self::$muted = false;
                }
            }
        }
        Log::channel('bitrix24')->info('product-sync: category pushed', [
            'category_id' => $category->id, 'b24_id' => $category->id_b24,
            'result' => $resp['result'] ?? null,
        ]);
    }

    private function writeSyncCreatedHistory($id): void
    {
        try {
            $history = new History([
                'entity'    => 'products',
                'entity_id' => $id,
                'user_id'   => null,
                'event'     => 'OBJECT_CREATED',
                'text'      => 'Создана запись: ' . $id . ' (синхронизировано из Bitrix24)',
            ]);
            $history->saveQuietly();
        } catch (\Throwable $e) {
        }
    }

    private function writeSyncFieldHistory($id, array $changed): void
    {
        unset(
            $changed['updated_at'], $changed['created_at'], $changed['id_b24'],
            $changed['deleted_at'], $changed['photo']
        );
        if (!count($changed)) {
            return;
        }
        try {
            History::saveForObject('products', [array_merge(['id' => $id], $changed)]);
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('product-sync: history write failed', [
                'id' => $id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
