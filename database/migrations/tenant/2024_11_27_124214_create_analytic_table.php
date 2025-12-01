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
        Schema::create('analytic', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->string('entity')->nullable();
            $table->string('field')->nullable();
            $table->string('group_by')->nullable();
            $table->text('acondition')->nullable();
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->string('sort_field')->nullable();
            $table->string('sort_order')->nullable();
            $table->text('active_rows')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('type')->nullable();
            $table->text('config')->nullable();
            $table->text('test')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytic');
    }
};
