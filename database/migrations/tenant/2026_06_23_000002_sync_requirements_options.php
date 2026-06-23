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
        $this->sync(DB::connection());
    }

    public function down(): void
    {
    }

    private function sync($db): void
    {
        $carsId = $db->table('data_types')->where('slug', 'cars')->value('id');
        $empId = $db->table('data_types')->where('slug', 'employees')->value('id');

        if ($carsId) {
            $carDetails = $db->table('data_rows')
                ->where('data_type_id', $carsId)
                ->where('field', 'requirements')
                ->whereNotNull('details')
                ->value('details');
            if ($carDetails) {
                $db->table('data_rows')->where('field', 'car_requirements')->update(['details' => $carDetails]);
            }
        }

        if ($empId) {
            $empDetails = $db->table('data_rows')
                ->where('data_type_id', $empId)
                ->whereIn('field', ['requirements', 'employee_requirements'])
                ->whereNotNull('details')
                ->orderByRaw("field = 'requirements' DESC")
                ->value('details');
            if ($empDetails) {
                $db->table('data_rows')
                    ->whereIn('field', ['employee_requirements', 'driver_requirements'])
                    ->update(['details' => $empDetails]);
            }
        }
    }
};
