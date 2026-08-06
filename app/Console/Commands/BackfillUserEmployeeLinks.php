<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Достраивает обратную связь user -> employee (8859): у сотрудников,
 * на которых ссылается users.employee_id, заполняет employees.related_user_id,
 * если он пуст. Существующие привязки не перетираются. Команда идемпотентна.
 *   php artisan users:backfill-employee-links avixo
 */
class BackfillUserEmployeeLinks extends Command
{
    protected $signature = 'users:backfill-employee-links
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Заполнить employees.related_user_id по users.employee_id (8859)';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->applyTo(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function applyTo($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasColumn('users', 'employee_id') || !$sb->hasColumn('employees', 'related_user_id')) {
            $this->line("  {$label}: нет колонок employee_id/related_user_id, пропуск");
            return;
        }

        $links = $db->table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('employee_id')
            ->where('employee_id', '!=', 0)
            ->pluck('employee_id', 'id');

        $updated = 0;
        foreach ($links as $userId => $employeeId) {
            $updated += $db->table('employees')
                ->where('id', $employeeId)
                ->whereNull('related_user_id')
                ->update(['related_user_id' => $userId]);
        }
        $this->line("  {$label}: связей у пользователей {$links->count()}, заполнено сотрудников {$updated}");
    }
}
