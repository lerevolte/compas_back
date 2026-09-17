<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallActionTypeField extends Command
{
    protected $signature = 'logistic:install-action-type
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить поле-статус «Тип действия» (action_type) у задач логистики (Выгрузка по умолчанию / Загрузка) и зеркальное поле у заказов покупателей, библиотеки задач и быстрых задач с сопоставлением значений';

    public const FIELD = 'action_type';
    public const TITLE = 'Тип действия';
    public const ENTITIES = ['logistic_tasks'];
    public const MIRROR_ENTITIES = ['deals', 'addresses', 'warehouses'];
    public const VALUES = [
        ['value' => 'Выгрузка', 'color' => '#34C759'],
        ['value' => 'Загрузка', 'color' => '#007AFF'],
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
                'required' => 1,
                'only_read' => 0,
                'is_program' => 0,
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

            $details = json_decode((string) ($row->details ?? ($db->table('data_rows')->where('id', $fieldId)->value('details') ?? '')), true);
            $details = is_array($details) ? $details : [];
            $programIds = [];
            foreach (['unloading_value_id', 'loading_value_id'] as $detailsKey) {
                $candidate = $details[$detailsKey] ?? null;
                if (is_numeric($candidate) && $db->table('field_values')->where('field_id', $fieldId)->where('id', (int) $candidate)->exists()) {
                    $programIds[$detailsKey] = (int) $candidate;
                }
            }

            $keyByText = ['Выгрузка' => 'unloading_value_id', 'Загрузка' => 'loading_value_id'];
            foreach (self::VALUES as $def) {
                $detailsKey = $keyByText[$def['value']];
                if (isset($programIds[$detailsKey])) {
                    continue;
                }
                $value = $db->table('field_values')
                    ->where('field_id', $fieldId)
                    ->where('value', $def['value'])
                    ->first();
                if ($value) {
                    $programIds[$detailsKey] = (int) $value->id;
                }
            }

            if (count($programIds) < 2) {
                $taken = array_values($programIds);
                $existing = $db->table('field_values')
                    ->where('field_id', $fieldId)
                    ->whereIntegerNotInRaw('id', $taken)
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn ($v) => (int) $v)
                    ->all();
                foreach (['unloading_value_id', 'loading_value_id'] as $detailsKey) {
                    if (!isset($programIds[$detailsKey]) && count($existing)) {
                        $programIds[$detailsKey] = array_shift($existing);
                        $this->warn("    [{$label}] {$slug}: {$detailsKey} сопоставлен по порядку создания (id {$programIds[$detailsKey]}) — проверьте, что значение соответствует смыслу");
                    }
                }
            }

            foreach (self::VALUES as $sort => $def) {
                $detailsKey = $keyByText[$def['value']];
                if (isset($programIds[$detailsKey])) {
                    $db->table('field_values')->where('id', $programIds[$detailsKey])->update(['is_hidden' => 0]);
                    continue;
                }
                $programIds[$detailsKey] = (int) $db->table('field_values')->insertGetId([
                    'field_id' => $fieldId,
                    'value' => $def['value'],
                    'color' => $def['color'],
                    'sort' => $sort,
                    'is_hidden' => 0,
                ]);
            }

            $details['unloading_value_id'] = $programIds['unloading_value_id'];
            $details['loading_value_id'] = $programIds['loading_value_id'];
            $db->table('data_rows')->where('id', $fieldId)->update([
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ]);
            $this->line("    [{$label}] {$slug}: программные значения закреплены — выгрузка id {$programIds['unloading_value_id']}, загрузка id {$programIds['loading_value_id']}");

            $defaultId = $programIds['unloading_value_id'];
            if ($defaultId) {
                $filled = $db->table($slug)
                    ->where(fn ($q) => $q->whereNull(self::FIELD)->orWhere(self::FIELD, ''))
                    ->update([self::FIELD => $defaultId]);
                if ($filled) {
                    $this->line("    [{$label}] {$slug}: значение выгрузки проставлено {$filled} строкам");
                }
            }

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        $this->installMirrors($db, $label);

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }

    private function installMirrors($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $taskTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
        $taskField = $taskTypeId
            ? $db->table('data_rows')->where('data_type_id', $taskTypeId)->where('field', self::FIELD)->first()
            : null;
        if (!$taskField) {
            $this->line("    [{$label}] у задач логистики нет поля " . self::FIELD . ', зеркала не ставятся');
            return;
        }
        $taskDetails = json_decode((string) $taskField->details, true);
        $taskDetails = is_array($taskDetails) ? $taskDetails : [];
        $taskValues = $db->table('field_values')->where('field_id', $taskField->id)->orderBy('sort')->orderBy('id')->get();
        $unloadingId = (int) ($taskDetails['unloading_value_id'] ?? 0);

        foreach (self::MIRROR_ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                $this->line("    [{$label}] {$slug}: сущности нет, пропуск");
                continue;
            }
            if (!$sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::FIELD . '` TEXT NULL');
            }

            $attrs = [
                'type' => 'status',
                'title' => $taskField->title,
                'required' => 0,
                'only_read' => 0,
                'is_program' => 0,
                'is_default' => 1,
                'is_permanent' => 1,
                'is_remove' => 0,
                'hide' => 0,
            ];
            $row = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->first();
            if ($row) {
                $db->table('data_rows')->where('id', $row->id)->update($attrs);
                $fieldId = (int) $row->id;
                $details = json_decode((string) $row->details, true);
                $details = is_array($details) ? $details : [];
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
                $details = [];
                $this->line("    [{$label}] {$slug}: создано поле " . self::FIELD . " (id {$fieldId})");
            }

            $map = [];
            $existingMap = is_array($details['task_value_map'] ?? null) ? $details['task_value_map'] : [];
            $byTask = [];
            foreach ($existingMap as $ownId => $taskId) {
                if ($db->table('field_values')->where('field_id', $fieldId)->where('id', (int) $ownId)->exists()) {
                    $byTask[(int) $taskId] = (int) $ownId;
                }
            }
            foreach ($taskValues as $taskValue) {
                $ownId = $byTask[(int) $taskValue->id] ?? null;
                if (!$ownId) {
                    $ownId = $db->table('field_values')->where('field_id', $fieldId)->where('value', $taskValue->value)->value('id');
                }
                if ($ownId) {
                    $db->table('field_values')->where('id', $ownId)->update([
                        'value' => $taskValue->value,
                        'color' => $taskValue->color,
                        'sort' => $taskValue->sort,
                        'is_hidden' => $taskValue->is_hidden,
                    ]);
                } else {
                    $ownId = $db->table('field_values')->insertGetId([
                        'field_id' => $fieldId,
                        'value' => $taskValue->value,
                        'color' => $taskValue->color,
                        'sort' => $taskValue->sort,
                        'is_hidden' => $taskValue->is_hidden,
                    ]);
                }
                $map[(string) $ownId] = (int) $taskValue->id;
            }

            $details['task_value_map'] = $map;
            $db->table('data_rows')->where('id', $fieldId)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);

            $defaultOwn = array_search($unloadingId, $map, true);
            if ($defaultOwn !== false) {
                $filled = $db->table($slug)
                    ->where(fn ($q) => $q->whereNull(self::FIELD)->orWhere(self::FIELD, ''))
                    ->update([self::FIELD => (string) $defaultOwn]);
                if ($filled) {
                    $this->line("    [{$label}] {$slug}: значение выгрузки проставлено {$filled} строкам");
                }
            }
            $this->line("    [{$label}] {$slug}: значений сопоставлено с задачами: " . count($map));

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
