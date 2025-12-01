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
        Schema::create('cars', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->string('name')->nullable();
            $table->text('photo')->nullable();
            $table->string('color_status')->nullable();
            $table->text('employee_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('color')->nullable()->default('');
            $table->string('number')->nullable();
            $table->string('sts_number')->nullable();
            $table->integer('sort')->nullable();
            $table->timestamp('choosed_at')->nullable();
            $table->text('fine_id')->nullable();
            $table->text('autodor_id')->nullable();
            $table->integer('carsmonitoring_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cars');
    }
};
