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
        Schema::connection('seeds')->create('filters', function (Blueprint $table) {
            $table->comment('');
            $table->integer('id', true);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->text('config')->nullable();
            $table->integer('user_id');
            $table->string('name')->nullable();
            $table->integer('data_type')->nullable();
            $table->tinyInteger('is_active')->default(0);
            $table->integer('sort')->default(1);
            $table->tinyInteger('is_hidden')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('filters');
    }
};
