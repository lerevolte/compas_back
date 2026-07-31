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

        if ($schema->hasTable('products') && !$schema->hasColumn('products', 'category_id')) {
            $db->statement('ALTER TABLE `products` ADD COLUMN `category_id` TEXT NULL');
        }

        $categoriesType = $db->table('data_types')->where('slug', 'categories')->first();
        if (!$categoriesType) {
            $db->table('data_types')->insert([
                'name' => 'categories',
                'slug' => 'categories',
                'title_singular' => 'Категория',
                'title_plural' => 'Категории',
                'model_name' => 'App\\Models\\Category',
                'generate_permissions' => 0,
                'server_side' => 0,
                'enable' => 1,
                'hidden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $update = [];
            if (!$categoriesType->model_name) {
                $update['model_name'] = 'App\\Models\\Category';
            }
            if (!$categoriesType->enable) {
                $update['enable'] = 1;
            }
            if (count($update)) {
                $db->table('data_types')->where('id', $categoriesType->id)->update($update);
            }
        }

        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId) {
            return;
        }

        $exists = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'category_id')
            ->exists();
        if ($exists) {
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'products')
            ->whereNull('module')
            ->orderBy('id')
            ->value('id');
        if (!$sectionId) {
            $sectionId = $db->table('field_sections')->where('page', 'products')->orderBy('id')->value('id');
        }

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
        $db->table('data_rows')->insert([
            'data_type_id' => $typeId,
            'field' => 'category_id',
            'type' => 'relation',
            'title' => 'Категория',
            'required' => 0, 'details' => json_encode(['table' => 'categories']), 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => $maxSort + 1,
            'created_at' => now(), 'updated_at' => now(), 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => 'categories', 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $typeId = DB::table('data_types')->where('slug', 'products')->value('id');
            if ($typeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('field', 'category_id')
                    ->delete();
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
