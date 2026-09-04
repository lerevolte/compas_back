<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallTaskNumbers extends Command
{
    protected $signature = 'logistic:install-task-numbers
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Единая сквозная нумерация задач логистики и самовывозов: таблица document_counters, поле «Номер» (number) у обеих сущностей, бэкфилл по дате создания';

    public const FIELD = 'number';
    public const TITLE = 'Номер';
    public const ENTITIES = ['logistic_tasks', 'pickups'];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();

        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `document_counters` (
  `name` varchar(64) NOT NULL,
  `value` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $rows = [];
        foreach (self::ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                continue;
            }

            if (!$sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::FIELD . '` VARCHAR(32) NULL');
            }

            $existing = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', self::FIELD)
                ->first();
            $attrs = [
                'type' => 'text',
                'title' => self::TITLE,
                'only_read' => 1,
                'is_program' => 1,
                'is_default' => 1,
                'is_permanent' => 1,
                'is_remove' => 0,
                'hide' => 0,
            ];
            if ($existing) {
                $db->table('data_rows')->where('id', $existing->id)->update($attrs);
            } else {
                $sectionId = $db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id');
                $db->table('data_rows')->insert($attrs + [
                    'data_type_id' => $typeId,
                    'field' => self::FIELD,
                    'visible_always' => 1,
                    'section_id' => $sectionId,
                    'sort' => 1,
                    'is_plural' => 0,
                ]);
                $this->line("    [{$label}] {$slug}: создано поле «" . self::TITLE . '»');
            }

            foreach ($db->table($slug)->whereNull(self::FIELD)->orderBy('created_at')->orderBy('id')->get(['id', 'created_at']) as $row) {
                $rows[] = ['slug' => $slug, 'id' => (int) $row->id, 'created_at' => (string) $row->created_at];
            }

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        usort($rows, fn ($a, $b) => [$a['created_at'], $a['id']] <=> [$b['created_at'], $b['id']]);

        $current = (int) $db->table('document_counters')->where('name', \App\Services\DocumentNumber::COUNTER)->value('value');
        $assigned = 0;
        foreach ($rows as $row) {
            $current++;
            $db->table($row['slug'])->where('id', $row['id'])->update([self::FIELD => (string) $current]);
            $assigned++;
        }
        $db->table('document_counters')->updateOrInsert(
            ['name' => \App\Services\DocumentNumber::COUNTER],
            ['value' => $current]
        );

        $this->line("    [{$label}] номеров присвоено: {$assigned}, счётчик = {$current}");

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
