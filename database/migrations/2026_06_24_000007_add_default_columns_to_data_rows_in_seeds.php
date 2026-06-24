<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        if (!Schema::connection('seeds')->hasColumn('data_rows', 'default_value')) {
            Schema::connection('seeds')->table('data_rows', function (Blueprint $table) {
                $table->text('default_value')->nullable();
            });
        }

        if (!Schema::connection('seeds')->hasColumn('data_rows', 'set_default')) {
            Schema::connection('seeds')->table('data_rows', function (Blueprint $table) {
                $table->tinyInteger('set_default')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Колонки не удаляем — на них могут опираться другие данные.
    }
};
