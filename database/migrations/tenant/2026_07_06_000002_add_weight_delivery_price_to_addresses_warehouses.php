<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $newFields = ['weight', 'delivery_price'];

    public function up(): void
    {
        foreach (['addresses', 'warehouses'] as $tbl) {
            if (!Schema::hasTable($tbl)) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'weight')) {
                    $table->text('weight')->nullable();
                }
                if (!Schema::hasColumn($tbl, 'delivery_price')) {
                    $table->text('delivery_price')->nullable();
                }
            });
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $srcTypeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');

        foreach (['addresses', 'warehouses'] as $slug) {
            $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                continue;
            }

            $sectionId = DB::table('field_sections')
                ->where('page', $slug)
                ->whereNull('module')
                ->orderBy('id')
                ->value('id');
            if (!$sectionId) {
                $sectionId = DB::table('field_sections')->where('page', $slug)->orderBy('id')->value('id');
            }

            $moduleSecId = DB::table('field_sections')
                ->where('page', $slug)
                ->where('module', 'logistic')
                ->orderBy('id')
                ->value('id');

            foreach ($this->newFields as $field) {
                $exists = DB::table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', $field)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $src = $srcTypeId
                    ? DB::table('data_rows')->where('data_type_id', $srcTypeId)->where('field', $field)->first()
                    : null;

                if ($src) {
                    $row = (array) $src;
                    unset($row['id']);
                    $row['data_type_id'] = $typeId;
                    $row['section_id'] = $sectionId;
                    $hasModuleSection = false;
                    $msRaw = trim((string) ($row['module_section_id'] ?? ''));
                    if ($msRaw !== '' && $msRaw !== '0' && $msRaw !== '[]') {
                        $decoded = json_decode($msRaw, true);
                        $hasModuleSection = is_array($decoded) && count($decoded) > 0;
                    }
                    $row['module_section_id'] = $hasModuleSection && $moduleSecId ? '['.$moduleSecId.']' : null;
                } else {
                    $maxSort = (int) DB::table('data_rows')->where('data_type_id', $typeId)->max('sort');
                    $row = [
                        'data_type_id' => $typeId,
                        'field' => $field,
                        'type' => 'number',
                        'title' => $field == 'weight' ? 'Вес' : 'Цена доставки',
                        'unit' => $field == 'weight' ? 'кг' : 'руб',
                        'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
                        'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
                        'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
                        'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
                        'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
                        'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
                        'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
                        'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                        'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
                        'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
                        'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
                    ];
                }

                DB::table('data_rows')->insert($row);
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            foreach (['addresses', 'warehouses'] as $slug) {
                $typeId = DB::table('data_types')->where('slug', $slug)->value('id');
                if ($typeId) {
                    DB::table('data_rows')
                        ->where('data_type_id', $typeId)
                        ->whereIn('field', $this->newFields)
                        ->delete();
                }
            }
        }

        foreach (['addresses', 'warehouses'] as $tbl) {
            if (!Schema::hasTable($tbl)) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (Schema::hasColumn($tbl, 'weight')) {
                    $table->dropColumn('weight');
                }
                if (Schema::hasColumn($tbl, 'delivery_price')) {
                    $table->dropColumn('delivery_price');
                }
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
