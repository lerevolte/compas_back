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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->comment('');
            $table->string('key');
            $table->string('entity')->nullable();
            $table->timestamps();
            //$table->string('name')->nullable();
            $table->tinyInteger('visible')->default(0);
            $table->string('perm_type')->nullable();
            $table->integer('account_id')->nullable();
            $table->string('perm_value', 1)->nullable();
            $table->string('field_name')->nullable();
            $table->integer('parent_id')->nullable();
            $table->string('area')->nullable();
            $table->string('field_display_name')->nullable();
            $table->integer('role_id')->nullable();
            $table->tinyInteger('is_parent')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
