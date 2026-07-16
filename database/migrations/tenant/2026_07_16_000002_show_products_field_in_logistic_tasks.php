<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::apply(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }

        $typeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId) {
            return;
        }

        $row = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'products')
            ->first();
        if (!$row) {
            return;
        }

        $update = ['only_read' => 1];
        if ($row->hide) {
            $sectionId = $db->table('field_sections')
                ->where('page', 'logistic_tasks')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
            if (!$sectionId) {
                $sectionId = $db->table('field_sections')->where('page', 'logistic_tasks')->orderBy('id')->value('id');
            }
            if ($sectionId) {
                $update['hide'] = 0;
                if (!$row->section_id) {
                    $update['section_id'] = $sectionId;
                }
            }
        }

        $db->table('data_rows')->where('id', $row->id)->update($update);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }
        $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if ($typeId) {
            DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'products')
                ->update(['only_read' => 0, 'hide' => 1]);
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
