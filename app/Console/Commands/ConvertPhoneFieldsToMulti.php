<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class ConvertPhoneFieldsToMulti extends Command
{
    protected $signature = 'fields:phones-to-multi
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Сделать поля «Телефон» множественными (multi_text) во всех сущностях; users: «Мобильный телефон» → «Телефон»';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->convert(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->convert(\DB::connection(), (string) $tenant->id));
                    $this->info("  ✓ {$tenant->id}");
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
        $tenant->run(fn () => $this->convert(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function convert($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $dry = (bool) $this->option('dry-run');

        $rows = $db->table('data_rows')
            ->join('data_types', 'data_types.id', '=', 'data_rows.data_type_id')
            ->where('data_rows.type', 'text')
            ->where('data_rows.is_remove', 0)
            ->where(function ($q) {
                $q->where('data_rows.title', 'Телефон')
                    ->orWhere(function ($q2) {
                        $q2->where('data_types.slug', 'users')
                            ->where('data_rows.title', 'Мобильный телефон');
                    });
            })
            ->select('data_rows.id', 'data_rows.field', 'data_rows.title', 'data_types.slug')
            ->get();

        foreach ($rows as $row) {
            if (!$sb->hasTable($row->slug) || !$sb->hasColumn($row->slug, $row->field)) {
                $this->warn("    [{$label}] {$row->slug}.{$row->field}: нет таблицы/колонки, пропуск");
                continue;
            }

            $affected = $db->table($row->slug)
                ->whereNotNull($row->field)
                ->where($row->field, '!=', '')
                ->where($row->field, 'NOT LIKE', '[%')
                ->count();

            if ($dry) {
                $this->line("    [{$label}] {$row->slug}.{$row->field} ({$row->title}) → multi_text, обернуть значений: {$affected}");
                continue;
            }

            $db->table('data_rows')->where('id', $row->id)->update([
                'type' => 'multi_text',
                'is_plural' => 1,
                'title' => 'Телефон',
            ]);
            $db->statement(
                "UPDATE `{$row->slug}` SET `{$row->field}` = JSON_ARRAY(`{$row->field}`)
                 WHERE `{$row->field}` IS NOT NULL AND `{$row->field}` != '' AND `{$row->field}` NOT LIKE '[%'"
            );
            $this->line("    [{$label}] {$row->slug}.{$row->field}: multi_text, обёрнуто {$affected}");
        }

        if (!$dry) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
