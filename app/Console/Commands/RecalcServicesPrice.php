<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class RecalcServicesPrice extends Command
{
    protected $signature = 'services-price:recalc
        {target=avixo : all-tenants | <tenant_id>}
        {--entity=deals : deals | logistic_tasks | all}';

    protected $description = 'Пересчитать «Цену услуг» (delivery_price) из услуг состава у заказов покупателей и/или задач логистики';

    public function handle(): int
    {
        $target = $this->argument('target');
        $entities = $this->option('entity') === 'all' ? ['deals', 'logistic_tasks'] : [$this->option('entity')];
        $classes = ['deals' => \App\Models\Deal::class, 'logistic_tasks' => \App\Models\Task::class];

        $tenants = $target === 'all-tenants' ? Tenant::get() : Tenant::where('id', $target)->get();
        if ($tenants->isEmpty()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant, $entities, $classes) {
                    foreach ($entities as $slug) {
                        $class = $classes[$slug] ?? null;
                        if (!$class || !\Schema::hasTable($slug) || !\Schema::hasColumn($slug, 'delivery_price')) {
                            $this->line("  − {$tenant->id}: {$slug} пропуск");
                            continue;
                        }
                        $total = 0;
                        $changed = 0;
                        $class::whereNotNull('products')->where('products', '!=', '')->where('products', '!=', '[]')
                            ->chunkById(200, function ($rows) use (&$total, &$changed) {
                                foreach ($rows as $row) {
                                    $total++;
                                    $before = (string) $row->delivery_price;
                                    try {
                                        $row->recalcServicesPrice();
                                    } catch (\Throwable $e) {
                                        continue;
                                    }
                                    if ((string) $row->delivery_price !== $before) {
                                        $changed++;
                                    }
                                }
                            });
                        $this->info("  ✓ {$tenant->id}: {$slug} проверено={$total}, изменено={$changed}");
                    }
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
