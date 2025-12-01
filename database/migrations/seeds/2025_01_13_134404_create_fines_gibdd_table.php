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
        Schema::connection('seeds')->create('fines_gibdd', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->text('address')->nullable();
            $table->date('date')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('employee_id')->nullable();
            $table->integer('car_id')->nullable();
            $table->string('number_doc')->nullable();
            $table->date('date_doc')->nullable();
            $table->text('place')->nullable();
            $table->string('article')->nullable();
            $table->text('discharged_by')->nullable();
            $table->text('name_of_payment')->nullable();
            $table->string('kbk')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('bank')->nullable();
            $table->string('invoice')->nullable();
            $table->string('corr_invoice')->nullable();
            $table->string('bik')->nullable();
            $table->string('oktmo')->nullable();
            $table->double('sum')->nullable();
            $table->double('discount_sum')->nullable();
            $table->date('sale_finish')->nullable();
            $table->text('name')->nullable();
            $table->text('photo')->nullable();
            $table->timestamp('choosed_at')->nullable();
            $table->integer('division_id')->nullable();
            $table->string('color')->nullable();
            $table->text('icon')->nullable();
            $table->text('payment')->nullable();
            $table->text('wad_2617')->nullable();
            $table->text('qwdqww_2617')->nullable();
            $table->text('qwdqwd_2617')->nullable();
            $table->text('wd_2617')->nullable();
            $table->text('12_2621')->nullable();
            $table->text('34_2621')->nullable();
            $table->text('56_2621')->nullable();
            $table->text('ewwf_2621')->nullable();
            $table->text('1231231231_2629')->nullable();
            $table->text('1231_2631')->nullable();
            $table->text('22_2632')->nullable();
            $table->text('33_2632')->nullable();
            $table->text('44_2632')->nullable();
            $table->text('11111111111111_2632')->nullable();
            $table->string('wire_username')->nullable();
            $table->string('payer_identifier')->nullable();
            $table->integer('camera_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('fines_gibdd');
    }
};
