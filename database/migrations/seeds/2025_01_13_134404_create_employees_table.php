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
        Schema::connection('seeds')->create('employees', function (Blueprint $table) {
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
            $table->text('telefon_blizkogo_celoveka_2573')->nullable();
            $table->text('foto_dokumentov_2575')->nullable();
            $table->text('dannye_voditelia_2576')->nullable();
            $table->text('novoe_pole_111111_2601')->nullable();
            $table->text('novoe_pole_2222_2602')->nullable();
            $table->text('primecanie_2_2610')->nullable();
            $table->text('primecanie_3_2611')->nullable();
            $table->text('test_2612')->nullable();
            $table->text('554_2638')->nullable();
            $table->text('uvcici_2711')->nullable();
            $table->text('civicuicu_2713')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('employees');
    }
};
