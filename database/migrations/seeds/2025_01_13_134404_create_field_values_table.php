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
        Schema::connection('seeds')->create('field_values', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->integer('field_id');
            $table->string('color', 100)->nullable();
            $table->string('file')->nullable();
            $table->string('value')->nullable();
            $table->integer('sort')->nullable();
            $table->tinyInteger('is_hidden')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('field_values');
    }
};
