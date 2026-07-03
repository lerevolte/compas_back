<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['car_requirements', 'employee_requirements', 'driver_data'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }
        $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->whereIn('field', $this->fields)
            ->delete();

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
