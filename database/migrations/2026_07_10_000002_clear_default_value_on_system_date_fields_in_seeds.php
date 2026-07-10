<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $db = DB::connection('seeds');

        $reset = [];
        if ($schema->hasColumn('data_rows', 'set_default')) {
            $reset['set_default'] = 0;
        }
        if ($schema->hasColumn('data_rows', 'default_value')) {
            $reset['default_value'] = null;
        }
        if (!$reset) {
            return;
        }

        $db->table('data_rows')
            ->whereIn('field', ['created_at', 'updated_at'])
            ->update($reset);

        $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if ($tasksTypeId) {
            $db->table('data_rows')
                ->where('data_type_id', $tasksTypeId)
                ->where('field', 'plan_time')
                ->update($reset);
        }
    }

    public function down(): void
    {
    }
};
