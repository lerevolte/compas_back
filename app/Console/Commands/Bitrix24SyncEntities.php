<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Modules\Bitrix24\Services\B24EntitySync;

/**
 * Периодическая синхронизация deals/contacts/companies с Bitrix24.
 * Пропускает тенантов без сущностей/конфига (B24EntitySync::ready) —
 * поэтому безопасно гоняется по всем порталам (политика avixo).
 *
 *   php artisan bitrix24:sync-entities avixo --full   # первичная выгрузка
 *   php artisan bitrix24:sync-entities all-tenants    # инкрементально (крон)
 */
class Bitrix24SyncEntities extends Command
{
    protected $signature = 'bitrix24:sync-entities
        {target=all-tenants : all-tenants | <tenant_id>}
        {--full : полная выгрузка (без фильтра по дате изменения)}';

    protected $description = 'Синхронизировать сделки/контакты/компании с Bitrix24';

    public function handle(): int
    {
        $target = $this->argument('target');

        $tenants = $target === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $target)->get();

        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    if (!B24EntitySync::ready()) {
                        return;
                    }
                    $svc = B24EntitySync::make();
                    $startedAt = now()->format('Y-m-d\TH:i:sP');
                    $saveMark = function () use ($startedAt) {
                        \DB::table('settings')->updateOrInsert(
                            ['type' => 'b24_entities_synced_at', 'entity' => null, 'user_id' => null],
                            ['key' => 'b24_entities_synced_at', 'value' => $startedAt]
                        );
                    };

                    if ($this->option('full')) {
                        $result = $svc->fullSync(null);
                        $saveMark();
                        $this->info("  ✓ {$tenant->id} (full): deals={$result['deals']}, contacts={$result['contacts']}, stages={$result['stages']}");
                        return;
                    }

                    $since = \DB::table('settings')
                        ->where('type', 'b24_entities_synced_at')
                        ->value('value');

                    if (!$since) {
                        $stages = $svc->syncStages();
                        $saveMark();
                        $this->info("  ✓ {$tenant->id} (init): stages=" . count($stages) . '; сделки/контакты дальше пойдут инкрементально (вебхук + крон), полная выгрузка — только с --full');
                        return;
                    }

                    $result = $svc->fullSync($since);
                    $saveMark();
                    $this->info("  ✓ {$tenant->id}: deals={$result['deals']}, contacts={$result['contacts']}, stages={$result['stages']}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
