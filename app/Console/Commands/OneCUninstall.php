<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCExportService;
use Illuminate\Console\Command;

class OneCUninstall extends Command
{
    protected $signature = 'onec:uninstall
        {target=avixo : <tenant_id>}
        {--purge : удалить также номера накладных и журнал обмена}';

    protected $description = 'Снять обмен с 1С: настройки и поле «Номер» у накладных (номера и журнал сохраняются, --purge удаляет и их)';

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
            foreach (array_keys(OneCExportService::DOCUMENTS) as $slug) {
                $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
                if ($typeId) {
                    $ids = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCExportService::NUMBER_FIELD)->pluck('id');
                    if ($ids->count()) {
                        if ($sb->hasTable('section_fields_sort')) {
                            $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                        }
                        $db->table('data_rows')->whereIn('id', $ids)->delete();
                    }
                }
                if ($this->option('purge') && $sb->hasTable($slug) && $sb->hasColumn($slug, OneCExportService::NUMBER_FIELD)) {
                    $db->statement('ALTER TABLE `' . $slug . '` DROP COLUMN `' . OneCExportService::NUMBER_FIELD . '`');
                }
                try {
                    if ($sb->hasTable('local_cache')) {
                        $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                }
            }
            if ($sb->hasTable(OneCExportService::CONFIG_TABLE)) {
                $db->statement('DROP TABLE `' . OneCExportService::CONFIG_TABLE . '`');
            }
            if ($this->option('purge') && $sb->hasTable(OneCExportService::STATE_TABLE)) {
                $db->statement('DROP TABLE `' . OneCExportService::STATE_TABLE . '`');
            }
            OneCExportService::forget();
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
            $this->info("  ✓ {$tenant->id}: обмен с 1С снят" . ($this->option('purge') ? ' вместе с номерами и журналом' : ''));
        });

        return self::SUCCESS;
    }
}
