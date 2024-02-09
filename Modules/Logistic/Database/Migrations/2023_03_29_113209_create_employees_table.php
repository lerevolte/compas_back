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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
            $table->integer('rate')->nullable();
            $table->integer('timework')->default(0)->nullable();
            $table->integer('increase')->default(0)->nullable();
            $table->integer('fund_percent')->default(0)->nullable();
            $table->integer('fund_calculate_type')->default(1)->nullable();
            $table->string('color_status')->nullable();
            $table->string('car_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('app_id')->nullable();
            $table->text('statistic')->nullable();
            $table->string('stat_all_income')->default(0)->nullable();
            $table->string('stat_max_sum')->default(0)->nullable();
            $table->string('stat_sum')->default(0)->nullable();
            $table->string('stat_expenses')->default(0)->nullable();
            $table->string('geo_online')->default(0)->nullable();
            $table->text('stat_block')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
