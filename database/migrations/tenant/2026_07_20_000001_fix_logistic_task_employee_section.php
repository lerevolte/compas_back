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
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types') || !$schema->hasTable('field_sections')) {
            return;
        }
        $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$tasksTypeId) {
            return;
        }

        $detailSectionIds = $db->table('field_sections')
            ->where('page', 'logistic_tasks')
            ->whereNull('module')
            ->where('hide', 0)
            ->whereIn('column_id', [1, 2])
            ->orderBy('id')
            ->pluck('id')
            ->all();
        if (!count($detailSectionIds)) {
            return;
        }

        $employeeRow = $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('field', 'employee_id')
            ->first();
        if (!$employeeRow) {
            return;
        }

        if (in_array((int) $employeeRow->section_id, array_map('intval', $detailSectionIds), true)
            && !$employeeRow->hide && !$employeeRow->is_remove && !$employeeRow->group_id) {
            return;
        }

        $anchor = $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->whereIn('field', ['name', 'address', 'delivery_date'])
            ->whereIn('section_id', $detailSectionIds)
            ->where('hide', 0)
            ->where('is_remove', 0)
            ->orderByRaw("FIELD(field, 'name', 'address', 'delivery_date')")
            ->first();
        $targetSectionId = $anchor->section_id ?? $detailSectionIds[0];

        $maxSort = (int) $db->table('data_rows')
            ->where('data_type_id', $tasksTypeId)
            ->where('section_id', $targetSectionId)
            ->max('sort');

        $db->table('data_rows')->where('id', $employeeRow->id)->update([
            'section_id' => $targetSectionId,
            'sort' => $maxSort + 1,
            'hide' => 0,
            'is_remove' => 0,
            'group_id' => null,
        ]);
    }

    public function down(): void
    {
    }
};
