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
        Schema::create('tabs', function (Blueprint $table) {
            $table->comment('');
            $table->increments('id');
            $table->string('title');
            $table->string('entity')->nullable();
            $table->string('tab')->nullable();
            $table->unsignedInteger('sort')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('has_roles_read')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tabs');
    }
};
