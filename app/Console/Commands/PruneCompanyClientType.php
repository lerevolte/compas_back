<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Tenant;
use App\Services\TypeTagService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneCompanyClientType extends Command
{
    protected $signature = 'companies:prune-client-type
        {target=all-tenants : all-tenants | <tenant_id>}
        {--dry-run : только посчитать}';

    protected $description = 'Снять тип «Клиент» у компаний, по которым нет ни одного заказа покупателя';

    public function handle(): int
    {
        $target = $this->argument('target');
        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->prune((string) $tenant->id));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }

    private function prune(string $label): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }
        $row = TypeTagService::field('companies', Company::TYPE_FIELD_TITLE);
        $option = $row ? TypeTagService::optionValue($row, 'Клиент') : null;
        if ($option === null) {
            $this->line("  [{$label}] нет поля «Тип компании» с вариантом «Клиент», пропуск");
            return;
        }

        $withDeals = [];
        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'company_id')) {
            $query = DB::table('deals')->whereNotNull('company_id')->where('company_id', '!=', '');
            if (Schema::hasColumn('deals', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($query->pluck('company_id') as $value) {
                foreach (TypeTagService::ids($value) as $id) {
                    $withDeals[$id] = true;
                }
            }
        }

        $targets = [];
        DB::table('companies')->whereNotNull($row->field)->orderBy('id')->select(['id', $row->field])
            ->chunk(1000, function ($items) use ($row, $option, $withDeals, &$targets) {
                foreach ($items as $item) {
                    if (isset($withDeals[$item->id])) {
                        continue;
                    }
                    if (in_array((string) $option, array_map('strval', TypeTagService::current($item->{$row->field})), true)) {
                        $targets[] = (int) $item->id;
                    }
                }
            });

        if ($this->option('dry-run')) {
            $this->line("  [{$label}] будет снят «Клиент» у " . count($targets) . ' компаний (с заказами: ' . count($withDeals) . ')');
            return;
        }
        $done = 0;
        foreach (array_chunk($targets, 200) as $chunk) {
            $done += Company::removeType($chunk, 'Клиент');
        }
        $this->line("  [{$label}] снят «Клиент» у {$done} компаний");
    }
}
