<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeCompanies extends Command
{
    protected $signature = 'companies:merge
        {target : tenant id портала}
        {from : id компании-дубля (будет удалена)}
        {to : id компании, в которую переносим связи}
        {--dry-run : показать план без изменений}';

    protected $description = 'Склеить две компании: перенести все связи и пустые поля с дубля на основную, дубль удалить в корзину';

    private const JSON_LIST_COLUMNS = ['contact_id', 'deal_id', 'employee_id', 'car_id', 'fine_id', 'bank_requisite_id'];
    private const SKIP_FILL_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at', 'choosed_at', 'name', 'color', 'user_id'];

    private bool $dry = false;

    public function handle(): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }

        $from = (int) $this->argument('from');
        $to = (int) $this->argument('to');
        if ($from <= 0 || $to <= 0 || $from === $to) {
            $this->error('Укажите разные id компаний from и to');
            return self::FAILURE;
        }

        $this->dry = (bool) $this->option('dry-run');
        $code = self::SUCCESS;

        $tenant->run(function () use ($from, $to, &$code) {
            $source = DB::table('companies')->where('id', $from)->first();
            $target = DB::table('companies')->where('id', $to)->whereNull('deleted_at')->first();
            if (!$source || !$target) {
                $this->error('Компания не найдена: ' . (!$source ? $from : $to));
                $code = self::FAILURE;
                return;
            }

            $this->info(($this->dry ? '[dry-run] ' : '') . "«{$source->name}» (#{$from}) → «{$target->name}» (#{$to})");

            $this->relinkRelationFields($from, $to);
            $this->relinkPlainColumns($from, $to);
            $this->mergeOwnLists($source, $target);
            $this->fillEmptyFields($source, $target);
            $this->relinkObjectRelations($from, $to);
            $this->relinkHistories($from, $to);
            $this->deleteSource($from);

            $this->info($this->dry ? 'План показан, изменений нет' : 'Готово');
        });

        return $code;
    }

    private function relinkRelationFields(int $from, int $to): void
    {
        $rows = DB::table('data_rows')
            ->join('data_types', 'data_types.id', '=', 'data_rows.data_type_id')
            ->where('data_rows.relation_table', 'companies')
            ->where('data_rows.is_remove', 0)
            ->get(['data_types.slug', 'data_rows.field', 'data_rows.is_plural']);

        foreach ($rows as $row) {
            $this->relinkColumn($row->slug, $row->field, $from, $to);
        }
    }

    private function relinkPlainColumns(int $from, int $to): void
    {
        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('COLUMN_NAME', 'LIKE', '%company_id')
            ->where('TABLE_NAME', '!=', 'companies')
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        foreach ($columns as $column) {
            $this->relinkColumn($column->TABLE_NAME, $column->COLUMN_NAME, $from, $to);
        }
    }

    private array $done = [];

    private function relinkColumn(string $table, string $column, int $from, int $to): void
    {
        $key = "{$table}.{$column}";
        if (isset($this->done[$key]) || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }
        $this->done[$key] = true;

        try {
            $rows = DB::table($table)->where($column, 'LIKE', "%{$from}%")->get(['id', $column]);
        } catch (\Illuminate\Database\QueryException $e) {
            $count = DB::table($table)->where($column, $from)->count();
            if ($count) {
                $this->line("  {$key}: {$count}");
                if (!$this->dry) {
                    DB::table($table)->where($column, $from)->update([$column => $to]);
                }
            }
            return;
        }
        $updated = 0;
        foreach ($rows as $row) {
            $value = $this->replaceId($row->{$column}, $from, $to);
            if ($value === null) {
                continue;
            }
            $updated++;
            if ($this->dry) {
                continue;
            }
            try {
                DB::table($table)->where('id', $row->id)->update([$column => $value]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::table($table)->where('id', $row->id)->delete();
            }
        }

        if ($updated) {
            $this->line("  {$key}: {$updated}");
        }
    }

    private function replaceId($raw, int $from, int $to)
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (int) $raw === $from ? $to : null;
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return null;
        }
        if (!in_array($from, array_map('intval', $decoded), true)) {
            return null;
        }
        $list = [];
        foreach ($decoded as $id) {
            $id = (int) $id === $from ? $to : (int) $id;
            if ($id && !in_array($id, $list, true)) {
                $list[] = $id;
            }
        }

        return json_encode($list);
    }

    private function mergeOwnLists(object $source, object $target): void
    {
        $patch = [];
        foreach (self::JSON_LIST_COLUMNS as $column) {
            if (!property_exists($source, $column)) {
                continue;
            }
            $sourceList = $this->idList($source->{$column});
            if (!count($sourceList)) {
                continue;
            }
            $merged = array_values(array_unique(array_merge($this->idList($target->{$column}), $sourceList)));
            if ($merged !== $this->idList($target->{$column})) {
                $patch[$column] = json_encode($merged);
            }
        }

        if (count($patch)) {
            $this->line('  companies.' . implode(', companies.', array_keys($patch)) . ': объединены списки');
            if (!$this->dry) {
                DB::table('companies')->where('id', $target->id)->update($patch);
            }
        }
    }

    private function idList($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return is_numeric($raw) ? [(int) $raw] : [];
        }

        return array_values(array_filter(array_map('intval', $decoded)));
    }

    private function fillEmptyFields(object $source, object $target): void
    {
        $patch = [];
        foreach ((array) $source as $column => $value) {
            if (in_array($column, self::SKIP_FILL_COLUMNS, true) || in_array($column, self::JSON_LIST_COLUMNS, true)) {
                continue;
            }
            if ($this->isEmpty($value) || !$this->isEmpty($target->{$column} ?? null)) {
                continue;
            }
            $patch[$column] = $value;
        }

        if (count($patch)) {
            $this->line('  companies: заполнены пустые поля ' . implode(', ', array_keys($patch)));
            if (!$this->dry) {
                DB::table('companies')->where('id', $source->id)->update(array_fill_keys(array_keys($patch), null));
                DB::table('companies')->where('id', $target->id)->update($patch);
            }
        }
    }

    private function isEmpty($value): bool
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return true;
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && !count(array_filter($decoded, fn ($v) => $v !== null && $v !== '' && $v !== 0));
    }

    private function relinkObjectRelations(int $from, int $to): void
    {
        if (!Schema::hasTable('object_relations')) {
            return;
        }
        foreach (['source', 'target'] as $side) {
            $count = DB::table('object_relations')->where("{$side}_slug", 'companies')->where("{$side}_id", $from)->count();
            if (!$count) {
                continue;
            }
            $this->line("  object_relations.{$side}: {$count}");
            if ($this->dry) {
                continue;
            }
            $rows = DB::table('object_relations')->where("{$side}_slug", 'companies')->where("{$side}_id", $from)->get();
            foreach ($rows as $row) {
                try {
                    DB::table('object_relations')->where('id', $row->id)->update(["{$side}_id" => $to]);
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::table('object_relations')->where('id', $row->id)->delete();
                }
            }
        }
    }

    private function relinkHistories(int $from, int $to): void
    {
        if (!Schema::hasTable('histories')) {
            return;
        }
        $own = DB::table('histories')->where('entity', 'companies')->where('entity_id', $from)->count();
        $needle = "data-slug='companies' data-id='{$from}'";
        $mentions = DB::table('histories')->where('text', 'LIKE', "%{$needle}%")->count();
        if ($own || $mentions) {
            $this->line("  histories: своих {$own}, упоминаний {$mentions}");
        }
        if ($this->dry) {
            return;
        }
        if ($own) {
            DB::table('histories')->where('entity', 'companies')->where('entity_id', $from)->update(['entity_id' => $to]);
        }
        if ($mentions) {
            DB::table('histories')->where('text', 'LIKE', "%{$needle}%")->update([
                'text' => DB::raw("REPLACE(text, '{$needle}', 'data-slug=''companies'' data-id=''{$to}''')"),
            ]);
        }
    }

    private function deleteSource(int $from): void
    {
        $this->line("  companies #{$from}: в корзину");
        if ($this->dry) {
            return;
        }
        $patch = ['deleted_at' => now()];
        if (Schema::hasColumn('companies', 'b24_id')) {
            $patch['b24_id'] = null;
        }
        DB::table('companies')->where('id', $from)->update($patch);
    }

    private function resolveTenant(string $target): ?Tenant
    {
        $tenant = Tenant::find($target);
        if ($tenant) {
            return $tenant;
        }
        $prefix = (string) config('tenancy.database.prefix', '');
        if ($prefix !== '' && str_starts_with($target, $prefix)) {
            return Tenant::find(substr($target, strlen($prefix)));
        }

        return null;
    }
}
