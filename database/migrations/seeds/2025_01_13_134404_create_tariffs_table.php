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
        Schema::connection('seeds')->create('tariffs', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->text('prices')->nullable();
            $table->integer('sort')->nullable();
            $table->integer('price_per_day')->nullable();
            $table->text('restrictions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('tariffs');
    }
};
