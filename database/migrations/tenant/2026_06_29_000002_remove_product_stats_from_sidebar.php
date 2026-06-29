<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * «Статистика товаров по дням» переехала в раздел «Аналитика» (открывается
 * блоком по клику), поэтому отдельный пункт сайдбара /product-stats больше не
 * нужен — убираем его (8557). Сама страница /product-stats остаётся доступной
 * по прямой ссылке/из модалки аналитики.
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        if ($db->getSchemaBuilder()->hasTable('sidebar_items')) {
            $db->table('sidebar_items')
                ->where('slug', 'product-stats')
                ->orWhere('link', '/product-stats')
                ->delete();
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $db = DB::connection();
        if (!$db->getSchemaBuilder()->hasTable('sidebar_items')) {
            return;
        }

        $existing = $db->table('sidebar_items')
            ->where('slug', 'product-stats')
            ->orWhere('link', '/product-stats')
            ->first();

        if (!$existing) {
            $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
            $now = now();
            $db->table('sidebar_items')->insert([
                'created_at' => $now, 'updated_at' => $now,
                'name' => 'Статистика товаров', 'slug' => 'product-stats',
                'sort' => 0, 'link' => '/product-stats',
                '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                'is_hidden' => 0, 'enabled' => 1,
            ]);
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
