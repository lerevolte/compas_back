<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (8476/8477) Маски ввода в столбце data_rows.mask. Сами проверки диапазонов
 * (часы 00–23, минуты 00–59, формат e-mail) реализованы на фронте в
 * компоненте Input (структура — maska, нормализация/валидация — на blur).
 *
 *  - logistic_tasks.time («Окно»)   → '##:## - ##:##'  (например «08:00 - 21:00»)
 *  - loading_time («Начало маршрута») → '##:##'        (например «08:00»)
 *  - dt=1 phone / work_phone        → '+#(###)###-##-##'
 *  - dt=1 email                     → 'email' (служебный маркер e-mail режима)
 *
 * routes.time здесь НЕ трогаем — оно сделано числовым в 000001.
 * Парная миграция для шаблона новых порталов: ..._in_seeds.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        DB::table('data_rows')->where('data_type_id', 3)
            ->where('field', 'time')->update(['mask' => '##:## - ##:##']);

        DB::table('data_rows')->where('field', 'loading_time')->update(['mask' => '##:##']);

        DB::table('data_rows')->where('data_type_id', 1)
            ->whereIn('field', ['phone', 'work_phone'])->update(['mask' => '+#(###)###-##-##']);

        DB::table('data_rows')->where('data_type_id', 1)
            ->where('field', 'email')->update(['mask' => 'email']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        DB::table('data_rows')->where('data_type_id', 3)->where('field', 'time')->update(['mask' => null]);
        DB::table('data_rows')->where('field', 'loading_time')->update(['mask' => null]);
        DB::table('data_rows')->where('data_type_id', 1)
            ->whereIn('field', ['phone', 'work_phone', 'email'])->update(['mask' => null]);
    }
};
