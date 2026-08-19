<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class InstallRelationsTab extends Command
{
    protected $signature = 'relations:install-tab
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--entities=deals,addresses,logistic_tasks : слаги сущностей через запятую}
        {--remove : убрать вкладку вместо установки}';

    protected $description = 'Добавить вкладку «Связанные документы» в карточку сущностей';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->apply(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $tenant->run(fn () => $this->apply(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function apply(ConnectionInterface $db, string $label): void
    {
        $entities = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('entities')))));
        $remove = (bool) $this->option('remove');

        foreach ($entities as $entity) {
            if (!$db->table('data_types')->where('slug', $entity)->exists()) {
                continue;
            }

            $changed = \App\Models\ObjectRelation::ensureTab($entity, $db, $remove);
            $this->line("    [{$label}] {$entity}: вкладка " . ($remove ? 'убрана' : 'добавлена') . ($changed ? '' : ' (без изменений)'));
        }
    }
}
