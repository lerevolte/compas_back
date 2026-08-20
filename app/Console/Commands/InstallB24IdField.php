<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallB24IdField extends Command
{
    protected $signature = 'b24:install-id-field
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--entities=deals,logistic_tasks : слаги сущностей через запятую}
        {--remove : убрать поле из data_rows (колонка и данные сохраняются)}
        {--rename-deals : проставить сделкам название = b24_id}';

    protected $description = 'Поле «ID Bitrix24» (b24_id, скрытое от пользователей): системная строка в data_rows + колонка и бэкфилл из crm_link';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target, true));

        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $remove = (bool) $this->option('remove');
        $entities = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('entities')))));
        $changed = false;

        foreach ($entities as $slug) {
            $type = $db->table('data_types')->where('slug', $slug)->first();
            if (!$type || !$sb->hasTable($slug)) {
                $this->line("  [{$label}] {$slug}: сущности нет — пропуск");
                continue;
            }

            if ($remove) {
                $deleted = $db->table('data_rows')
                    ->where('data_type_id', $type->id)
                    ->where('field', 'b24_id')
                    ->delete();
                if ($deleted) {
                    $changed = true;
                    $this->bumpFieldsCache($db, $slug);
                    $this->line("  [{$label}] {$slug}: поле b24_id убрано из data_rows");
                }
                continue;
            }

            if (!$sb->hasColumn($slug, 'b24_id')) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `b24_id` VARCHAR(32) NULL");
            }
            $indexes = collect($db->select("SHOW INDEX FROM `{$slug}`"))->pluck('Key_name');
            if (!$indexes->contains("{$slug}_b24_id_index")) {
                $db->statement("ALTER TABLE `{$slug}` ADD INDEX `{$slug}_b24_id_index` (`b24_id`)");
            }

            $sectionIds = $db->table('field_sections')->where('page', $slug)->pluck('id');
            $firstSectionId = (int) ($db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')->orderBy('id')
                ->value('id') ?: 0);

            $row = $db->table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('field', 'b24_id')
                ->first();

            if ($row) {
                $update = [
                    'hide' => 1,
                    'only_read' => 1,
                    'is_permanent' => 1,
                    'is_default' => 0,
                ];
                if (!$sectionIds->contains((int) $row->section_id)) {
                    $update['section_id'] = $firstSectionId;
                }
                $db->table('data_rows')->where('id', $row->id)->update($update);
                $this->line("  [{$label}] {$slug}: строка b24_id обновлена (hide=1, is_permanent=1)");
            } else {
                $maxSort = (int) $db->table('data_rows')
                    ->where('data_type_id', $type->id)
                    ->where('section_id', $firstSectionId)
                    ->max('sort');
                $db->table('data_rows')->insert([
                    'data_type_id' => $type->id, 'field' => 'b24_id', 'type' => 'text',
                    'title' => 'ID Bitrix24', 'required' => 0, 'details' => null,
                    'visible_always' => 0, 'label_color' => '', 'section_id' => $firstSectionId,
                    'group_id' => null, 'sort' => $maxSort + 1, 'button_name' => 'Загрузить',
                    'show_file_image' => 0, 'hide' => 1, 'is_plural' => 0, 'roles_read' => '',
                    'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '',
                    'display_parent_name' => null, 'rules' => null, 'only_read' => 1,
                    'is_permanent' => 1, 'show_file_name' => 0, 'external_link' => '',
                    'is_external_link' => 0, 'module' => '', 'is_link' => 0, 'unit' => '',
                    'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                    'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0,
                    'permanent_name' => 0, 'relation_table' => null, 'options' => null,
                    'set_color' => 0, 'related_field' => null, 'is_unique' => 0,
                    'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
                ]);
                $this->line("  [{$label}] {$slug}: добавлено поле b24_id (section={$firstSectionId})");
            }

            $changed = true;

            if ($sb->hasColumn($slug, 'crm_link')) {
                $filled = $db->table($slug)
                    ->where(fn ($q) => $q->whereNull('b24_id')->orWhere('b24_id', ''))
                    ->whereNotNull('crm_link')
                    ->where('crm_link', '!=', '')
                    ->where('crm_link', 'REGEXP', '/deal/details/[0-9]+')
                    ->update([
                        'b24_id' => $db->raw("REGEXP_SUBSTR(`crm_link`, '(?<=/deal/details/)[0-9]+')"),
                    ]);
                if ($filled) {
                    $this->line("  [{$label}] {$slug}: b24_id заполнен из crm_link у {$filled} записей");
                }
            }

            if ($slug === 'deals' && $this->option('rename-deals')) {
                $renamedJson = $db->table('deals')
                    ->whereNotNull('b24_id')->where('b24_id', '!=', '')
                    ->whereRaw('JSON_VALID(`name`)')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`name`, '$.value')) != `b24_id`")
                    ->update(['name' => $db->raw("JSON_SET(`name`, '$.value', `b24_id`)")]);
                $renamedPlain = $db->table('deals')
                    ->whereNotNull('b24_id')->where('b24_id', '!=', '')
                    ->whereRaw('NOT JSON_VALID(`name`)')
                    ->whereColumn('name', '!=', 'b24_id')
                    ->update(['name' => $db->raw('`b24_id`')]);
                $renamed = (int) $renamedJson + (int) $renamedPlain;
                if ($renamed) {
                    $this->line("  [{$label}] deals: название заменено на b24_id у {$renamed} записей");
                }
            }

            if ($inTenant && $sb->hasTable('object_relations')) {
                $relFilled = 0;
                foreach (\App\Models\ObjectRelation::where('target_slug', $slug)->orderBy('id')->get() as $relation) {
                    if (\App\Models\ObjectRelation::copyB24Id(
                        $relation->source_slug,
                        (int) $relation->source_id,
                        $relation->target_slug,
                        (int) $relation->target_id
                    )) {
                        $relFilled++;
                    }
                }
                if ($relFilled) {
                    $this->line("  [{$label}] {$slug}: b24_id заполнен из объектов-источников у {$relFilled} записей");
                }
            }

            $this->bumpFieldsCache($db, $slug);
        }

        if ($changed && $inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->info("  ✓ {$label}");
    }

    private function bumpFieldsCache($db, string $slug): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }
}
