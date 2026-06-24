<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['car_requirements', 'employee_requirements', 'car_data', 'driver_data'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeIds = DB::table('data_types')->where('slug', 'routes')->pluck('id');
        if ($typeIds->isEmpty()) {
            return;
        }

        DB::table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->whereIn('field', $this->fields)
            ->update(['hide' => 1]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeIds = DB::table('data_types')->where('slug', 'routes')->pluck('id');

        DB::table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->whereIn('field', $this->fields)
            ->update(['hide' => 0]);
    }
};
