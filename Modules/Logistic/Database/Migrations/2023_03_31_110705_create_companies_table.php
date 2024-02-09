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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
            $table->string('name')->nullable();
            $table->integer('user_id')->nullable();
            /*$table->text('stat_block')->nullable();
            $table->string('stat_all_income')->default(0)->nullable();
            $table->string('stat_max_sum')->default(0)->nullable();
            $table->string('stat_sum')->default(0)->nullable();
            $table->string('stat_expenses')->default(0)->nullable();*/
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
