<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sidebar_items')) {
            return;
        }

        $exists = DB::table('sidebar_items')
            ->where(function ($q) {
                $q->where('slug', 'roles')->orWhere('link', '/roles');
            })
            ->exists();
        if ($exists) {
            return;
        }

        $sort = (int) DB::table('sidebar_items')->max('sort');
        $maxRgt = (int) DB::table('sidebar_items')->max('_rgt');

        DB::table('sidebar_items')->insert([
            'name' => 'Настройка ролей сущности',
            'slug' => 'roles',
            'link' => '/roles',
            'sort' => $sort + 1,
            'parent_id' => null,
            '_lft' => $maxRgt + 1,
            '_rgt' => $maxRgt + 2,
            'is_hidden' => 0,
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sidebar_items')) {
            return;
        }

        DB::table('sidebar_items')
            ->where('slug', 'roles')
            ->where('link', '/roles')
            ->delete();
    }
};
