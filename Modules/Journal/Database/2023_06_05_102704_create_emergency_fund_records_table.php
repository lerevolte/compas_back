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
        Schema::create('emergency_fund_records', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->integer('emergency_fund_start_day')->default(0)->nullable();
            $table->integer('emergency_fund_end_day')->nullable();
            $table->integer('emergency_fund_day')->nullable();
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
        Schema::dropIfExists('emergency_fund_records');
    }
};
