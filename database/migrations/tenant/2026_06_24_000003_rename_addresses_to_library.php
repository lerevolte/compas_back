<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('data_types')) {
            DB::table('data_types')
                ->where('slug', 'addresses')->orWhere('name', 'addresses')
                ->update([
                    'title_plural'   => 'Библиотека задач',
                    'title_singular' => 'Задача',
                ]);
        }

        if (Schema::hasTable('sidebar_items')) {
            DB::table('sidebar_items')
                ->where('slug', 'addresses')
                ->update(['name' => 'Библиотека задач']);
        }
    }

    public function down()
    {
        if (Schema::hasTable('data_types')) {
            DB::table('data_types')
                ->where('slug', 'addresses')->orWhere('name', 'addresses')
                ->update([
                    'title_plural'   => 'Справочник адресов',
                    'title_singular' => 'Адрес',
                ]);
        }

        if (Schema::hasTable('sidebar_items')) {
            DB::table('sidebar_items')
                ->where('slug', 'addresses')
                ->update(['name' => 'Справочник адресов']);
        }
    }
};
