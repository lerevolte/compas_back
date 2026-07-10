<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['fund_percent', 'fund_calculate_type'];

    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $db = DB::connection('seeds');

        $employeesTypeId = $db->table('data_types')->where('slug', 'employees')->value('id');
        if (!$employeesTypeId) {
            return;
        }

        $rows = $db->table('data_rows')
            ->where('data_type_id', $employeesTypeId)
            ->whereIn('field', $this->fields)
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        if ($schema->hasTable('field_values')) {
            $db->table('field_values')
                ->whereIn('field_id', $rows->pluck('id')->all())
                ->delete();
        }

        $db->table('data_rows')
            ->whereIn('id', $rows->pluck('id')->all())
            ->delete();

        $sectionIds = $rows->pluck('module_section_id')->filter()->unique()->all();
        if ($sectionIds && $schema->hasTable('field_sections')) {
            foreach ($sectionIds as $sectionId) {
                $stillUsed = $db->table('data_rows')
                    ->where('module_section_id', $sectionId)
                    ->exists();
                if (!$stillUsed) {
                    $db->table('field_sections')->where('id', $sectionId)->delete();
                }
            }
        }
    }

    public function down(): void
    {
    }
};
