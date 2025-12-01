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
        Schema::create('osago_polises', function (Blueprint $table) {
            $table->comment('');
            $table->unsignedBigInteger('id');
            $table->timestamps();
            $table->softDeletes();
            $table->text('name')->nullable();
            $table->text('documents')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('sort')->nullable();
            $table->timestamp('choosed_at')->nullable();
            $table->text('car_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('date_start')->nullable();
            $table->string('date_end')->nullable();
            $table->integer('period')->nullable();
            $table->string('purpose')->nullable();
            $table->text('model_id')->nullable();
            $table->string('category')->nullable();
            $table->string('has_trailer')->nullable();
            $table->string('policyholder_name')->nullable();
            $table->string('policyholder_last_name')->nullable();
            $table->string('policyholder_patronymic')->nullable();
            $table->string('policyholder_email')->nullable();
            $table->string('policyholder_phone')->nullable();
            $table->text('policyholder_address')->nullable();
            $table->string('policyholder_passport_number')->nullable();
            $table->string('policyholder_passport_series')->nullable();
            $table->string('policyholder_passport_date')->nullable();
            $table->string('policyholder_birthday')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('osago_polises');
    }
};
