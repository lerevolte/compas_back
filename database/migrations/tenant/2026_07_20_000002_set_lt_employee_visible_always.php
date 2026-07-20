<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::apply(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class) && function_exists('tenant') && tenant('id')) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
            try {
                $now = now();
                foreach (DB::table('users')->pluck('id') as $userId) {
                    $updated = DB::table('local_cache')
                        ->where(['url' => 'logistic_tasks', 'user_id' => $userId])
                        ->update(['updated_at' => $now]);
                    if (!$updated) {
                        DB::table('local_cache')->insert([
                            'url' => 'logistic_tasks',
                            'user_id' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$tasksTypeId) {
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('field', 'employee_id')
            ->update(['visible_always' => 1]);
    }

    public function down(): void
    {
    }
};
