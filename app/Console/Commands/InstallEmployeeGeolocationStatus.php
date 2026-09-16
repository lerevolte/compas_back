<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\GeolocationStatusService;
use Illuminate\Console\Command;

class InstallEmployeeGeolocationStatus extends Command
{
    protected $signature = 'employees:install-geolocation-status
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--remove : убрать поле из data_rows (колонка и данные сохраняются)}';

    protected $description = 'Установить программное поле «Геолокация» (geolocation_status) у сотрудников: только чтение, статусы Данные передаются (синий) / Данные не передаются (серый), обновляется по геопозиции связанного пользователя';

    public const FIELD = GeolocationStatusService::FIELD;
    public const TITLE = 'Геолокация';
    public const VALUES = [
        ['value' => GeolocationStatusService::OFF, 'color' => '#A8A8A8'],
        ['value' => GeolocationStatusService::ON, 'color' => '#1A73E8'],
    ];

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
                    $this->info("  ✓ {$tenant->id}");
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
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();

        $typeId = $db->table('data_types')->where('slug', 'employees')->value('id');
        if (!$typeId || !$sb->hasTable('employees')) {
            $this->line("    [{$label}] employees: сущности нет, пропуск");
            return;
        }

        if ($this->option('remove')) {
            $deleted = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', self::FIELD)
                ->delete();
            if ($deleted) {
                $this->line("    [{$label}] поле " . self::FIELD . ' убрано из data_rows');
            }
            $this->clearCache($db, $inTenant);
            return;
        }

        if (!$sb->hasColumn('employees', self::FIELD)) {
            $db->statement('ALTER TABLE `employees` ADD COLUMN `' . self::FIELD . '` TEXT NULL');
        }

        $row = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', self::FIELD)
            ->first();

        $attrs = [
            'type' => 'status',
            'title' => self::TITLE,
            'required' => 0,
            'only_read' => 1,
            'is_program' => 1,
            'is_default' => 1,
            'is_permanent' => 1,
            'is_remove' => 0,
            'hide' => 0,
        ];

        if ($row) {
            $db->table('data_rows')->where('id', $row->id)->update($attrs);
            $fieldId = (int) $row->id;
        } else {
            $sectionId = $db->table('field_sections')
                ->where('page', 'employees')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $fieldId = (int) $db->table('data_rows')->insertGetId($attrs + [
                'data_type_id' => $typeId,
                'field' => self::FIELD,
                'visible_always' => 1,
                'section_id' => $sectionId,
                'sort' => $maxSort + 1,
                'is_plural' => 0,
            ]);
            $this->line("    [{$label}] employees: создано поле " . self::FIELD . " (id {$fieldId})");
        }

        foreach (self::VALUES as $sort => $def) {
            $value = $db->table('field_values')
                ->where('field_id', $fieldId)
                ->where('value', $def['value'])
                ->first();
            if ($value) {
                $db->table('field_values')->where('id', $value->id)->update(['sort' => $sort, 'color' => $def['color'], 'is_hidden' => 0]);
            } else {
                $db->table('field_values')->insert([
                    'field_id' => $fieldId,
                    'value' => $def['value'],
                    'color' => $def['color'],
                    'sort' => $sort,
                    'is_hidden' => 0,
                ]);
            }
        }

        $defaultId = $db->table('field_values')
            ->where('field_id', $fieldId)
            ->where('value', self::VALUES[0]['value'])
            ->value('id');
        if ($defaultId) {
            $filled = $db->table('employees')
                ->where(fn ($q) => $q->whereNull(self::FIELD)->orWhere(self::FIELD, ''))
                ->update([self::FIELD => $defaultId]);
            if ($filled) {
                $this->line("    [{$label}] employees: «" . GeolocationStatusService::OFF . "» проставлено {$filled} строкам");
            }
        }

        $this->clearCache($db, $inTenant);

        if ($inTenant) {
            try {
                $refreshed = GeolocationStatusService::refresh();
                $this->line("    [{$label}] статус пересчитан по геопозиции: {$refreshed}");
            } catch (\Throwable $e) {
            }
        }
    }

    private function clearCache($db, bool $inTenant): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/employees')->update(['updated_at' => now()]);
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
