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
        Schema::connection('seeds')->create('requisites', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->timestamp('choosed_at')->nullable();
            $table->string('name')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->text('address')->nullable();
            $table->text('fact_address')->nullable();
            $table->integer('company_id')->nullable();
            $table->softDeletes();
            $table->string('account')->nullable();
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('requisites');
    }
};
