<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\OneC\OneCExportService;
use App\Services\OneC\OneCProductionService;
use Illuminate\Console\Command;

class OneCUninstallProduction extends Command
{
    protected $signature = 'onec:uninstall-production {target=avixo : <tenant_id>} {--purge : удалить состояние обмена и колонки статуса}';

    protected $description = 'Отключить обмен с 1С для производственных сущностей';

    public function handle(): int
    {
        $tenant = Tenant::find((string) $this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $tenant->run(function () {
            $db = \DB::connection();
            $sb = $db->getSchemaBuilder();
            if ($sb->hasTable(OneCExportService::CONFIG_TABLE) && $sb->hasColumn(OneCExportService::CONFIG_TABLE, OneCExportService::SLUG_SINCE_FIELD)) {
                foreach ($db->table(OneCExportService::CONFIG_TABLE)->get() as $config) {
                    $bySlug = array_diff_key(OneCExportService::slugSince($config), OneCProductionService::TYPES);
                    $db->table(OneCExportService::CONFIG_TABLE)->where('id', $config->id)->update([
                        OneCExportService::SLUG_SINCE_FIELD => json_encode($bySlug), 'updated_at' => now(),
                    ]);
                }
            }
            foreach (OneCProductionService::TYPES as $slug => $type) {
                $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
                if ($typeId) {
                    $fieldId = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', OneCExportService::STATUS_FIELD)->value('id');
                    if ($fieldId) {
                        $db->table('field_values')->where('field_id', $fieldId)->delete();
                        $db->table('section_fields_sort')->where('field_id', $fieldId)->delete();
                        $db->table('data_rows')->where('id', $fieldId)->delete();
                    }
                }
                if ($this->option('purge')) {
                    if ($sb->hasTable($slug) && $sb->hasColumn($slug, OneCExportService::STATUS_FIELD)) {
                        $sb->table($slug, fn ($table) => $table->dropColumn(OneCExportService::STATUS_FIELD));
                    }
                    if ($sb->hasTable(OneCExportService::STATE_TABLE)) {
                        $db->table(OneCExportService::STATE_TABLE)->where('slug', $slug)->delete();
                    }
                }
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
            }
            OneCExportService::forget();
            \App\Models\Settings::clear_cache();
        });
        $this->info('Обмен производственными документами отключён');

        return self::SUCCESS;
    }
}
