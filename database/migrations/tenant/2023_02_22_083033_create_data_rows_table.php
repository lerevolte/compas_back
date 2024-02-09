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
        Schema::create('data_rows', function (Blueprint $table) {
            $table->comment('');
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('data_type_id')->index('data_rows_data_type_id_foreign')->nullable();
            $table->string('field')->nullable();
            $table->string('type')->nullable();
            $table->string('display_name')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('browse')->default(true);
            $table->boolean('read')->default(true);
            $table->boolean('edit')->default(true);
            $table->boolean('add')->default(true);
            $table->boolean('delete')->default(true);
            $table->text('details')->nullable();
            $table->integer('order')->default(1);
            $table->integer('account_id')->nullable();
            $table->tinyInteger('visible_always')->nullable()->default(0);
            $table->string('label_color')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->string('measure')->nullable();
            $table->integer('sort')->nullable();
            $table->timestamps();
            $table->string('button_name')->nullable();
            $table->tinyInteger('show_file_image')->nullable()->default(0);
            $table->string('submodel')->nullable();
            $table->tinyInteger('hide')->default(0);
            $table->tinyInteger('is_plural')->default(0);
            $table->tinyInteger('perm_access')->default(0);
            $table->string('roles_read')->nullable();
            $table->string('roles_write')->nullable();
            $table->tinyInteger('is_remove')->default(0);
            $table->text('mobile_pages')->nullable();
            $table->string('display_parent_name')->nullable();
            $table->text('rules')->nullable();
            $table->tinyInteger('only_read')->default(0);
            $table->tinyInteger('is_permanent')->default(0)->nullable();
            $table->tinyInteger('show_file_name')->default(0)->nullable();
            $table->string('external_link')->nullable();
            $table->tinyInteger('is_external_link')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_rows');
    }
};
