<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallCarBodyTypeField extends Command
{
    protected $signature = 'cars:install-body-type
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Добавить поле «Кузов» (select_dropdown) в сущность cars';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $this->install(\DB::connection('seeds'), 'admin_seeds');
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target));

        return self::SUCCESS;
    }

    private function install($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $dry = (bool) $this->option('dry-run');

        $dataType = $db->table('data_types')->where('slug', 'cars')->first();
        if (!$dataType || !$sb->hasTable('cars')) {
            $this->warn("    [{$label}] сущность cars не найдена, пропуск");
            return;
        }

        $details = json_encode(['options' => InstallSabyModule::BODY_TYPES], JSON_UNESCAPED_UNICODE);
        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', 'body_type')
            ->first();

        if ($existing) {
            $this->line("    [{$label}] поле уже есть (id {$existing->id})" . ($dry ? '' : ', обновлены тип/варианты/обязательность'));
            if (!$dry) {
                if (!$sb->hasColumn('cars', 'body_type')) {
                    $db->statement('ALTER TABLE `cars` ADD COLUMN `body_type` TEXT NULL');
                }
                $db->table('data_rows')->where('id', $existing->id)->update([
                    'type' => 'select_dropdown',
                    'title' => 'Кузов',
                    'required' => 1,
                    'details' => $details,
                    'is_remove' => 0,
                ]);
                $this->clearCache();
            }
            return;
        }

        if ($dry) {
            $this->line("    [{$label}] будет создано поле cars.body_type");
            return;
        }

        if (!$sb->hasColumn('cars', 'body_type')) {
            $db->statement('ALTER TABLE `cars` ADD COLUMN `body_type` TEXT NULL');
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'cars')
            ->whereNull('module')
            ->orderBy('sort')
            ->value('id');
        $afterSort = (int) $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', 'vehicle_type')
            ->value('sort');
        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $dataType->id)->max('sort');

        $id = $db->table('data_rows')->insertGetId([
            'data_type_id' => $dataType->id, 'field' => 'body_type', 'type' => 'select_dropdown', 'title' => 'Кузов',
            'required' => 1, 'details' => $details, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => $afterSort > 0 ? $afterSort + 1 : $maxSort + 1,
            'created_at' => null, 'updated_at' => null, 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ]);
        $this->line("    [{$label}] создано поле cars.body_type (id {$id})");
        $this->clearCache();
    }

    private function clearCache(): void
    {
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
