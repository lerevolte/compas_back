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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('name')->nullable();
            $table->string('loading_time')->nullable();
            $table->string('number_tasks')->nullable();
            $table->string('mileage')->nullable();
            $table->string('time')->nullable();
            $table->string('weight')->nullable();
            $table->string('volume')->nullable();
            $table->string('rate')->nullable();
            $table->string('reserve_for_delivery')->nullable();
            $table->string('delivery_price')->nullable();
            $table->integer('car_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('user_id')->nullable();
            
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
};
