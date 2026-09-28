<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Saby\SabyOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SabySyncTaskColumn extends Command
{
    protected $signature = 'saby:sync-task-column {target=all-tenants : all-tenants | <tenant_id>}';

    protected $description = 'Пересобрать сводку «Заказы в Саби» (колонка saby_waybills) у задач логистики по таблицам saby_orders и saby_waybills';

    public function handle(): int
    {
        $tenants = $this->argument('target') === 'all-tenants'
            ? Tenant::get()
            : Tenant::where('id', $this->argument('target'))->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    if (!Schema::hasTable('logistic_tasks') || !Schema::hasColumn('logistic_tasks', SabyOrderService::TASK_COLUMN)) {
                        return;
                    }
                    $ids = collect();
                    if (Schema::hasTable('saby_orders')) {
                        $ids = $ids->merge(DB::table('saby_orders')->whereNotNull('task_id')->distinct()->pluck('task_id'));
                    }
                    if (Schema::hasTable('saby_waybills') && Schema::hasColumn('saby_waybills', 'task_id')) {
                        $ids = $ids->merge(DB::table('saby_waybills')->whereNotNull('task_id')->distinct()->pluck('task_id'));
                    }
                    $ids = $ids->map(fn ($v) => (int) $v)->unique()->values();
                    foreach ($ids as $id) {
                        SabyOrderService::syncTaskColumn($id);
                    }
                    $cleared = DB::table('logistic_tasks')
                        ->whereNotNull(SabyOrderService::TASK_COLUMN)
                        ->where(SabyOrderService::TASK_COLUMN, '!=', '')
                        ->whereIntegerNotInRaw('id', $ids->all())
                        ->update([SabyOrderService::TASK_COLUMN => null]);
                    $this->info("  ✓ {$tenant->id}: задач обновлено {$ids->count()}, очищено {$cleared}");
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
