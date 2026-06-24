<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('warehouses')) {
            return;
        }

        Schema::create('warehouses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->json('address')->nullable();
            $table->text('name')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('sort')->nullable();
            $table->string('client_id')->nullable();
            $table->text('photo')->nullable();
            $table->text('car_requirements')->nullable();
            $table->text('employee_requirements')->nullable();
            $table->integer('service_time')->nullable()->default(0);
            $table->text('phone')->nullable();
            $table->text('time')->nullable();
            $table->text('comment')->nullable();
            $table->text('contact')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouses');
    }
};
