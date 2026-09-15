<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ModifyFields extends Command
{
    protected $signature = 'fields:modify
        {slug : slug сущности (pickups, expense_invoices, …)}
        {target=all-tenants : seeds | all-tenants | all (all-tenants + seeds) | <tenant_id>}
        {--field=* : имя поля (data_rows.field)}
        {--title=* : заголовок поля (точное совпадение)}
        {--remove : удалить поля (data_rows, field_values, section_fields_sort; колонка остаётся)}
        {--hide : скрыть поля (hide=1)}
        {--show : показать поля (hide=0)}
        {--dry-run : только показать план}';

    protected $description = 'Удалить/скрыть/показать поля сущности на порталах по имени поля или заголовку';

    public function handle(): int
    {
        if (!$this->option('remove') && !$this->option('hide') && !$this->option('show')) {
            $this->error('Укажите действие: --remove, --hide или --show');
            return self::FAILURE;
        }
        if (!count($this->option('field')) && !count($this->option('title'))) {
            $this->error('Укажите --field=… и/или --title=…');
            return self::FAILURE;
        }

        $target = (string) $this->argument('target');

        if ($target === 'seeds' || $target === 'all') {
            $this->apply(\DB::connection('seeds'), 'admin_seeds', false);
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }

        if ($target === 'all-tenants' || $target === 'all') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id, true));
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
        $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id, true));

        return self::SUCCESS;
    }

    private function apply($db, string $label, bool $inTenant): void
    {
        $slug = (string) $this->argument('slug');
        $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId) {
            $this->line("  - {$label}: сущности {$slug} нет");
            return;
        }

        $fields = array_values(array_filter($this->option('field')));
        $titles = array_values(array_filter($this->option('title')));
        $rows = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where(function ($q) use ($fields, $titles) {
                if (count($fields)) {
                    $q->whereIn('field', $fields);
                }
                if (count($titles)) {
                    count($fields) ? $q->orWhereIn('title', $titles) : $q->whereIn('title', $titles);
                }
            })
            ->get(['id', 'field', 'title', 'hide', 'is_remove']);

        if ($rows->isEmpty()) {
            $this->line("  - {$label}: полей не найдено");
            return;
        }

        $dry = (bool) $this->option('dry-run');
        $names = $rows->map(fn ($r) => "{$r->field} («{$r->title}»)")->implode(', ');
        $ids = $rows->pluck('id')->all();

        if ($this->option('remove')) {
            if (!$dry) {
                $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
                $db->table('field_values')->whereIn('field_id', $ids)->delete();
                $db->table('data_rows')->whereIn('group_id', $ids)->update(['group_id' => null]);
                foreach ($db->table('data_rows')->where('data_type_id', $typeId)->where('type', 'text_group')->get(['id', 'subfields']) as $group) {
                    $sub = json_decode((string) $group->subfields, true);
                    if (is_array($sub) && array_intersect($sub, $ids)) {
                        $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode(array_values(array_diff($sub, $ids)))]);
                    }
                }
                $db->table('data_rows')->whereIn('id', $ids)->delete();
            }
            $this->line("  ✓ {$label}: " . ($dry ? '[dry-run] ' : '') . "удалено — {$names}");
        } elseif ($this->option('hide') || $this->option('show')) {
            $hide = $this->option('hide') ? 1 : 0;
            if (!$dry) {
                $db->table('data_rows')->whereIn('id', $ids)->update(['hide' => $hide]);
            }
            $this->line("  ✓ {$label}: " . ($dry ? '[dry-run] ' : '') . ($hide ? 'скрыто' : 'показано') . " — {$names}");
        }

        if ($dry) {
            return;
        }
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
