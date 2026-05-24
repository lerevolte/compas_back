<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('routes')) {
            if (!Schema::hasColumn('routes', 'choosed_at')) {
                Schema::table('routes', function (Blueprint $table) {
                    $table->timestamp('choosed_at')->nullable();
                });
            }
            if (!Schema::hasColumn('routes', 'task_id')) {
                Schema::table('routes', function (Blueprint $table) {
                    $table->text('task_id')->nullable();
                });
            }
        }
    }
};
