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
        Schema::create('logistic_tasks', function (Blueprint $table) {
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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistic_tasks');
    }
};
