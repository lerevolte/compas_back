<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DocumentNumber;
use App\Services\OneC\OneCExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OneCInstall extends Command
{
    protected $signature = 'onec:install
        {target=avixo : <tenant_id>}
        {--token= : секретный токен для запросов 1С (если не задан и токена ещё нет — будет сгенерирован)}
        {--since= : с какой даты создания отдавать накладные, Y-m-d или Y-m-d H:i:s (по умолчанию — с момента установки)}
        {--storehouse=* : внешний идентификатор склада в 1С, <id склада>:<id_1c>, можно несколько раз}
        {--disable : выключить обмен}
        {--statuses-only : только убрать дубли значений «Статус 1С», без перенумерации и настроек обмена}
        {--fields : только поля расходных накладных: «Дата отгрузки» с заполнением и видимый «Номер»}';

    protected $description = 'Обмен с 1С: выгрузка расходных и приходных накладных (номер cmps-, дата, продавец, заказ, склад, строки) по GET-запросу и подтверждение обработки';

    public const NUMBER_TITLE = 'Номер';
    public const STOREHOUSE_1C_TITLE = 'ID 1С';
    public const STATUS_TITLE = OneCExportService::STATUS_TITLE;

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $since = $this->option('since');
        if ($since !== null && $since !== '' && !strtotime((string) $since)) {
            $this->error('Некорректная дата в --since');
            return self::FAILURE;
        }
        $storehouseCodes = [];
        foreach ((array) $this->option('storehouse') as $pair) {
            if (!preg_match('/^\s*(\d+)\s*[:=]\s*(.+?)\s*$/u', (string) $pair, $m)) {
                $this->error("Некорректное значение --storehouse={$pair}, нужно <id склада>:<id_1c>");
                return self::FAILURE;
            }
            $storehouseCodes[(int) $m[1]] = $m[2];
        }

        if ($this->option('fields')) {
            $tenant->run(function () use ($tenant) {
                $db = \DB::connection();
                $this->installShipmentDateField($db, $db->getSchemaBuilder(), (string) $tenant->id);
                try {
                    \App\Models\Settings::clear_cache();
                } catch (\Throwable $e) {
                }
            });

            return self::SUCCESS;
        }

        if ($this->option('statuses-only')) {
            $tenant->run(function () use ($tenant) {
                $db = \DB::connection();
                foreach (array_keys(OneCExportService::DOCUMENTS) as $slug) {
                    $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
                    $fieldId = $typeId ? (int) $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCExportService::STATUS_FIELD)->value('id') : 0;
                    if (!$fieldId) {
                        continue;
                    }
                    $merged = self::mergeDuplicateStatuses($db, $slug, $fieldId);
                    $this->line("    [{$tenant->id}] {$slug}: удалено дублей статуса 1С {$merged}");
                    try {
                        if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                            $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                        }
                    } catch (\Throwable $e) {
                    }
                }
                OneCExportService::forget();
                try {
                    \App\Models\Settings::clear_cache();
                } catch (\Throwable $e) {
                }
            });

            return self::SUCCESS;
        }

        $tenant->run(function () use ($tenant, $since, $storehouseCodes) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            OneCExportService::ensureTables($db);
            DocumentNumber::ensureTable($db);

            foreach (array_keys(OneCExportService::DOCUMENTS) as $slug) {
                $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
                if (!$typeId || !$sb->hasTable($slug)) {
                    $this->warn("    [{$tenant->id}] {$slug}: сущности нет, пропуск");
                    continue;
                }
                if (!$sb->hasColumn($slug, OneCExportService::NUMBER_FIELD)) {
                    $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . OneCExportService::NUMBER_FIELD . '` VARCHAR(64) NULL, ADD INDEX `' . $slug . '_number_index` (`' . OneCExportService::NUMBER_FIELD . '`)');
                }
                OneCExportService::forget();
                if (!$db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCExportService::NUMBER_FIELD)->exists()) {
                    $sectionId = (int) $db->table('field_sections')
                        ->where('page', $slug)
                        ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                        ->orderBy('sort')
                        ->value('id');
                    $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
                    $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow((int) $typeId, $sectionId), [
                        'field' => OneCExportService::NUMBER_FIELD,
                        'type' => 'text',
                        'title' => self::NUMBER_TITLE,
                        'only_read' => 1,
                        'is_program' => 1,
                        'hide' => 0,
                        'sort' => $maxSort + 1,
                    ]));
                }
                $numbered = OneCExportService::renumber($db, $slug);
                $this->line("    [{$tenant->id}] {$slug}: номеров cmps-<id> присвоено {$numbered}");
                self::installStatusField($db, $sb, $slug, (int) $typeId, fn (string $message) => $this->line("    [{$tenant->id}] {$slug}: {$message}"));
                try {
                    if ($sb->hasTable('local_cache')) {
                        $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                }
            }

            $this->installStorehouseField($db, $sb, (string) $tenant->id, $storehouseCodes);
            $this->installShipmentDateField($db, $sb, (string) $tenant->id);

            $row = $db->table(OneCExportService::CONFIG_TABLE)->orderBy('id')->first();
            $attrs = ['updated_at' => now(), 'enabled' => $this->option('disable') ? 0 : 1];
            $token = trim((string) $this->option('token'));
            if ($token !== '') {
                $attrs['token'] = $token;
            } elseif (!$row || trim((string) $row->token) === '') {
                $attrs['token'] = Str::random(40);
            }
            if ($since !== null && $since !== '') {
                $attrs['since'] = date('Y-m-d H:i:s', strtotime((string) $since));
            } elseif (!$row || !$row->since) {
                $attrs['since'] = now();
            }
            if ($row) {
                $db->table(OneCExportService::CONFIG_TABLE)->where('id', $row->id)->update($attrs);
            } else {
                $db->table(OneCExportService::CONFIG_TABLE)->insert($attrs + ['created_at' => now()]);
            }
            $config = $db->table(OneCExportService::CONFIG_TABLE)->orderBy('id')->first();
            $moduleUserId = OneCExportService::moduleUserId();
            $this->line('    пользователь «' . OneCExportService::MODULE_USER_NAME . '»: ' . ($moduleUserId ? '#' . $moduleUserId : 'не создан'));

            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }

            $this->info("  ✓ {$tenant->id}: обмен с 1С " . ($config->enabled ? 'включён' : 'выключен') . ", накладные с {$config->since}");
            $this->line("    токен: {$config->token}");
            $this->line("    GET  https://{$tenant->id}.compas.pro/api/1c/documents?token=<токен>");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/documents/confirm?token=<токен>  {\"documents\": [{\"number\": \"cmps-<id>\", \"type\": \"expense_invoice\", \"ref\": \"<GUID>\", \"status\": \"created|posted|deleted\"}]}");
        });

        return self::SUCCESS;
    }

    public static function installStatusField($db, $sb, string $slug, int $typeId, callable $log): void
    {
        $field = OneCExportService::STATUS_FIELD;
        if (!$sb->hasColumn($slug, $field)) {
            $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . $field . '` TEXT NULL');
        }
        OneCExportService::forget();
        $attrs = [
            'type' => 'status',
            'title' => self::STATUS_TITLE,
            'required' => 0,
            'only_read' => 1,
            'is_program' => 1,
            'is_default' => 1,
            'is_permanent' => 1,
            'is_remove' => 0,
            'hide' => 0,
        ];
        $row = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        if ($row) {
            $db->table('data_rows')->where('id', $row->id)->update($attrs);
            $fieldId = (int) $row->id;
        } else {
            $sectionId = (int) $db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $fieldId = (int) $db->table('data_rows')->insertGetId(array_merge(InstallSaleDocsEntities::baseRow($typeId, $sectionId), $attrs, [
                'field' => $field,
                'sort' => $maxSort + 1,
            ]));
            $log("создано поле {$field} (id {$fieldId})");
        }
        foreach (OneCExportService::STATUS_VALUES as $sort => $def) {
            $value = $db->table('field_values')->where('field_id', $fieldId)->where('value', $def['value'])->orderBy('id')->first();
            $patch = ['sort' => $sort, 'is_hidden' => 0];
            if (!$value && isset($def['legacy'])) {
                $value = $db->table('field_values')->where('field_id', $fieldId)->where('value', $def['legacy'])->orderBy('id')->first();
                $patch += ['value' => $def['value'], 'color' => $def['color']];
            }
            if (!$value) {
                $value = $db->table('field_values')->where('field_id', $fieldId)->where('sort', $sort)->where('is_hidden', '!=', 1)->orderBy('id')->first();
                $patch = ['sort' => $sort, 'is_hidden' => 0];
            }
            if ($value) {
                $db->table('field_values')->where('id', $value->id)->update($patch);
            } else {
                $db->table('field_values')->insert([
                    'field_id' => $fieldId, 'value' => $def['value'], 'color' => $def['color'], 'sort' => $sort, 'is_hidden' => 0,
                ]);
            }
        }
        $merged = self::mergeDuplicateStatuses($db, $slug, $fieldId);
        if ($merged) {
            $log("удалено дублей статуса 1С {$merged}");
        }
        OneCExportService::forget();
        $ids = [];
        foreach (array_keys(OneCExportService::STATUS_VALUES) as $index) {
            $ids[] = (string) OneCExportService::statusValueId($slug, $index);
        }
        $confirmedIds = $db->table(OneCExportService::STATE_TABLE)->where('slug', $slug)->whereNotNull('confirmed_at')->pluck('document_id')->all();
        $confirmed = count($confirmedIds)
            ? $db->table($slug)->whereIn('id', $confirmedIds)->where(fn ($q) => $q->whereNull($field)->orWhere($field, '')->orWhere($field, $ids[OneCExportService::STATUS_NONE]))->update([$field => $ids[OneCExportService::STATUS_CREATED]])
            : 0;
        $filled = $db->table($slug)->where(fn ($q) => $q->whereNull($field)->orWhere($field, ''))->update([$field => $ids[OneCExportService::STATUS_NONE]]);
        $log("статус 1С — создана {$confirmed}, не создана {$filled}");
        OneCExportService::forget();
    }

    private function installShipmentDateField($db, $sb, string $tenantId): void
    {
        $slug = \App\Services\ShipmentService::DOCUMENT;
        $field = OneCExportService::SHIPMENT_DATE_FIELD;
        $typeId = (int) $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId || !$sb->hasTable($slug)) {
            $this->warn("    [{$tenantId}] {$slug}: сущности нет, пропуск");
            return;
        }
        if (!$sb->hasColumn($slug, $field)) {
            $db->statement('ALTER TABLE `' . $slug . '` ADD COLUMN `' . $field . '` DATE NULL');
        }
        OneCExportService::forget();
        if (!$db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->exists()) {
            $sectionId = (int) $db->table('field_sections')
                ->where('page', $slug)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow($typeId, $sectionId), [
                'field' => $field,
                'type' => 'date',
                'title' => OneCExportService::SHIPMENT_DATE_TITLE,
                'only_read' => 1,
                'is_default' => 1,
                'sort' => $maxSort + 1,
            ]));
            $this->line("    [{$tenantId}] {$slug}: создано поле «" . OneCExportService::SHIPMENT_DATE_TITLE . '»');
        }
        $shown = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCExportService::NUMBER_FIELD)->update(['hide' => 0, 'only_read' => 1]);
        $this->line("    [{$tenantId}] {$slug}: поле «" . self::NUMBER_TITLE . '» ' . ($shown ? 'показано, только чтение' : 'не найдено или уже видно'));
        $filled = 0;
        foreach ($db->table($slug)->pluck('id') as $id) {
            if (OneCExportService::refreshShipmentDate($slug, (int) $id)) {
                $filled++;
            }
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        $this->line("    [{$tenantId}] {$slug}: дата отгрузки заполнена у {$filled}");
    }

    public static function mergeDuplicateStatuses($db, string $slug, int $fieldId): int
    {
        $field = OneCExportService::STATUS_FIELD;
        $removed = 0;
        foreach (array_keys(OneCExportService::STATUS_VALUES) as $index) {
            $dupes = $db->table('field_values')
                ->where('field_id', $fieldId)
                ->where('sort', $index)
                ->where('is_hidden', '!=', 1)
                ->orderBy('id')
                ->pluck('id')
                ->all();
            if (count($dupes) < 2) {
                continue;
            }
            $keep = (string) array_shift($dupes);
            $db->table($slug)->whereIn($field, array_map('strval', $dupes))->update([$field => $keep]);
            $db->table('field_values')->whereIn('id', $dupes)->delete();
            $removed += count($dupes);
        }

        return $removed;
    }

    private function installStorehouseField($db, $sb, string $tenantId, array $codes): void
    {
        $field = OneCExportService::STOREHOUSE_1C_FIELD;
        $typeId = $db->table('data_types')->where('slug', 'storehouses')->value('id');
        if (!$typeId || !$sb->hasTable('storehouses')) {
            $this->warn("    [{$tenantId}] storehouses: сущности нет, пропуск");
            return;
        }
        if (!$sb->hasColumn('storehouses', $field)) {
            $db->statement('ALTER TABLE `storehouses` ADD COLUMN `' . $field . '` VARCHAR(191) NULL');
        }
        if (!$db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->exists()) {
            $sectionId = (int) $db->table('field_sections')
                ->where('page', 'storehouses')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow((int) $typeId, $sectionId), [
                'field' => $field,
                'type' => 'text',
                'title' => self::STOREHOUSE_1C_TITLE,
                'sort' => $maxSort + 1,
            ]));
        }
        foreach ($codes as $id => $code) {
            $updated = $db->table('storehouses')->where('id', $id)->update([$field => $code]);
            $this->line("    [{$tenantId}] склад {$id}: " . ($updated ? "id_1c = {$code}" : 'не найден или без изменений'));
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/storehouses')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }
}
