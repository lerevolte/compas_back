<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // logistic_tasks
        if (Schema::hasTable('logistic_tasks')) {
            $cols = ['car_requirements', 'employee_requirements', 'weight', 'volume', 'plan_time', 'delivery_date', 'service_time'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('logistic_tasks', $col)) {
                    Schema::table('logistic_tasks', function (Blueprint $table) use ($col) {
                        $table->text($col)->nullable();
                    });
                }
            }
        }

        // cars
        if (Schema::hasTable('cars')) {
            $cols = ['requirements', 'weight_min', 'weight_max', 'volume_min', 'volume_max', 'price_per_km', 'car_data', 'route_id'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('cars', $col)) {
                    Schema::table('cars', function (Blueprint $table) use ($col) {
                        $table->text($col)->nullable();
                    });
                }
            }
        }

        // employees
        if (Schema::hasTable('employees')) {
            $cols = ['employee_requirements', 'timework', 'rate', 'increase', 'route_id', 'driver_data'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('employees', $col)) {
                    Schema::table('employees', function (Blueprint $table) use ($col) {
                        $table->text($col)->nullable();
                    });
                }
            }
        }

        // routes
        if (Schema::hasTable('routes')) {
            $cols = ['company_id', 'color', 'car_requirements', 'employee_requirements', 'car_data', 'driver_data'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('routes', $col)) {
                    Schema::table('routes', function (Blueprint $table) use ($col) {
                        $table->text($col)->nullable();
                    });
                }
            }
        }
    }
};
