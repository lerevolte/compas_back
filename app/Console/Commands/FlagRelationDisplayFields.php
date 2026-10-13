<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class FlagRelationDisplayFields extends Command
{
    protected $signature = 'relations:flag-display-fields
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--fields=name,id,delivery_date,products : поля через запятую}
        {--statuses=1 : помечать все поля типа status}';

    protected $description = 'Проставить полям настройку «Выводить в связанных документах» (name/id/delivery_date/products + статусы), не трогая уже настроенные';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $count = $this->applyTo(\DB::connection('seeds'));
            $this->info("seeds: помечено {$count}");

            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        $count = $this->applyTo(\DB::connection());
                        \App\Models\Settings::clear_cache();
                        $this->info("  ✓ {$tenant->id}: {$count}");
                    });
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
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");

            return self::FAILURE;
        }
        $tenant->run(function () use ($target) {
            $count = $this->applyTo(\DB::connection());
            \App\Models\Settings::clear_cache();
            $this->info("Готово: {$target}, помечено {$count}");
        });

        return self::SUCCESS;
    }

    private function applyTo($db): int
    {
        $names = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('fields')))));
        $withStatuses = filter_var($this->option('statuses'), FILTER_VALIDATE_BOOLEAN);
        $query = $db->table('data_rows')->where(function ($q) use ($names, $withStatuses) {
            $q->whereIn('field', $names);
            if ($withStatuses) {
                $q->orWhere('type', 'status');
            }
        });
        $count = 0;
        foreach ($query->get(['id', 'details']) as $row) {
            $details = json_decode((string) $row->details, true);
            $details = is_array($details) ? $details : [];
            if (array_key_exists('show_in_relations', $details)) {
                continue;
            }
            $details['show_in_relations'] = 1;
            $db->table('data_rows')->where('id', $row->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
            $count++;
        }

        return $count;
    }
}
