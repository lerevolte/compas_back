<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallShipmentStatusField extends Command
{
    protected $signature = 'logistic:install-shipment-status
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить программное поле «Отгрузки со склада» (shipment_status) у задач логистики и самовывозов: нормальный код поля, только чтение, статусы Не отгружено/Отгружено частично/Отгружено полностью';

    public const FIELD = 'shipment_status';
    public const TITLE = 'Отгрузки со склада';
    public const ENTITIES = ['logistic_tasks', 'pickups'];
    public const VALUES = [
        ['value' => 'Не отгружено', 'color' => '#A8A8A8'],
        ['value' => 'Отгружено частично', 'color' => '#FF9500'],
        ['value' => 'Отгружено полностью', 'color' => '#34C759'],
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

        foreach (self::ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                $this->line("    [{$label}] {$slug}: сущности нет, пропуск");
                continue;
            }

            $row = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', self::FIELD)
                ->first();
            if (!$row) {
                $row = $db->table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('type', 'status')
                    ->where('title', self::TITLE)
                    ->where('is_remove', 0)
                    ->first();
            }

            if ($row && $row->field !== self::FIELD) {
                if ($sb->hasColumn($slug, $row->field) && !$sb->hasColumn($slug, self::FIELD)) {
                    $db->statement("ALTER TABLE `{$slug}` CHANGE `{$row->field}` `" . self::FIELD . '` TEXT NULL');
                }
                $db->table('data_rows')->where('id', $row->id)->update(['field' => self::FIELD]);
                $this->line("    [{$label}] {$slug}: поле {$row->field} переименовано в " . self::FIELD);
            }

            if (!$sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::FIELD . '` TEXT NULL');
            }

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
                    ->where('page', $slug)
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
                $this->line("    [{$label}] {$slug}: создано поле " . self::FIELD . " (id {$fieldId})");
            }

            foreach (self::VALUES as $sort => $def) {
                $value = $db->table('field_values')
                    ->where('field_id', $fieldId)
                    ->where('value', $def['value'])
                    ->first();
                if ($value) {
                    $db->table('field_values')->where('id', $value->id)->update(['sort' => $sort, 'is_hidden' => 0]);
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
                $filled = $db->table($slug)
                    ->where(fn ($q) => $q->whereNull(self::FIELD)->orWhere(self::FIELD, ''))
                    ->update([self::FIELD => $defaultId]);
                if ($filled) {
                    $this->line("    [{$label}] {$slug}: «Не отгружено» проставлено {$filled} строкам");
                }
            }

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }

            try {
                if (\App\Services\ShipmentService::ready()) {
                    $sources = \App\Models\ObjectRelation::whereIn('source_slug', \App\Services\ShipmentService::SOURCES)
                        ->where('target_slug', \App\Services\ShipmentService::DOCUMENT)
                        ->get(['source_slug', 'source_id'])
                        ->unique(fn ($r) => $r->source_slug . ':' . $r->source_id);
                    foreach ($sources as $source) {
                        \App\Services\ShipmentService::recalcForSource((string) $source->source_slug, (int) $source->source_id);
                    }
                    $this->line("    [{$label}] статус пересчитан по отгрузкам: " . count($sources));
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
