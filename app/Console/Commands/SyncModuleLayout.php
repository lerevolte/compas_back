<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ModuleLayoutService;
use Illuminate\Console\Command;

class SyncModuleLayout extends Command
{
    protected $signature = 'seeds:sync-module-layout
        {module : slug модуля (logistic, saby, relations, …) или all}
        {page? : slug сущности; без него — все сущности модуля из seeds}
        {--tenant=* : ограничить порталами}';

    protected $description = 'Применить разделы и порядок полей страниц модуля из seeds ко всем порталам';

    public function handle(): int
    {
        $module = (string) $this->argument('module');
        $page = $this->argument('page');
        $tenantIds = $this->option('tenant') ?: null;
        $seeds = \DB::connection('seeds');

        $query = $seeds->table('field_sections')->whereNotNull('module')->where('module', '!=', '');
        if ($module !== 'all') {
            $query->where('module', $module);
        }
        if ($page) {
            $query->where('page', $page);
        }
        $pairs = $query->get(['page', 'module'])->map(fn ($r) => [(string) $r->module, (string) $r->page])->unique(fn ($p) => $p[0] . ':' . $p[1])->values();

        if ($pairs->isEmpty()) {
            $this->warn('В seeds нет разделов для указанного модуля/сущности');
            return self::SUCCESS;
        }

        foreach ($pairs as [$m, $p]) {
            $this->info("{$m} / {$p}");
            foreach (ModuleLayoutService::syncFromSeeds($m, $p, $tenantIds) as $tenant => $result) {
                if (isset($result['error'])) {
                    $this->error("  ✗ {$tenant}: {$result['error']}");
                } elseif (isset($result['skipped'])) {
                    $this->line("  - {$tenant}: {$result['skipped']}");
                } else {
                    $missing = count($result['missing']) ? ', нет полей: ' . implode(', ', $result['missing']) : '';
                    $this->line("  ✓ {$tenant}: разделов {$result['sections']}, полей {$result['fields']}{$missing}");
                }
            }
        }

        return self::SUCCESS;
    }
}
