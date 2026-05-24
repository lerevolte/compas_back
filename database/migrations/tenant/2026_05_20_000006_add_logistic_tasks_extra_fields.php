<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('logistic_tasks')) {
            $cols = ['delivery_price', 'phone', 'priority', 'time'];
            foreach ($cols as $col) {
                if (!Schema::hasColumn('logistic_tasks', $col)) {
                    Schema::table('logistic_tasks', function (Blueprint $table) use ($col) {
                        $table->text($col)->nullable();
                    });
                }
            }
        }
    }
};
