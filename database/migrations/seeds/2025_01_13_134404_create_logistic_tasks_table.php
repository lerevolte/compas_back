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
        Schema::connection('seeds')->create('logistic_tasks', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->json('address')->nullable();
            $table->text('name')->nullable();
            $table->integer('route_id')->nullable();
            $table->bigInteger('point_status')->nullable();
            $table->text('products')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('sort')->nullable();
            $table->string('client_id')->nullable();
            $table->text('photo')->nullable();
            $table->text('fail_2570')->nullable();
            $table->text('111_2653')->nullable();
            $table->text('111_2664')->nullable();
            $table->text('1_2676')->nullable();
            $table->text('2_2676')->nullable();
            $table->text('3_2676')->nullable();
            $table->text('stroka_neskolko_znacenii_2676')->nullable();
            $table->text('222_2680')->nullable();
            $table->text('data_2681')->nullable();
            $table->text('cislo_2683')->nullable();
            $table->text('status_2684')->nullable();
            $table->text('fail_2685')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('logistic_tasks');
    }
};
