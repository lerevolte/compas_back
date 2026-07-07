<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        // Удаляем определение поля «Клиент» (client_id) из ВСЕХ сущностей на
        // портале (8670). Это старое поле, которое нигде не выводится, но из-за
        // наличия data_row попадало в фильтр «Библиотеки задач» и в карточку.
        // Физическую колонку client_id и pivot logistic_task_client НЕ трогаем:
        // RouteController копирует client при создании задачи из адреса/склада
        // именно в колонку, а не через это определение поля.
        DB::table('data_rows')->where('field', 'client_id')->delete();

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
