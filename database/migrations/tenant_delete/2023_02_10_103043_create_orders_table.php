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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->comment('');
            $table->timestamps();
            $table->softDeletes();
            $table->string('number')->nullable();
            $table->text('address')->nullable();
            $table->string('weight')->nullable();
            $table->string('time')->nullable();
            $table->string('type')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->text('comment')->nullable();
            $table->string('payment')->nullable();
            $table->string('invoice')->nullable();
            $table->date('date')->nullable();
            $table->string('manager')->nullable();
            $table->string('service_time')->nullable()->default('30');
            $table->tinyInteger('payment_type')->nullable();
            $table->integer('store_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('store_name')->nullable();
            $table->tinyInteger('is_store')->nullable()->default(0);
            $table->tinyInteger('is_address')->nullable()->default(0);
            $table->integer('route_id')->nullable();
            $table->string('delivery_price')->nullable();
            $table->string('unloading')->nullable();
            $table->string('date_create')->nullable();
            $table->tinyInteger('hide')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('point_status')->nullable()->default(1);
            $table->tinyInteger('splitted')->nullable()->default(0);
            $table->integer('replic_num')->nullable();
            $table->integer('replic_type')->nullable();
            $table->bigInteger('payment_status')->nullable()->default(1);
            $table->string('link')->nullable();
            $table->bigInteger('docs_status')->nullable()->default(1);
            $table->tinyInteger('is_supply')->nullable();
            $table->tinyInteger('is_tc')->nullable()->default(0);
            $table->integer('replic_num_split')->nullable();
            $table->text('products')->nullable();
            $table->string('crm_link')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('client_id')->nullable();
            $table->integer('sort')->nullable();
            $table->string('copy_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
