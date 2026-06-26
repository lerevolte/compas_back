<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Поле «Сотрудник Bitrix24» у пользователя (users) — select_dropdown, опции
 * которого подтягиваются из сотрудников Bitrix24 (имя+фамилия). Хранит ID
 * сотрудника Bitrix24; по нему Bitrix24Controller сопоставляет ответственного
 * сделки с пользователем сервиса.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'b24_responsible')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('b24_responsible')->nullable();
            });
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $usersTypeId = DB::table('data_types')->where('slug', 'users')->value('id');
        if (!$usersTypeId) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $usersTypeId)
            ->where('field', 'b24_responsible')
            ->exists();
        if ($exists) {
            return;
        }

        // Клонируем структуру существующего поля users, чтобы совпали все колонки.
        $sample = DB::table('data_rows')->where('data_type_id', $usersTypeId)->orderBy('sort', 'desc')->first();
        if (!$sample) {
            return;
        }
        $maxSort = (int) DB::table('data_rows')->where('data_type_id', $usersTypeId)->max('sort');

        $row = (array) $sample;
        unset($row['id']);
        $row['field']          = 'b24_responsible';
        $row['type']           = 'select_dropdown';
        $row['title']          = 'Сотрудник Bitrix24';
        $row['details']        = json_encode(['options' => []], JSON_UNESCAPED_UNICODE);
        $row['required']       = 0;
        $row['is_plural']      = 0;
        $row['only_read']      = 0;
        $row['is_default']     = 0;
        $row['is_unique']      = 0;
        $row['is_program']     = 0;
        $row['hide']           = 0;
        $row['relation_table'] = null;
        $row['related_field']  = null;
        $row['sort']           = $maxSort + 1;
        if (array_key_exists('options', $row)) {
            $row['options'] = null;
        }
        if (array_key_exists('module', $row)) {
            $row['module'] = '';
        }
        if (array_key_exists('module_section_id', $row)) {
            $row['module_section_id'] = null;
        }

        DB::table('data_rows')->insert($row);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_types') && Schema::hasTable('data_rows')) {
            $usersTypeId = DB::table('data_types')->where('slug', 'users')->value('id');
            if ($usersTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $usersTypeId)
                    ->where('field', 'b24_responsible')
                    ->delete();
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'b24_responsible')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('b24_responsible');
            });
        }
    }
};
