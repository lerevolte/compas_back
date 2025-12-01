<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('seeds')->create('mileages', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->integer('mileage_start_day')->nullable()->default(0);
            $table->integer('mileage_end_day')->nullable();
            $table->integer('mileage_day')->nullable();
            $table->integer('engine_hours_start_day')->nullable()->default(0);
            $table->integer('engine_hours_end_day')->nullable();
            $table->integer('engine_hours_day')->nullable();
            $table->integer('auto_day_mileage')->nullable()->default(0);
            $table->integer('auto_day_engine_hours')->nullable()->default(0);
            $table->text('comment')->nullable();
            $table->integer('car_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('worker_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('mileages');
    }
};
