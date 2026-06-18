<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'loading_time')
            ->update(['title' => 'Начало маршрута']);

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'employee_requirements')
            ->update(['title' => 'Требования к сотруднику']);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'loading_time')
            ->update(['title' => 'Время загрузки']);

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'employee_requirements')
            ->update(['title' => 'Требования к водителю']);
    }
};
