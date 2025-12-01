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
        Schema::connection('seeds')->create('data_rows', function (Blueprint $table) {
            $table->comment('');
            $table->bigIncrements('id');
            $table->unsignedInteger('data_type_id')->nullable()->index('data_rows_data_type_id_foreign');
            $table->string('field')->nullable();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->boolean('required')->default(false);
            $table->text('details')->nullable();
            $table->boolean('visible_always')->nullable()->default(false);
            $table->string('label_color')->nullable();
            $table->integer('section_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('sort')->nullable();
            $table->timestamps();
            $table->string('button_name')->nullable()->default('Загрузить');
            $table->tinyInteger('show_file_image')->nullable()->default(0);
            $table->tinyInteger('hide')->default(0);
            $table->tinyInteger('is_plural')->default(0);
            $table->string('roles_read')->nullable();
            $table->string('roles_write')->nullable();
            $table->tinyInteger('is_remove')->default(0);
            $table->text('mobile_pages')->nullable();
            $table->string('display_parent_name')->nullable();
            $table->text('rules')->nullable();
            $table->tinyInteger('only_read')->nullable()->default(0);
            $table->tinyInteger('is_permanent')->nullable();
            $table->tinyInteger('show_file_name')->nullable()->default(0);
            $table->string('external_link')->nullable();
            $table->tinyInteger('is_external_link')->nullable()->default(0);
            $table->string('module')->nullable();
            $table->tinyInteger('is_link')->nullable()->default(0);
            $table->string('unit')->nullable();
            $table->text('module_section_id')->nullable();
            $table->tinyInteger('is_default')->nullable()->default(0);
            $table->tinyInteger('is_inactive')->nullable();
            $table->tinyInteger('blocked_changes')->nullable()->default(0);
            $table->string('mask')->nullable();
            $table->tinyInteger('permanent_required')->default(0);
            $table->tinyInteger('permanent_name')->default(0);
            $table->string('relation_table')->nullable();
            $table->text('options')->nullable();
            $table->tinyInteger('set_color')->default(0);
            $table->string('related_field')->nullable();
            $table->tinyInteger('is_unique')->nullable()->default(0);
            $table->tinyInteger('is_program')->nullable()->default(0);
            $table->text('subfields')->nullable();
            $table->text('dependency_fields')->nullable();

            $table->unique(['id'], 'id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('seeds')->dropIfExists('data_rows');
    }
};
