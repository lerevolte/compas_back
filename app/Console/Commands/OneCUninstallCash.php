<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCCashExportService;
use App\Services\OneC\OneCExportService;
use Illuminate\Console\Command;

class OneCUninstallCash extends Command
{
    protected $signature = 'onec:uninstall-cash {target=avixo : <tenant_id>} {--purge : удалить колонку кодов и состояние выгрузки}';

    protected $description = 'Снять обмен с 1С по кассе: поле «Код 1С» у статей';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            $typeId = $db->table('data_types')->where('slug', OneCCashExportService::ARTICLES)->value('id');
            if ($typeId) {
                $removed = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCCashExportService::CODE_FIELD)->delete();
                $this->line("    [{$tenant->id}] expense_articles: полей снято {$removed}");
            }
            if ($this->option('purge')) {
                if ($sb->hasTable(OneCCashExportService::ARTICLES) && $sb->hasColumn(OneCCashExportService::ARTICLES, OneCCashExportService::CODE_FIELD)) {
                    $sb->table(OneCCashExportService::ARTICLES, fn ($table) => $table->dropColumn(OneCCashExportService::CODE_FIELD));
                }
                if ($sb->hasTable(OneCExportService::STATE_TABLE)) {
                    $db->table(OneCExportService::STATE_TABLE)->whereIn('slug', [OneCCashExportService::ARTICLES, OneCCashExportService::DOCUMENTS])->delete();
                }
                $this->line("    [{$tenant->id}] колонка кодов и состояние выгрузки удалены");
            }
            OneCCashExportService::forget();
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . OneCCashExportService::ARTICLES)->update(['updated_at' => now()]);
                }
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            $this->info("  ✓ {$tenant->id}: выгрузка кассы в 1С снята");
        });

        return self::SUCCESS;
    }
}
