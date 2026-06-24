<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('type', 'sidebar')
            ->where('value', 'like', '%Справочник адресов%')
            ->get()
            ->each(function ($row) {
                DB::table('settings')->where('id', $row->id)->update([
                    'value' => str_replace('Справочник адресов', 'Библиотека задач', $row->value),
                ]);
            });

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('type', 'sidebar')
            ->where('value', 'like', '%Библиотека задач%')
            ->get()
            ->each(function ($row) {
                DB::table('settings')->where('id', $row->id)->update([
                    'value' => str_replace('Библиотека задач', 'Справочник адресов', $row->value),
                ]);
            });
    }
};
