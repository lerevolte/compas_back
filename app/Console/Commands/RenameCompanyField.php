<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class RenameCompanyField extends Command
{
    protected $signature = 'logistic:rename-company-field
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Переименовать поле «Компания» (company_id) у задач логистики и самовывозов в «Компания-получатель»';

    public const OLD_TITLE = 'Компания';
    public const NEW_TITLE = 'Компания-получатель';
    public const ENTITIES = ['logistic_tasks', 'pickups'];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds' || $target === 'all-tenants') {
            $this->rename(\DB::connection('seeds'), 'admin_seeds', false);
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->rename(\DB::connection(), (string) $tenant->id, true));
                    $this->info("  ✓ {$tenant->id}");
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
        $tenant->run(fn () => $this->rename(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function rename($db, string $label, bool $inTenant): void
    {
        $total = 0;
        foreach (self::ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                continue;
            }

            $updated = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'company_id')
                ->where('title', self::OLD_TITLE)
                ->update(['title' => self::NEW_TITLE]);
            $total += $updated;

            if ($updated) {
                try {
                    if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                        $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if ($total && $inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->line("    [{$label}] переименовано строк: {$total}");
    }
}
