<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('data_types') || !Schema::hasTable('data_rows')) {
            return;
        }

        if (!Schema::hasColumn('accounts', 'owner_type')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->text('owner_type')->nullable();
            });
        }
        if (!Schema::hasColumn('accounts', 'phone')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->text('phone')->nullable();
            });
        }

        $type = DB::table('data_types')->where('slug', 'accounts')->first();
        if (!$type) {
            return;
        }

        $sectionId = null;
        if (Schema::hasTable('field_sections')) {
            $sectionId = DB::table('field_sections')
                ->where('page', 'accounts')
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
            if (!$sectionId) {
                $sectionId = DB::table('field_sections')
                    ->where('page', 'accounts')
                    ->orderBy('id')
                    ->value('id');
            }
        }

        $maxSort = (int) DB::table('data_rows')->where('data_type_id', $type->id)->max('sort');

        $rows = [
            [
                'field' => 'owner_type',
                'type' => 'select_dropdown',
                'title' => 'Кем вы являетесь',
                'details' => json_encode(['options' => [
                    ['value' => 1, 'label' => 'Грузовладелец'],
                    ['value' => 2, 'label' => 'Владелец автопарка'],
                    ['value' => 3, 'label' => 'Владелец груза и автопарка'],
                ]], JSON_UNESCAPED_UNICODE),
                'mask' => null,
                'sort' => $maxSort + 1,
            ],
            [
                'field' => 'phone',
                'type' => 'text',
                'title' => 'Телефон',
                'details' => null,
                'mask' => '+#(###)###-##-##',
                'sort' => $maxSort + 2,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('field', $row['field'])
                ->exists();
            if ($exists) {
                continue;
            }

            $insert = [
                'data_type_id' => $type->id,
                'field' => $row['field'],
                'type' => $row['type'],
                'title' => $row['title'],
                'required' => 0,
                'details' => $row['details'],
                'visible_always' => 1,
                'label_color' => '',
                'section_id' => $sectionId,
                'group_id' => null,
                'sort' => $row['sort'],
                'created_at' => null,
                'updated_at' => null,
                'button_name' => 'Загрузить',
                'show_file_image' => 0,
                'hide' => $sectionId ? 0 : 1,
                'is_plural' => 0,
                'roles_read' => '',
                'roles_write' => '',
                'is_remove' => 0,
                'mobile_pages' => '',
                'display_parent_name' => null,
                'rules' => null,
                'only_read' => 0,
                'is_permanent' => 0,
                'show_file_name' => 0,
                'external_link' => '',
                'is_external_link' => 0,
                'module' => '',
                'is_link' => 0,
                'unit' => '',
                'module_section_id' => null,
                'is_default' => 0,
                'is_inactive' => 0,
                'blocked_changes' => 0,
                'mask' => $row['mask'],
                'permanent_required' => 0,
                'permanent_name' => 0,
                'relation_table' => null,
                'options' => null,
                'set_color' => 0,
                'related_field' => null,
                'is_unique' => 0,
                'is_program' => 0,
                'subfields' => null,
                'dependency_fields' => null,
            ];

            foreach (array_keys($insert) as $col) {
                if (!Schema::hasColumn('data_rows', $col)) {
                    unset($insert[$col]);
                }
            }

            DB::table('data_rows')->insert($insert);
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $type = DB::table('data_types')->where('slug', 'accounts')->first();
        if ($type) {
            DB::table('data_rows')
                ->where('data_type_id', $type->id)
                ->whereIn('field', ['owner_type', 'phone'])
                ->delete();
        }
        foreach (['owner_type', 'phone'] as $col) {
            if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', $col)) {
                Schema::table('accounts', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
