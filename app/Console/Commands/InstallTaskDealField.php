<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class InstallTaskDealField extends Command
{
    protected $signature = 'logistic:install-deal-field
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Добавить поле «Заказ покупателя» (deal_id, relation → deals) у задач логистики и самовывозов и заполнить его из существующих связей «на основании»';

    public const FIELD = 'deal_id';
    public const TITLE = 'Заказ покупателя';
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
        $dealsTypeId = $db->table('data_types')->where('slug', 'deals')->value('id');
        if (!$dealsTypeId || !$sb->hasTable('deals')) {
            $this->line("    [{$label}] сущности deals нет, пропуск");
            return;
        }

        foreach (self::ENTITIES as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                $this->line("    [{$label}] {$slug}: сущности нет, пропуск");
                continue;
            }

            if (!$sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::FIELD . '` INT NULL');
            }

            $row = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', self::FIELD)
                ->first();
            $attrs = [
                'type' => 'relation',
                'title' => self::TITLE,
                'relation_table' => 'deals',
                'details' => '{"table":"deals"}',
                'is_plural' => 0,
                'is_link' => 1,
                'is_remove' => 0,
                'hide' => 0,
                'only_read' => 0,
                'is_permanent' => 1,
            ];
            if ($row) {
                $db->table('data_rows')->where('id', $row->id)->update($attrs);
            } else {
                $sectionId = $db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id');
                $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
                $fieldId = $db->table('data_rows')->insertGetId($attrs + [
                    'data_type_id' => $typeId,
                    'field' => self::FIELD,
                    'required' => 0,
                    'visible_always' => 1,
                    'section_id' => $sectionId,
                    'sort' => $maxSort + 1,
                    'roles_read' => '',
                    'roles_write' => '',
                    'related_field' => '',
                    'is_default' => 0,
                    'is_program' => 0,
                ]);
                $this->line("    [{$label}] {$slug}: создано поле " . self::FIELD . " (id {$fieldId})");
            }

            if ($sb->hasTable('object_relations')) {
                $relations = $db->table('object_relations')
                    ->where('source_slug', 'deals')
                    ->where('target_slug', $slug)
                    ->orderBy('id')
                    ->get(['source_id', 'target_id']);
                $filled = 0;
                $seen = [];
                foreach ($relations as $relation) {
                    if (isset($seen[$relation->target_id])) {
                        continue;
                    }
                    $seen[$relation->target_id] = true;
                    $filled += $db->table($slug)
                        ->where('id', $relation->target_id)
                        ->whereNull(self::FIELD)
                        ->update([self::FIELD => (int) $relation->source_id]);
                }
                $this->line("    [{$label}] {$slug}: заполнено из связей: {$filled}");
            }

            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
