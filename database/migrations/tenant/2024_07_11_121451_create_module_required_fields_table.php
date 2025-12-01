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
        Schema::create('module_required_fields', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->string('module')->nullable();
            $table->string('entity')->nullable();
            $table->text('field')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module_required_fields');
    }
};
