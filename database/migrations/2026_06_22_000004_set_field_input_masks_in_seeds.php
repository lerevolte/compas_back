<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._set_field_input_masks.php — то же в базе-шаблоне
 * (connection: seeds) для новых порталов (8476/8477).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }
        $db = DB::connection('seeds');

        $db->table('data_rows')->where('data_type_id', 3)
            ->where('field', 'time')->update(['mask' => '##:## - ##:##']);
        $db->table('data_rows')->where('field', 'loading_time')->update(['mask' => '##:##']);
        $db->table('data_rows')->where('data_type_id', 1)
            ->whereIn('field', ['phone', 'work_phone'])->update(['mask' => '+#(###)###-##-##']);
        $db->table('data_rows')->where('data_type_id', 1)
            ->where('field', 'email')->update(['mask' => 'email']);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }
        $db = DB::connection('seeds');
        $db->table('data_rows')->where('data_type_id', 3)->where('field', 'time')->update(['mask' => null]);
        $db->table('data_rows')->where('field', 'loading_time')->update(['mask' => null]);
        $db->table('data_rows')->where('data_type_id', 1)
            ->whereIn('field', ['phone', 'work_phone', 'email'])->update(['mask' => null]);
    }
};
