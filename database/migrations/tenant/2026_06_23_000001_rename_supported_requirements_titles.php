<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $carsId = DB::table('data_types')->where('slug', 'cars')->value('id');
        $empId = DB::table('data_types')->where('slug', 'employees')->value('id');

        if ($carsId) {
            DB::table('data_rows')
                ->where('data_type_id', $carsId)
                ->where('title', 'Требования к машине')
                ->update(['title' => 'Поддерживаемые требования к машине']);
        }

        if ($empId) {
            DB::table('data_rows')
                ->where('data_type_id', $empId)
                ->where('title', 'Требования к сотруднику')
                ->update(['title' => 'Поддерживаемые требования к сотруднику']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $carsId = DB::table('data_types')->where('slug', 'cars')->value('id');
        $empId = DB::table('data_types')->where('slug', 'employees')->value('id');

        if ($carsId) {
            DB::table('data_rows')
                ->where('data_type_id', $carsId)
                ->where('title', 'Поддерживаемые требования к машине')
                ->update(['title' => 'Требования к машине']);
        }

        if ($empId) {
            DB::table('data_rows')
                ->where('data_type_id', $empId)
                ->where('title', 'Поддерживаемые требования к сотруднику')
                ->update(['title' => 'Требования к сотруднику']);
        }
    }
};
