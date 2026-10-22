<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallEmployeeField extends Command
{
    protected $signature = 'entity:install-employee-field
        {entity : slug сущности}
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Добавить сущности множественное поле «Сотрудник» (как у задач логистики) — для QR-привязки сотрудников';

    public const FIELD = 'employee_id';

    private string $slug = '';

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $this->slug = (string) $this->argument('entity');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', $this->slug)->value('id');
        $srcTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        if (!$typeId || !$sb->hasTable($this->slug)) {
            $this->line("  [{$label}] нет сущности {$this->slug}, пропуск");
            return;
        }

        if (!$sb->hasColumn($this->slug, self::FIELD)) {
            $db->statement('ALTER TABLE `' . $this->slug . '` ADD COLUMN `' . self::FIELD . '` TEXT NULL');
        }

        $attrs = [
            'type' => 'relation',
            'title' => 'Сотрудник',
            'is_plural' => 1,
            'details' => '{"table":"employees"}',
            'relation_table' => 'employees',
            'related_field' => null,
            'is_link' => 1,
            'is_remove' => 0,
            'hide' => 0,
            'visible_always' => 1,
        ];

        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->first();
        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            $this->line("  [{$label}] поле уже было (id {$existing->id}), атрибуты обновлены");
        } else {
            $src = $srcTypeId ? $db->table('data_rows')->where('data_type_id', $srcTypeId)->where('field', self::FIELD)->first() : null;
            $row = $src ? (array) $src : [];
            unset($row['id']);
            $sectionId = $db->table('field_sections')
                ->where('page', $this->slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $row = array_merge($row, $attrs, [
                'data_type_id' => $typeId,
                'field' => self::FIELD,
                'section_id' => $sectionId,
                'group_id' => null,
                'module' => '',
                'module_section_id' => null,
                'required' => 0,
                'sort' => ((int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort')) + 1,
            ]);
            $columns = $sb->getColumnListing('data_rows');
            $row = array_intersect_key($row, array_flip($columns));
            $id = $db->table('data_rows')->insertGetId($row);
            $this->line("  [{$label}] добавлено поле «Сотрудник» (id {$id})");
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $this->slug)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
