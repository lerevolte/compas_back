<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'priority')
            ->update(['is_permanent' => 0, 'is_default' => 0]);
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'priority')
            ->update(['is_permanent' => 1, 'is_default' => 1]);
    }
};
