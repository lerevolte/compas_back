<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCExportService;
use App\Services\OneC\OneCProductionService;
use Illuminate\Console\Command;

class OneCInstallProduction extends Command
{
    protected $signature = 'onec:install-production {target=avixo : <tenant_id>} {--since= : дата начала выгрузки}';

    protected $description = 'Включить обмен с 1С для заказов на производство, производств и спецификаций';

    public function handle(): int
    {
        $tenant = Tenant::find((string) $this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $since = $this->option('since');
        if ($since !== null && $since !== '' && !strtotime((string) $since)) {
            $this->error('Некорректная дата в --since');
            return self::FAILURE;
        }
        $result = self::SUCCESS;
        $tenant->run(function () use ($tenant, $since, &$result) {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            if (!$sb->hasTable(OneCExportService::CONFIG_TABLE) || !$db->table(OneCExportService::CONFIG_TABLE)->exists()) {
                $this->error('Обмен с 1С не установлен: сначала выполните onec:install');
                $result = self::FAILURE;
                return;
            }
            OneCExportService::ensureTables($db);
            $config = $db->table(OneCExportService::CONFIG_TABLE)->orderBy('id')->first();
            $bySlug = OneCExportService::slugSince($config);
            $installed = 0;
            foreach (OneCProductionService::TYPES as $slug => $type) {
                $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
                if (!$typeId || !$sb->hasTable($slug)) {
                    continue;
                }
                OneCInstall::installStatusField($db, $sb, $slug, (int) $typeId, fn (string $message) => $this->line("    {$slug}: {$message}"));
                if ($since !== null && $since !== '') {
                    $bySlug[$slug] = date('Y-m-d H:i:s', strtotime((string) $since));
                } elseif (!array_key_exists($slug, $bySlug)) {
                    $bySlug[$slug] = now()->format('Y-m-d H:i:s');
                }
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
                $installed++;
            }
            if (!$installed) {
                $this->error('Производственные сущности на портале не установлены');
                $result = self::FAILURE;
                return;
            }
            $db->table(OneCExportService::CONFIG_TABLE)->where('id', $config->id)->update([
                OneCExportService::SLUG_SINCE_FIELD => json_encode($bySlug), 'updated_at' => now(),
            ]);
            OneCExportService::forget();
            \App\Models\Settings::clear_cache();
            $this->info("  ✓ {$tenant->id}: обмен производственными документами включён");
        });

        return $result;
    }
}
