<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('car_categories')) return;
        
        Schema::create('car_categories', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('choosed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->integer('user_id')->nullable();
            $table->binary('model_id')->nullable();
            $table->string('name', 255)->nullable();
        });

        DB::table('car_categories')->insert([
            ['id' => 1, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Легковые'],
            ['id' => 2, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Лёгкие коммерческие'],
            ['id' => 3, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Грузовики'],
            ['id' => 4, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Седельные тягачи'],
            ['id' => 5, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Автобусы'],
            ['id' => 6, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Строительная и дорожная'],
            ['id' => 7, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Погрузчики'],
            ['id' => 8, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Автокраны'],
            ['id' => 9, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Коммунальная'],
            ['id' => 10, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Мотоциклы'],
            ['id' => 11, 'created_at' => now(), 'updated_at' => now(), 'name' => 'Скутеры'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('car_categories');
    }
};
