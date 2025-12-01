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
        Schema::connection('seeds')->create('permissions', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->integer('entity_id')->nullable();
            $table->timestamps();
            $table->integer('parent_id')->nullable();
            $table->integer('role_id')->nullable();
            $table->string('read_p', 1)->default('A');
            $table->string('create_p', 1)->default('A');
            $table->string('update_p', 1)->default('A');
            $table->string('delete_p', 1)->default('A');
            $table->string('export_p', 1)->default('A');
            $table->string('import_p', 1)->default('A');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('permissions');
    }
};
