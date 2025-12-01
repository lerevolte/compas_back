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
        Schema::create('salaries', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->string('timework')->nullable();
            $table->string('rate')->nullable();
            $table->string('increase')->nullable();
            $table->string('award')->nullable();
            $table->string('fund_percent')->nullable();
            $table->string('fund_sum')->nullable();
            $table->string('status')->nullable();
            $table->text('comment')->nullable();
            $table->integer('driver_id')->nullable();
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
        Schema::dropIfExists('salaries');
    }
};
