<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeB24DealDuplicates extends Command
{
    protected $signature = 'deals:merge-b24-duplicates
        {target=avixo : all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Слить дубли заказов с одинаковым b24_id: связи и контакты переносятся на самый ранний, остальные удаляются';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->merge((string) $tenant->id));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->merge((string) $target));

        return self::SUCCESS;
    }

    private function merge(string $label): void
    {
        if (!Schema::hasTable('deals') || !Schema::hasColumn('deals', 'b24_id')) {
            return;
        }
        $dry = (bool) $this->option('dry-run');

        $groups = DB::table('deals')
            ->select('b24_id', DB::raw('COUNT(*) AS c'), DB::raw('MIN(id) AS keep_id'))
            ->whereNotNull('b24_id')
            ->where('b24_id', '!=', '')
            ->whereNull('deleted_at')
            ->groupBy('b24_id')
            ->having('c', '>', 1)
            ->get();

        if (!count($groups)) {
            $this->line("    [{$label}] дублей нет");
            return;
        }

        foreach ($groups as $group) {
            $keepId = (int) $group->keep_id;
            $dupIds = DB::table('deals')
                ->where('b24_id', $group->b24_id)
                ->whereNull('deleted_at')
                ->where('id', '!=', $keepId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->line("    [{$label}] b24 #{$group->b24_id}: оставляем {$keepId}, удаляем " . implode(', ', $dupIds));
            if ($dry) {
                continue;
            }

            foreach ($dupIds as $dupId) {
                $this->moveRelations($dupId, $keepId);
                $this->movePivots($dupId, $keepId);
                DB::table('deals')->where('id', $dupId)->update(['deleted_at' => now()]);
            }
        }
    }

    private function moveRelations(int $from, int $to): void
    {
        if (!Schema::hasTable('object_relations')) {
            return;
        }
        foreach (['source', 'target'] as $side) {
            $rows = DB::table('object_relations')
                ->where($side . '_slug', 'deals')
                ->where($side . '_id', $from)
                ->get();
            foreach ($rows as $row) {
                $exists = DB::table('object_relations')
                    ->where('source_slug', $row->source_slug)
                    ->where('source_id', $side === 'source' ? $to : $row->source_id)
                    ->where('target_slug', $row->target_slug)
                    ->where('target_id', $side === 'target' ? $to : $row->target_id)
                    ->exists();
                if ($exists) {
                    DB::table('object_relations')->where('id', $row->id)->delete();
                } else {
                    DB::table('object_relations')->where('id', $row->id)->update([$side . '_id' => $to]);
                }
            }
        }
    }

    private function movePivots(int $from, int $to): void
    {
        foreach (['contact_deal' => 'contact_id', 'company_deal' => 'company_id'] as $table => $column) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $ids = DB::table($table)->where('deal_id', $from)->pluck($column);
            foreach ($ids as $id) {
                DB::table($table)->where('deal_id', $from)->where($column, $id)->delete();
                $exists = DB::table($table)->where('deal_id', $to)->where($column, $id)->exists();
                if (!$exists) {
                    DB::table($table)->insert(['deal_id' => $to, $column => $id]);
                }
            }
        }
    }
}
