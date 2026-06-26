<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('sidebar_items')) {
            return;
        }

        $db = DB::connection('seeds');
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
            return;
        }

        $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
        $db->table('sidebar_items')->insert([
            'created_at' => $now, 'updated_at' => $now,
            'name' => 'Статистика товаров', 'slug' => 'product-stats',
            'sort' => 0, 'link' => '/product-stats',
            '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
            'is_hidden' => 0, 'enabled' => 1,
        ]);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('sidebar_items')) {
            return;
        }

        DB::connection('seeds')->table('sidebar_items')
            ->where('slug', 'product-stats')
            ->orWhere('link', '/product-stats')
            ->delete();
    }
};
