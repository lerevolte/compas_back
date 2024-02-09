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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->comment('');
            $table->timestamps();
            $table->string('point_field')->nullable();
            $table->string('domain')->nullable();
            $table->text('yandex_api_key')->nullable();
            $table->string('map_city')->nullable();
            $table->tinyInteger('map_provider')->default(1);
            $table->integer('map_zone_radius')->nullable();
            $table->integer('map_zone_car_radius')->nullable();
            $table->integer('map_stop_time')->nullable();
            $table->integer('map_stop_car_radius')->nullable();
            $table->string('map_latitude')->nullable();
            $table->string('map_longitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
