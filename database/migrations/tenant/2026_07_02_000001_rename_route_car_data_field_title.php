<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Переименование поля сущности «Маршрут» (routes): «Данные машины» -> «Примечание».
 * Меняем заголовок в data_rows. Применяется к порталам (tenants:migrate) и к
 * БД-шаблону seeds (seeds:migrate), чтобы новые порталы создавались уже с новым
 * названием (8590).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Данные машины', 'Примечание');
    }

    public function down(): void
    {
        $this->rename('Примечание', 'Данные машины');
    }

    private function rename(string $from, string $to): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')
            ->where('slug', 'routes')
            ->orWhere('name', 'routes')
            ->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('title', $from)
            ->update(['title' => $to]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
