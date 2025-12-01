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
        Schema::create('autodor', function (Blueprint $table) {
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
            $table->integer('camera_id')->nullable();
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
            $table->text('payer_identifier')->nullable();
            $table->text('wire_username')->nullable();
            $table->text('additional_payer_identifier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('autodor');
    }
};
