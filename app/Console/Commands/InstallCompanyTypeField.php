<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallCompanyTypeField extends Command
{
    protected $signature = 'companies:install-type-field
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Добавить поле «Тип компании» (select_dropdown: Клиент/Поставщик/Перевозчик) в сущность companies';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function install($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        $dry = (bool) $this->option('dry-run');

        $dataType = $db->table('data_types')->where('slug', 'companies')->first();
        if (!$dataType || !$sb->hasTable('companies')) {
            $this->warn("    [{$label}] сущность companies не найдена, пропуск");
            return;
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('type', 'select_dropdown')
            ->where('title', 'Тип компании')
            ->where('is_remove', 0)
            ->first();

        if ($existing) {
            if (!$sb->hasColumn('companies', $existing->field)) {
                if ($dry) {
                    $this->line("    [{$label}] поле есть (id {$existing->id}), нет колонки {$existing->field} — будет создана");
                    return;
                }
                $db->statement("ALTER TABLE `companies` ADD COLUMN `{$existing->field}` TEXT NULL");
                $this->line("    [{$label}] добавлена колонка companies.{$existing->field}");
                $this->clearCache();
            } else {
                $this->line("    [{$label}] поле уже установлено (id {$existing->id}), пропуск");
            }
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', 'companies')
            ->whereNull('module')
            ->orderBy('sort')
            ->value('id');

        $maxSort = (int) $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->max('sort');

        if ($dry) {
            $this->line("    [{$label}] будет создано поле «Тип компании» (section_id " . ($sectionId ?? 'NULL') . ")");
            return;
        }

        $id = $db->table('data_rows')->insertGetId([
            'data_type_id' => $dataType->id,
            'field' => 'tip_kompanii',
            'type' => 'select_dropdown',
            'title' => 'Тип компании',
            'required' => 0,
            'visible_always' => 1,
            'section_id' => $sectionId,
            'sort' => $maxSort + 1,
            'is_plural' => 1,
            'details' => json_encode([
                'options' => [
                    ['value' => 0, 'label' => 'Клиент'],
                    ['value' => 1, 'label' => 'Поставщик'],
                    ['value' => 2, 'label' => 'Перевозчик'],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $field = "tip_kompanii_{$id}";
        $db->table('data_rows')->where('id', $id)->update(['field' => $field]);

        if (!$sb->hasColumn('companies', $field)) {
            $db->statement("ALTER TABLE `companies` ADD COLUMN `{$field}` TEXT NULL");
        }

        $this->line("    [{$label}] создано поле {$field} (id {$id})");
        $this->clearCache();
    }

    private function clearCache(): void
    {
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
