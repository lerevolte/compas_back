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
        {--storehouse=* : внешний идентификатор склада в 1С, <id склада>:<1c_id>, можно несколько раз}
        {--disable : выключить обмен}';

    protected $description = 'Обмен с 1С: выгрузка расходных и приходных накладных (номер cmps-, дата, продавец, заказ, склад, строки) по GET-запросу и подтверждение обработки';

    public const NUMBER_TITLE = 'Номер';
    public const STOREHOUSE_1C_TITLE = 'ID 1С';

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
                $this->error("Некорректное значение --storehouse={$pair}, нужно <id склада>:<1c_id>");
                return self::FAILURE;
            }
            $storehouseCodes[(int) $m[1]] = $m[2];
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
                        'hide' => 1,
                        'sort' => $maxSort + 1,
                    ]));
                }
                $numbered = 0;
                $rows = $db->table($slug)
                    ->where(fn ($q) => $q->whereNull(OneCExportService::NUMBER_FIELD)->orWhere(OneCExportService::NUMBER_FIELD, ''))
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id');
                foreach ($rows as $id) {
                    $number = DocumentNumber::next();
                    if (!$number) {
                        break;
                    }
                    $db->table($slug)->where('id', $id)->update([OneCExportService::NUMBER_FIELD => $number]);
                    $numbered++;
                }
                $this->line("    [{$tenant->id}] {$slug}: номеров присвоено {$numbered}");
                try {
                    if ($sb->hasTable('local_cache')) {
                        $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                }
            }

            $this->installStorehouseField($db, $sb, (string) $tenant->id, $storehouseCodes);

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

            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }

            $this->info("  ✓ {$tenant->id}: обмен с 1С " . ($config->enabled ? 'включён' : 'выключен') . ", накладные с {$config->since}");
            $this->line("    токен: {$config->token}");
            $this->line("    GET  https://{$tenant->id}.compas.pro/api/1c/documents?token=<токен>");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/documents/confirm?token=<токен>  {\"numbers\": [\"cmps-…\"]}");
        });

        return self::SUCCESS;
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
            $this->line("    [{$tenantId}] склад {$id}: " . ($updated ? "1c_id = {$code}" : 'не найден или без изменений'));
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/storehouses')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
    }
}
