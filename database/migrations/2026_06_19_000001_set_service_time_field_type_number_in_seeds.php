<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._set_service_time_field_type_number.php: тот же апдейт в
 * базе-шаблоне admin_seeds (connection: seeds), чтобы у НОВЫХ регистрируемых
 * порталов поле service_time сразу было типа number.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'service_time')
            ->update(['type' => 'number']);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('field', 'service_time')
            ->update(['type' => 'text']);
    }
};
