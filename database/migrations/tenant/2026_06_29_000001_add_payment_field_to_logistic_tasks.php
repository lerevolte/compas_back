<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Поле «Оплата, руб» (payment) в задачах логистики (logistic_tasks) — ТОЛЬКО
 * для портала logistopt6 (8557). Значение приходит из вебхука Bitrix24
 * (Bitrix24Controller@dealHook): «Счет: <номера>» если у сделки есть
 * привязанные счета (crm.invoice.list по UF_DEAL_ID), иначе
 * «Нал: <OPPORTUNITY> р.». Колонки payment/payment_type уже добавлены
 * миграцией 2026_06_04_000007. Здесь добавляем только описание поля в
 * data_rows, чтобы «Оплата, руб» отображалась в таблице/карточке. Поле
 * только для чтения — заполняется синхронизацией, вручную не редактируется.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Только портал logistopt6: даже если tenants:migrate запустят для всех
        // тенантов, поле получит лишь logistopt6.
        if (!function_exists('tenant') || tenant('id') !== 'logistopt6') {
            return;
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        // Колонка payment уже есть (2026_06_04_000007_add_bitrix24_fields_to_logistic_tasks),
        // подстраховываемся на случай нестандартного состояния БД.
        if (Schema::hasTable('logistic_tasks') && !Schema::hasColumn('logistic_tasks', 'payment')) {
            Schema::table('logistic_tasks', function (Blueprint $table) {
                $table->text('payment')->nullable();
            });
        }

        $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'payment')
            ->exists();
        if ($exists) {
            return;
        }

        // Клонируем структуру существующего поля logistic_tasks, чтобы совпали
        // все колонки data_rows.
        $sample = DB::table('data_rows')->where('data_type_id', $typeId)->orderBy('sort', 'desc')->first();
        if (!$sample) {
            return;
        }
        $maxSort = (int) DB::table('data_rows')->where('data_type_id', $typeId)->max('sort');

        $row = (array) $sample;
        unset($row['id']);
        $row['field']          = 'payment';
        $row['type']           = 'text';
        $row['title']          = 'Оплата, руб';
        $row['details']        = null;
        $row['required']       = 0;
        $row['is_plural']      = 0;
        $row['only_read']      = 1; // только чтение — значение из Bitrix24
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
        if (!function_exists('tenant') || tenant('id') !== 'logistopt6') {
            return;
        }
        if (!Schema::hasTable('data_types') || !Schema::hasTable('data_rows')) {
            return;
        }
        $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if ($typeId) {
            DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'payment')
                ->delete();
        }
    }
};
