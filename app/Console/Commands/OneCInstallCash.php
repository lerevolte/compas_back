<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCCashExportService;
use App\Services\OneC\OneCExportService;
use Illuminate\Console\Command;

class OneCInstallCash extends Command
{
    protected $signature = 'onec:install-cash {target=avixo : <tenant_id>}';

    protected $description = 'Обмен с 1С по кассе: поле «Код 1С» у статей, выгрузка статей и документов по кассе';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $result = self::SUCCESS;
        $tenant->run(function () use ($tenant, &$result) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            if (!$sb->hasTable(OneCExportService::CONFIG_TABLE) || !$db->table(OneCExportService::CONFIG_TABLE)->exists()) {
                $this->error("  [{$tenant->id}] обмен с 1С не установлен — сначала onec:install");
                $result = self::FAILURE;
                return;
            }
            OneCExportService::ensureTables($db);
            $install = OneCCashExportService::install($db, $sb);
            if (!$install['ok']) {
                $this->error("  [{$tenant->id}] {$install['message']}");
                $result = self::FAILURE;
                return;
            }
            $this->line("    [{$tenant->id}] expense_articles: поле «" . OneCCashExportService::CODE_TITLE . '» ' . ($install['created'] ? 'создано' : 'уже есть'));
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            $this->info("  ✓ {$tenant->id}: выгрузка кассы в 1С включена");
            $this->line("    GET  https://{$tenant->id}.compas.pro/api/1c/articles?token=<токен>");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/articles/confirm?token=<токен>  {\"articles\": [{\"id\": 12, \"code\": \"<код в 1С>\"}]}");
            $this->line("    GET  https://{$tenant->id}.compas.pro/api/1c/cash-documents?token=<токен>");
            $this->line("    POST https://{$tenant->id}.compas.pro/api/1c/cash-documents/confirm?token=<токен>  {\"documents\": [{\"number\": \"cmps-k1\", \"ref\": \"<GUID>\"}]}");
        });

        return $result;
    }
}
