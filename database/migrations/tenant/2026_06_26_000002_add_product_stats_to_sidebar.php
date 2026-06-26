<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->add(DB::connection());
    }

    public function down(): void
    {
        $this->remove(DB::connection());
    }

    private function add(ConnectionInterface $db): void
    {
        if (!$db->getSchemaBuilder()->hasTable('sidebar_items')) {
            return;
        }

        $now = now();
        $existing = $db->table('sidebar_items')
            ->where('slug', 'product-stats')
            ->orWhere('link', '/product-stats')
            ->first();

        if ($existing) {
            $db->table('sidebar_items')->where('id', $existing->id)->update([
                'name' => 'Статистика товаров',
                'slug' => 'product-stats',
                'link' => '/product-stats',
                'enabled' => 1,
                'is_hidden' => 0,
                'updated_at' => $now,
            ]);
        } else {
            $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
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

    private function remove(ConnectionInterface $db): void
    {
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
};
