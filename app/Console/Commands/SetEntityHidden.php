<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class SetEntityHidden extends Command
{
    protected $signature = 'entity:set-hidden
        {slugs : slug сущности или несколько через запятую (product_returns,receipt_invoices)}
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--show : вернуть сущность обратно (hidden=0, пункт меню восстанавливается)}';

    protected $description = 'Скрыть сущность (data_types.hidden=1, пункт из бокового меню, вкладки связей и «Создать на основании») или показать её обратно';

    public function handle(): int
    {
        $slugs = array_values(array_filter(array_map('trim', explode(',', (string) $this->argument('slugs')))));
        if (!count($slugs)) {
            $this->error('Не указаны slug сущностей');
            return self::FAILURE;
        }
        $target = (string) $this->argument('target');
        $show = (bool) $this->option('show');

        if ($target === 'seeds') {
            $this->apply(\DB::connection('seeds'), 'admin_seeds', $slugs, $show, false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id, $slugs, $show, true));
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
        $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id, $slugs, $show, true));

        return self::SUCCESS;
    }

    private function apply($db, string $label, array $slugs, bool $show, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $now = now();
        foreach ($slugs as $slug) {
            $type = $db->table('data_types')->where('slug', $slug)->first();
            if (!$type) {
                $this->line("    [{$label}] {$slug}: сущности нет, пропуск");
                continue;
            }
            $db->table('data_types')->where('id', $type->id)->update(['hidden' => $show ? 0 : 1]);

            if ($sb->hasTable('sidebar_items')) {
                if ($show) {
                    $title = $type->title_plural ?: ($type->title_singular ?: $slug);
                    $existing = $db->table('sidebar_items')->where('slug', $slug)->first();
                    if ($existing) {
                        $db->table('sidebar_items')->where('id', $existing->id)->update(['enabled' => 1, 'is_hidden' => 0, 'updated_at' => $now]);
                    } else {
                        $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                        $db->table('sidebar_items')->insert([
                            'created_at' => $now, 'updated_at' => $now,
                            'name' => $title, 'slug' => $slug,
                            'sort' => 0, 'link' => '/objects/' . $slug,
                            '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                            'is_hidden' => 0, 'enabled' => 1,
                        ]);
                    }
                } else {
                    $db->table('sidebar_items')->where('slug', $slug)->delete();
                }
            }

            $this->line("    [{$label}] {$slug}: " . ($show ? 'показана' : 'скрыта'));
        }

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
