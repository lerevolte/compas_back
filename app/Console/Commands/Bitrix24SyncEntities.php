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
        {--full : полная выгрузка (без фильтра по дате изменения)}
        {--chunk=200 : максимум записей на поток (deals/contacts) за прогон}
        {--queue : поставить обработку в очередь вместо выполнения в процессе}';

    protected $description = 'Синхронизировать сделки/контакты/компании с Bitrix24';

    public function handle(): int
    {
        $target = $this->argument('target');
        $chunk = max(1, (int) $this->option('chunk'));

        $tenants = $target === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $target)->get();

        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant, $chunk) {
                    if (!B24EntitySync::ready() && !\Modules\Bitrix24\Services\B24ProductSync::ready()) {
                        return;
                    }

                    if ($this->option('queue') && !$this->option('full')) {
                        \Modules\Bitrix24\Jobs\SyncTenantEntities::dispatch((string) $tenant->id, $chunk);
                        $this->info("  → {$tenant->id}: поставлено в очередь (chunk={$chunk})");
                        return;
                    }

                    $svc = B24EntitySync::ready() ? B24EntitySync::make() : null;

                    if ($svc && $this->option('full')) {
                        $result = $svc->fullSync(null);
                        $startedAt = now()->format('Y-m-d\TH:i:sP');
                        foreach (['b24_entities_synced_at', 'b24_deals_synced_at', 'b24_contacts_synced_at'] as $type) {
                            \DB::table('settings')->updateOrInsert(
                                ['type' => $type, 'entity' => null, 'user_id' => null],
                                ['key' => $type, 'value' => $startedAt]
                            );
                        }
                        $this->info("  ✓ {$tenant->id} (full): deals={$result['deals']}, contacts={$result['contacts']}, stages={$result['stages']}");
                    } elseif ($svc) {
                        $result = $svc->runIncremental($chunk);
                        if ($result['init']) {
                            $this->info("  ✓ {$tenant->id} (init): stages={$result['stages']}; сделки/контакты дальше пойдут инкрементально чанками по {$chunk}, полная выгрузка — только с --full");
                        } else {
                            $more = $result['more'] ? ', есть ещё (доберёт следующий прогон)' : '';
                            $this->info("  ✓ {$tenant->id}: deals={$result['deals']}, contacts={$result['contacts']}{$more}");
                        }
                    }

                    $productSvc = \Modules\Bitrix24\Services\B24ProductSync::make();
                    if ($productSvc) {
                        if ($this->option('full')) {
                            $categories = $productSvc->pullCategories();
                            $products = $productSvc->pullProducts(null);
                            \DB::table('settings')->updateOrInsert(
                                ['type' => 'b24_products_synced_at', 'entity' => null, 'user_id' => null],
                                ['key' => 'b24_products_synced_at', 'value' => now()->format('Y-m-d\TH:i:sP')]
                            );
                            $this->info("  ✓ {$tenant->id} (full): categories={$categories}, products={$products['count']}");
                        } else {
                            $result = $productSvc->runIncremental($chunk);
                            $more = $result['more'] ? ', есть ещё' : '';
                            $this->info("  ✓ {$tenant->id}: categories={$result['categories']}, products={$result['products']}{$more}");
                        }
                    }
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
