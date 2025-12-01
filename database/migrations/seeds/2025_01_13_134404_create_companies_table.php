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
        Schema::connection('seeds')->create('companies', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->string('name')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('color')->nullable()->default('');
            $table->text('employee_id')->nullable();
            $table->text('car_id')->nullable();
            $table->text('photo')->nullable();
            $table->text('fine_id')->nullable();
            $table->text('data_dostavki_2596')->nullable();
            $table->text('primecanie_11123_2603')->nullable();
            $table->text('11111_2725')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('companies');
    }
};
