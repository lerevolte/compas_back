<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Удаляет дубли пунктов сайдбара по slug (например две строки companies) —
 * оставляем строку с наименьшим id. Дубли задваивали пункт в меню и в списке
 * «Сущности», а близнец с другим id ломал сохранение порядка/скрытия сайдбара
 * (8591, 8588). Применяется к порталам (tenants:migrate) и к БД-шаблону seeds
 * (seeds:migrate), чтобы новые порталы создавались уже без дублей.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sidebar_items')) {
            return;
        }

        $rows = DB::table('sidebar_items')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->get(['id', 'slug']);

        $seen = [];
        $dupIds = [];
        foreach ($rows as $row) {
            if (isset($seen[$row->slug])) {
                $dupIds[] = $row->id;
            } else {
                $seen[$row->slug] = $row->id;
            }
        }

        if (!empty($dupIds)) {
            DB::table('sidebar_items')->whereIn('id', $dupIds)->delete();
            if (Schema::hasTable('sidebar_items') && class_exists(\App\Models\SidebarItem::class)) {
                try { \App\Models\SidebarItem::fixTree(); } catch (\Throwable $e) {}
            }
            if (class_exists(\App\Models\Settings::class)) {
                try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
            }
        }
    }

    public function down(): void
    {
        // Восстановление дублей не требуется.
    }
};
