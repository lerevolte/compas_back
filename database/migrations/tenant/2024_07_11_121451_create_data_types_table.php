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
        Schema::create('data_types', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('title_singular');
            $table->string('title_plural');
            $table->string('icon')->nullable();
            $table->string('model_name')->nullable();
            $table->string('policy_name')->nullable();
            $table->string('controller')->nullable();
            $table->string('description')->nullable();
            $table->boolean('generate_permissions')->default(false);
            $table->tinyInteger('server_side')->default(0);
            $table->text('details')->nullable();
            $table->timestamps();
            $table->string('color')->nullable()->default('#275093');
            $table->text('menu')->nullable();
            $table->tinyInteger('enable')->nullable()->default(0);
            $table->string('slug_singular')->nullable();
            $table->integer('hidden')->nullable()->default(0);
            $table->text('empty_text')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_types');
    }
};
