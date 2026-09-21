<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Bitrix24\Services\B24ProductSync;

class MergeDuplicateProducts extends Command
{
    protected $signature = 'products:merge-duplicates
        {target : all-tenants | <tenant_id>}
        {--apply : выполнить слияние (без флага — только отчёт)}';

    protected $description = 'Слить товары-дубли с одинаковым названием: состав документов переводится на самый старый товар, дубли уходят в удалённые';

    public const DOCUMENTS = [
        'deals', 'supplier_orders', 'logistic_tasks', 'pickups', 'payment_invoices',
        'expense_invoices', 'product_returns', 'receipt_invoices', 'addresses', 'warehouses',
    ];
    public const LINK_COLUMNS = ['deal_id', 'supplier_order_id'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->merge(\DB::connection(), (string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function merge($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasTable('products')) {
            return;
        }
        $apply = (bool) $this->option('apply');
        $hasB24 = $sb->hasColumn('products', 'id_b24');
        $columns = array_merge(['id', 'name'], $hasB24 ? ['id_b24'] : []);

        $groups = [];
        foreach ($db->table('products')->whereNull('deleted_at')->orderBy('id')->get($columns) as $product) {
            $key = B24ProductSync::normalizeName($product->name);
            if ($key !== '') {
                $groups[$key][] = $product;
            }
        }

        $merged = 0;
        foreach ($groups as $key => $items) {
            if (count($items) < 2) {
                continue;
            }
            $b24Ids = array_values(array_unique(array_filter(array_map(fn ($p) => (string) ($p->id_b24 ?? ''), $items), fn ($v) => $v !== '')));
            $ids = implode(', ', array_map(fn ($p) => $p->id, $items));
            if (count($b24Ids) > 1) {
                $this->warn("    [{$label}] «{$key}»: товары {$ids} связаны с разными товарами Bitrix24 (" . implode(', ', $b24Ids) . ') — пропуск, нужен ручной разбор');
                continue;
            }
            $keeper = $items[0];
            $dupes = array_slice($items, 1);
            $this->line("    [{$label}] «{$key}»: остаётся #{$keeper->id}, дубли: " . implode(', ', array_map(fn ($p) => '#' . $p->id, $dupes)) . ($apply ? '' : ' (отчёт)'));
            if (!$apply) {
                continue;
            }
            $map = [];
            foreach ($dupes as $dupe) {
                $map[(int) $dupe->id] = (int) $keeper->id;
            }
            $this->rewriteDocuments($db, $map);
            $this->mergeLinks($db, (int) $keeper->id, array_keys($map));
            if ($hasB24 && count($b24Ids) === 1 && (string) ($keeper->id_b24 ?? '') === '') {
                $db->table('products')->whereIn('id', array_keys($map))->update(['id_b24' => null]);
                $db->table('products')->where('id', $keeper->id)->update(['id_b24' => $b24Ids[0]]);
            }
            $db->table('products')->whereIn('id', array_keys($map))->update(['deleted_at' => now()]);
            foreach (array_keys($map) as $dupeId) {
                $db->table('histories')->insert([
                    'entity' => 'products', 'entity_id' => $dupeId, 'user_id' => null,
                    'event' => 'OBJECT_DELETED', 'text' => 'Удалено как дубль товара #' . $keeper->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $merged += count($map);
        }

        $this->info("  ✓ {$label}: " . ($apply ? "слито дублей {$merged}" : 'отчёт сформирован'));
        if ($apply && $merged) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }

    private function rewriteDocuments($db, array $map): void
    {
        $sb = $db->getSchemaBuilder();
        foreach (self::DOCUMENTS as $table) {
            if (!$sb->hasTable($table) || !$sb->hasColumn($table, 'products')) {
                continue;
            }
            $query = $db->table($table)->whereNotNull('products')->where(function ($q) use ($map) {
                foreach (array_keys($map) as $dupeId) {
                    $q->orWhere('products', 'like', '%"id":' . $dupeId . '%')
                        ->orWhere('products', 'like', '%"id":"' . $dupeId . '"%');
                }
            });
            foreach ($query->get(['id', 'products']) as $row) {
                $lines = json_decode((string) $row->products, true);
                if (!is_array($lines)) {
                    continue;
                }
                $changed = false;
                foreach ($lines as $i => $line) {
                    $lineId = is_array($line) && isset($line['id']) && is_numeric($line['id']) ? (int) $line['id'] : null;
                    if ($lineId !== null && isset($map[$lineId])) {
                        $lines[$i]['id'] = $map[$lineId];
                        $changed = true;
                    }
                }
                if ($changed) {
                    $db->table($table)->where('id', $row->id)->update(['products' => json_encode($lines, JSON_UNESCAPED_UNICODE)]);
                }
            }
        }
    }

    private function mergeLinks($db, int $keeperId, array $dupeIds): void
    {
        $sb = $db->getSchemaBuilder();
        foreach (self::LINK_COLUMNS as $column) {
            if (!$sb->hasColumn('products', $column)) {
                continue;
            }
            $all = [];
            foreach ($db->table('products')->whereIn('id', array_merge([$keeperId], $dupeIds))->pluck($column) as $raw) {
                $all = array_merge($all, \App\Services\RelationFieldsService::ids($raw));
            }
            $all = array_values(array_unique($all));
            $db->table('products')->where('id', $keeperId)->update([$column => count($all) ? json_encode($all) : null]);
        }
    }
}
