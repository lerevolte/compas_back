<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Bitrix24\Services\B24EntitySync;

class BackfillB24Entities extends Command
{
    protected $signature = 'b24:backfill-entities
        {target=avixo : all-tenants | <tenant_id>}
        {--entities=companies,contacts : какие сущности выгружать (companies,contacts) — порядок сохраняется}
        {--chunk=500 : записей за один проход}
        {--queue : выполнять чанками в очереди (джоба переставляет себя, пока не закончит)}
        {--reset : начать выгрузку с начала (сбросить курсоры)}
        {--status : только показать состояние курсоров}';

    protected $description = 'Полная выгрузка компаний и контактов из Bitrix24 курсором по ID (возобновляемая)';

    public function handle(): int
    {
        $target = $this->argument('target');
        $chunk = max(1, (int) $this->option('chunk'));
        $entities = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('entities')))));
        $unknown = array_diff($entities, B24EntitySync::BACKFILL_ENTITIES);
        if (count($unknown) || !count($entities)) {
            $this->error('Допустимые сущности: ' . implode(',', B24EntitySync::BACKFILL_ENTITIES));
            return self::FAILURE;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : Tenant::where('id', $target)->get();
        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $entities, $chunk) {
                if (!B24EntitySync::ready()) {
                    $this->line("  – {$tenant->id}: синк Bitrix24 не настроен, пропуск");
                    return;
                }

                if ($this->option('reset')) {
                    foreach ($entities as $entity) {
                        B24EntitySync::resetBackfill($entity);
                    }
                    $this->line("  {$tenant->id}: курсоры сброшены");
                }

                if ($this->option('status')) {
                    foreach ($entities as $entity) {
                        $state = B24EntitySync::backfillState($entity);
                        $this->line("  {$tenant->id} {$entity}: after_id={$state['after_id']} " . ($state['done'] ? "завершено {$state['done']}" : 'в процессе'));
                    }
                    return;
                }

                if ($this->option('queue')) {
                    \Modules\Bitrix24\Jobs\BackfillB24Entities::dispatch((string) $tenant->id, $entities, $chunk);
                    $this->info("  → {$tenant->id}: поставлено в очередь (" . implode(',', $entities) . ", chunk={$chunk})");
                    return;
                }

                $svc = B24EntitySync::make();
                foreach ($entities as $entity) {
                    $total = 0;
                    do {
                        $result = $svc->backfill($entity, $chunk);
                        $total += $result['count'];
                        $this->line("  {$tenant->id} {$entity}: +{$result['count']} (всего {$total}, after_id={$result['after_id']})");
                    } while ($result['more']);
                    $this->info("  ✓ {$tenant->id} {$entity}: {$total}");
                }
            });
        }

        return self::SUCCESS;
    }
}
