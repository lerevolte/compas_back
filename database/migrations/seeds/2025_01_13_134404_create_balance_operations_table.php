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
        Schema::connection('seeds')->create('balance_operations', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('balance_id')->nullable();
            $table->string('type')->nullable();
            $table->bigInteger('sum')->nullable();
            $table->string('date')->nullable();
            $table->integer('count_routes')->nullable();
            $table->string('comment')->nullable();
            $table->integer('count_mobile')->nullable();
            $table->integer('document_id')->nullable();
            $table->integer('invoice_id')->nullable();
            $table->integer('balance_before')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('balance_operations');
    }
};
