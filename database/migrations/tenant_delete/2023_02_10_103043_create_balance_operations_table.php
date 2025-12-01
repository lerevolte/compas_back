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
        Schema::create('balance_operations', function (Blueprint $table) {
            $table->id();
            $table->comment('');
            $table->timestamps();
            $table->integer('balance_id')->nullable();
            $table->string('type')->nullable();
            $table->bigInteger('sum')->nullable();
            $table->string('date')->nullable();
            $table->integer('count_routes')->nullable();
            $table->string('comment')->nullable();
            $table->integer('count_mobile')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('balance_operations');
    }
};
