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
        Schema::connection('seeds')->create('osago_models', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->timestamps();
            $table->timestamp('choosed_at')->nullable();
            $table->softDeletes();
            $table->string('code')->nullable();
            $table->text('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('osago_models');
    }
};
