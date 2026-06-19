<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Флаг set_default у поля (data_rows) — по аналогии с set_color: значение по
 * умолчанию (default_value) может быть сохранено, но применяется при создании
 * объекта ТОЛЬКО когда set_default = 1 (8461).
 *
 * Бэкфилл: у полей, где default_value уже задан (под старым UI он применялся
 * всегда), включаем set_default = 1, чтобы поведение не изменилось.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        if (!Schema::hasColumn('data_rows', 'set_default')) {
            Schema::table('data_rows', function (Blueprint $table) {
                $table->tinyInteger('set_default')->default(0);
            });
        }

        if (Schema::hasColumn('data_rows', 'default_value')) {
            DB::table('data_rows')
                ->whereNotNull('default_value')
                ->where('default_value', '!=', '')
                ->update(['set_default' => 1]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('data_rows') && Schema::hasColumn('data_rows', 'set_default')) {
            Schema::table('data_rows', function (Blueprint $table) {
                $table->dropColumn('set_default');
            });
        }
    }
};
