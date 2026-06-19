<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Поле «Время обслуживания» (service_time) во всех порталах делаем числовым
 * (data_rows.type = number). Раньше оно было text, из-за чего ввод/валидация
 * шли как у текста. Колонка в таблицах (logistic_tasks/addresses) уже int.
 *
 * Шаблон новых порталов (admin_seeds) правит парная миграция
 * database/migrations/..._set_service_time_field_type_number_in_seeds.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        DB::table('data_rows')
            ->where('field', 'service_time')
            ->update(['type' => 'number']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        DB::table('data_rows')
            ->where('field', 'service_time')
            ->update(['type' => 'text']);
    }
};
