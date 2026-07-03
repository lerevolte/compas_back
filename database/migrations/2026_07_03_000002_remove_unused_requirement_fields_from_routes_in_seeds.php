<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['car_requirements', 'employee_requirements', 'driver_data'];

    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $db = DB::connection('seeds');
        $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->whereIn('field', $this->fields)
            ->delete();
    }

    public function down(): void
    {
    }
};
