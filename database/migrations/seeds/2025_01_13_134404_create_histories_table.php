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
        Schema::connection('seeds')->create('histories', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('entity')->nullable();
            $table->integer('entity_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->text('text')->nullable();
            $table->string('module')->nullable();
            $table->string('event')->nullable()->default('FIELD_UPDATED');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('field')->nullable();
            $table->text('used_in_modules')->nullable();
            $table->string('color')->nullable()->default('#000');
            $table->integer('is_relation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('histories');
    }
};
