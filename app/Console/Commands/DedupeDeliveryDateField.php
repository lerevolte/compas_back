<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class DedupeDeliveryDateField extends Command
{
    protected $signature = 'logistic:dedupe-delivery-date
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--apply : удалить пустые дубли (без флага — только отчёт)}';

    protected $description = 'Найти и убрать дубли поля «Дата доставки» у задач логистики (8904)';

    public function handle(): int
    {
        $target = $this->argument('target');
        $apply = (bool) $this->option('apply');

        if ($target === 'seeds') {
            $this->applyTo(\DB::connection('seeds'), 'admin_seeds', $apply, false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $tenant->id, $apply, true));
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
        $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $target, $apply, true));
        return self::SUCCESS;
    }

    private function applyTo($db, string $label, bool $apply, bool $isTenant): void
    {
        $rows = $db->table('data_rows')
            ->join('data_types', 'data_types.id', '=', 'data_rows.data_type_id')
            ->where('data_types.slug', 'logistic_tasks')
            ->where('data_rows.title', 'like', 'Дата доставки%')
            ->get(['data_rows.id', 'data_rows.field', 'data_rows.title', 'data_rows.hide', 'data_rows.is_remove']);

        $main = $rows->firstWhere('field', 'delivery_date');
        $dupes = $rows->filter(fn ($r) => $r->field !== 'delivery_date');

        if (!$dupes->count()) {
            return;
        }

        if (!$main) {
            $this->warn("{$label}: есть '" . $dupes->pluck('title')->implode("', '") . "', но нет delivery_date — пропуск, разобраться руками");
            return;
        }

        $deleted = false;
        $sb = $db->getSchemaBuilder();

        foreach ($dupes as $dupe) {
            $filled = null;
            if ($sb->hasColumn('logistic_tasks', $dupe->field)) {
                $filled = $db->table('logistic_tasks')
                    ->whereNotNull($dupe->field)
                    ->where($dupe->field, '!=', '')
                    ->where($dupe->field, '!=', 'null')
                    ->count();
            }

            $filledInfo = $filled === null ? 'колонки нет' : "заполнено: {$filled}";
            $this->line("{$label}: дубль «{$dupe->title}» (field={$dupe->field}, data_row id={$dupe->id}, {$filledInfo})");

            if (!$apply) {
                continue;
            }

            if ($filled !== null && $filled > 0) {
                $this->warn("{$label}:   пропуск — в поле есть данные ({$filled} задач), удалять руками после проверки");
                continue;
            }

            $db->table('data_rows')->where('id', $dupe->id)->delete();
            $deleted = true;
            $this->info("{$label}:   удалено");
        }

        if ($apply && $deleted && $isTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
