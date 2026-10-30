<?php

namespace App\Console\Commands;

use App\Models\Settings;
use App\Models\Tenant;
use Illuminate\Console\Command;

class LockWeightFields extends Command
{
    protected $signature = 'fields:lock-weight {target=all : seeds | all-tenants | all | <tenant_id>}';

    protected $description = 'Запретить редактирование поля «Вес» во всех сущностях, кроме портала zetaprint';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'zetaprint' || $target === 'admin_zetaprint') {
            $this->info('zetaprint пропущен');
            return self::SUCCESS;
        }

        if ($target === 'seeds' || $target === 'all') {
            $count = $this->applyTo(\DB::connection('seeds'));
            Settings::clear_cache();
            $this->info("seeds: заблокировано {$count} полей");
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }

        if ($target === 'all-tenants' || $target === 'all') {
            foreach (Tenant::get() as $tenant) {
                if ((string) $tenant->id === 'zetaprint') {
                    $this->info('  zetaprint: пропущен');
                    continue;
                }
                try {
                    $tenant->run(function () use ($tenant) {
                        $count = $this->applyTo(\DB::connection());
                        Settings::clear_cache();
                        $this->info("  {$tenant->id}: заблокировано {$count} полей");
                    });
                } catch (\Throwable $e) {
                    $this->error("  {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        if ((string) $tenant->id === 'zetaprint') {
            $this->info('zetaprint пропущен');
            return self::SUCCESS;
        }

        $tenant->run(function () use ($tenant) {
            $count = $this->applyTo(\DB::connection());
            Settings::clear_cache();
            $this->info("{$tenant->id}: заблокировано {$count} полей");
        });

        return self::SUCCESS;
    }

    private function applyTo($db): int
    {
        $schema = $db->getSchemaBuilder();
        $count = 0;
        foreach ($db->table('data_types')->get(['id', 'slug']) as $type) {
            $slug = (string) $type->slug;
            if (!$schema->hasTable($slug) || !$schema->hasColumn($slug, 'weight')) {
                continue;
            }
            $count += $db->table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('field', 'weight')
                ->update(['only_read' => 1]);
            if ($schema->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
        }

        return $count;
    }
}
