<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallCommentField extends Command
{
    protected $signature = 'fields:install-comment
        {target=all-tenants : seeds | all-tenants | <tenant_id>}
        {--entities= : список сущностей через запятую вместо стандартного}';

    protected $description = 'Поле «Примечание» (comment, многострочный текст) во всех документных сущностях';

    public const FIELD = 'comment';
    public const TITLE = 'Примечание';
    public const ENTITIES = [
        'deals', 'supplier_orders', 'logistic_tasks', 'pickups',
        'payment_invoices', 'expense_invoices', 'product_returns', 'receipt_invoices',
        'addresses', 'warehouses',
    ];

    public function handle(): int
    {
        $target = (string) $this->argument('target');
        $custom = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('entities')))));
        $entities = count($custom) ? $custom : self::ENTITIES;

        if ($target === 'seeds') {
            $this->report(self::install(\DB::connection('seeds'), $entities), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(function () use ($entities, $tenant) {
                        $this->report(self::install(\DB::connection(), $entities), (string) $tenant->id);
                        $this->clearCache();
                    });
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($entities, $target) {
            $this->report(self::install(\DB::connection(), $entities), $target);
            $this->clearCache();
        });
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    public static function install($db, array $entities = self::ENTITIES): array
    {
        $sb = $db->getSchemaBuilder();
        $result = [];
        foreach ($entities as $slug) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId || !$sb->hasTable($slug)) {
                continue;
            }
            if (!$sb->hasColumn($slug, self::FIELD)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `" . self::FIELD . '` TEXT NULL');
            }
            $row = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->first();
            if ($row) {
                $db->table('data_rows')->where('id', $row->id)->update(['type' => 'text', 'is_plural' => 1, 'is_remove' => 0]);
                $result[$slug] = 'updated';
            } else {
                $sectionId = (int) ($db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id') ?: 0);
                $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
                $db->table('data_rows')->insert(array_merge(InstallSaleDocsEntities::baseRow((int) $typeId, $sectionId), [
                    'field' => self::FIELD,
                    'type' => 'text',
                    'title' => self::TITLE,
                    'is_plural' => 1,
                    'sort' => $maxSort + 1,
                ]));
                $result[$slug] = 'created';
            }
            try {
                if ($sb->hasTable('local_cache')) {
                    $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
                }
            } catch (\Throwable $e) {
            }
        }

        return $result;
    }

    private function report(array $result, string $label): void
    {
        $created = array_keys(array_filter($result, fn ($v) => $v === 'created'));
        $this->line("    [{$label}] «" . self::TITLE . '»: сущностей ' . count($result) . ', добавлено в: ' . (count($created) ? implode(', ', $created) : '—'));
    }

    private function clearCache(): void
    {
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
