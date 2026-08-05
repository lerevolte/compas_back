<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        foreach (['logistic_tasks', 'deals', 'contacts', 'addresses', 'warehouses'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'color')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('color')->nullable()->default('');
                });
            }
        }
    }

    public function down()
    {
        foreach (['logistic_tasks', 'deals', 'contacts', 'addresses', 'warehouses'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'color')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('color');
                });
            }
        }
    }
};
