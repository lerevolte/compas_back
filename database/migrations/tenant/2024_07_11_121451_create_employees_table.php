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
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('photo')->nullable();
            $table->string('color_status')->nullable();
            $table->string('car_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('email')->nullable();
            $table->integer('sort')->nullable();
            $table->string('color')->nullable()->default('');
            $table->integer('related_user_id')->nullable();
            $table->string('driver_license')->nullable();
            $table->integer('carsmonitoring_id')->nullable();
            $table->text('fine_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
