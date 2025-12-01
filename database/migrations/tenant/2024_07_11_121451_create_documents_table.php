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
        Schema::create('documents', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->timestamps();
            $table->softDeletes();
            $table->string('name')->nullable();
            $table->integer('requisite_id')->nullable();
            $table->unsignedInteger('sum')->nullable();
            $table->text('file')->nullable();
            $table->text('photo')->nullable();
            $table->text('act')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
};
