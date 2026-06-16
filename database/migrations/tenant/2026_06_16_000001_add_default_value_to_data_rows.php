<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('data_rows') && !Schema::hasColumn('data_rows', 'default_value')) {
            Schema::table('data_rows', function (Blueprint $table) {
                $table->text('default_value')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('data_rows') && Schema::hasColumn('data_rows', 'default_value')) {
            Schema::table('data_rows', function (Blueprint $table) {
                $table->dropColumn('default_value');
            });
        }
    }
};
