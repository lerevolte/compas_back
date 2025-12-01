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
            $table->increments('id');
            $table->integer('sort')->nullable();
            $table->string('name')->nullable();
            $table->string('domain_key')->nullable();
            $table->string('page')->nullable();
            $table->timestamps();
            $table->integer('account_id')->nullable();
            $table->tinyInteger('hide')->default(0);
            $table->tinyInteger('column_id')->nullable()->default(1);
            $table->string('module')->nullable();
            $table->integer('parent_id')->nullable();
            $table->unsignedInteger('_lft')->default(0);
            $table->unsignedInteger('_rgt')->default(0);
            $table->tinyInteger('is_short')->nullable()->default(0);
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
