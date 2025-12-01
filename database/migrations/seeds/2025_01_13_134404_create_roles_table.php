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
        Schema::connection('seeds')->create('roles', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('display_name');
            $table->timestamps();
            $table->integer('sort')->default(0);
            $table->tinyInteger('is_admin')->default(0);
            $table->softDeletes();
            $table->tinyInteger('is_permanent')->nullable();
            $table->text('tables')->nullable();
            $table->timestamp('choosed_at')->nullable();
            $table->text('menus')->nullable();
            $table->text('sidebar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('roles');
    }
};
