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
        Schema::connection('seeds')->create('cars', function (Blueprint $table) {
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
            $table->longText('autodor_id')->nullable();
            $table->integer('carsmonitoring_id')->nullable();
            $table->text('dokumenty_2494')->nullable();
            $table->text('primecanie_2571')->nullable();
            $table->text('121_2588')->nullable();
            $table->text('123_2588')->nullable();
            $table->text('321_213_2588')->nullable();
            $table->text('fyvifcyvicui_2588')->nullable();
            $table->text('okowkecokew_2645')->nullable();
            $table->text('uvucvcuv_2647')->nullable();
            $table->text('test_daty_2648')->nullable();
            $table->text('000_2649')->nullable();
            $table->text('test_2650')->nullable();
            $table->text('2_2699')->nullable();
            $table->text('3_2699')->nullable();
            $table->text('4_2699')->nullable();
            $table->text('test_2_2699')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('cars');
    }
};
