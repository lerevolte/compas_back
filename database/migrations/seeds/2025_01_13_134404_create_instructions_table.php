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
        Schema::connection('seeds')->create('instructions', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->integer('user_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->text('123212_2028')->nullable();
            $table->text('opisanie_2038')->nullable();
            $table->string('summa_strafa_2144')->nullable();
            $table->string('summa_premii_2145')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('instructions');
    }
};
