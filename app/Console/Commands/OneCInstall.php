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
        {--disable : выключить обмен}';

    protected $description = 'Обмен с 1С: выгрузка расходных и приходных накладных (номер cmps-, дата, продавец, заказ, склад, строки) по GET-запросу и подтверждение обработки';

    public const NUMBER_TITLE = 'Номер';

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

        $tenant->run(function () use ($tenant, $since) {
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
}
