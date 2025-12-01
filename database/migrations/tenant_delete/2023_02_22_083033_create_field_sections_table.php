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
        Schema::create('field_sections', function (Blueprint $table) {
            $table->comment('');
            $table->unsignedInteger('id')->primary();
            $table->integer('sort')->nullable();
            $table->string('name')->nullable();
            $table->string('domain_key')->nullable();
            $table->string('page')->nullable();
            $table->timestamps();
            $table->integer('account_id')->nullable();
            $table->integer('column_id')->default(1);
            $table->tinyInteger('hide')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('field_sections');
    }
};
