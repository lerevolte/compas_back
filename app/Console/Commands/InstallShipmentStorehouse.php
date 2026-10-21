<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\StorehouseService;
use Illuminate\Console\Command;

class InstallShipmentStorehouse extends Command
{
    public const DEALS = 'deals';
    public const B24_COLUMN = 'id_b24';

    protected $signature = 'storehouses:install-shipment
        {target=all : seeds | all-tenants | all (all-tenants + seeds) | <tenant_id>}
        {--map=* : соответствие значения списка Битрикс24 складу: <значение B24>:<id склада>, например 2806:1}
        {--dry-run : только показать план}';

    protected $description = '«Склад списания» → «Склад отгрузки» у всех сущностей; поле «Склад отгрузки» у заказов покупателей; соответствие складов значениям поля Битрикс24';

    public function handle(): int
    {
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
        $tenant->run(fn () => $this->apply(\DB::connection(), $target, true));

        return self::SUCCESS;
    }

    private function apply($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasTable(StorehouseService::TABLE)) {
            $this->line("  - {$label}: складов нет");
            return;
        }
        $dry = (bool) $this->option('dry-run');
        $title = StorehouseService::TITLES[StorehouseService::WRITE_OFF];

        $renamed = $db->table('data_rows')
            ->where('field', StorehouseService::WRITE_OFF)
            ->where('type', 'relation')
            ->where('title', StorehouseService::LEGACY_WRITE_OFF_TITLE);
        $renamedCount = $dry ? $renamed->count() : $renamed->update(['title' => $title]);
        $this->line("  ✓ {$label}: " . ($dry ? '[dry-run] ' : '') . "переименовано полей «" . StorehouseService::LEGACY_WRITE_OFF_TITLE . "» → «{$title}»: {$renamedCount}");

        $this->ensureDealField($db, $label, $dry);
        $this->applyB24Map($db, $label, $dry);

        if ($dry) {
            return;
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'like', 'fields/%')->update(['updated_at' => now()]);
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

    private function ensureDealField($db, string $label, bool $dry): void
    {
        $sb = $db->getSchemaBuilder();
        $field = StorehouseService::WRITE_OFF;
        $typeId = $db->table('data_types')->where('slug', self::DEALS)->value('id');
        if (!$typeId || !$sb->hasTable(self::DEALS)) {
            return;
        }
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->first();
        if ($existing) {
            $this->line("    [{$label}] " . self::DEALS . ": поле {$field} уже есть (id {$existing->id})");
            return;
        }
        $title = StorehouseService::TITLES[$field];
        $sameTitle = $db->table('data_rows')->where('data_type_id', $typeId)->where('title', $title)->where('is_remove', 0)->first(['id', 'field', 'type']);
        if ($sameTitle) {
            $this->warn("    [{$label}] " . self::DEALS . ": уже есть поле «{$title}» ({$sameTitle->field}, {$sameTitle->type}, id {$sameTitle->id})");
        }
        if ($dry) {
            $this->line("    [{$label}] " . self::DEALS . ": [dry-run] будет добавлено поле «{$title}»");
            return;
        }
        if (!$sb->hasColumn(self::DEALS, $field)) {
            $db->statement('ALTER TABLE `' . self::DEALS . '` ADD COLUMN `' . $field . '` TEXT NULL');
        }
        $sectionId = (int) $db->table('field_sections')->where('page', self::DEALS)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')->value('id');
        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
        $id = $db->table('data_rows')->insertGetId(array_merge($this->baseRow((int) $typeId, $sectionId), [
            'type' => 'relation',
            'title' => $title,
            'details' => '{"table":"' . StorehouseService::TABLE . '"}',
            'relation_table' => StorehouseService::TABLE,
            'is_link' => 1,
            'field' => $field,
            'sort' => $maxSort + 1,
        ]));
        $this->line("    [{$label}] " . self::DEALS . ": добавлено поле «{$title}» (id {$id})");
    }

    private function applyB24Map($db, string $label, bool $dry): void
    {
        $sb = $db->getSchemaBuilder();
        $pairs = [];
        foreach ((array) $this->option('map') as $item) {
            [$value, $id] = array_pad(explode(':', (string) $item, 2), 2, null);
            if (trim((string) $value) === '' || !is_numeric($id) || (int) $id <= 0) {
                $this->warn("    [{$label}] пропущено соответствие «{$item}» — ожидается <значение B24>:<id склада>");
                continue;
            }
            $pairs[trim((string) $value)] = (int) $id;
        }
        if (!count($pairs)) {
            return;
        }
        if ($dry) {
            foreach ($pairs as $value => $id) {
                $this->line("    [{$label}] [dry-run] склад {$id} «" . ($db->table(StorehouseService::TABLE)->where('id', $id)->value('name') ?? '—') . "» ↔ B24 {$value}");
            }
            return;
        }
        if (!$sb->hasColumn(StorehouseService::TABLE, self::B24_COLUMN)) {
            $db->statement('ALTER TABLE `' . StorehouseService::TABLE . '` ADD COLUMN `' . self::B24_COLUMN . '` VARCHAR(64) NULL');
        }
        foreach ($pairs as $value => $id) {
            $db->table(StorehouseService::TABLE)->where(self::B24_COLUMN, $value)->where('id', '!=', $id)->update([self::B24_COLUMN => null]);
            $updated = $db->table(StorehouseService::TABLE)->where('id', $id)->update([self::B24_COLUMN => $value]);
            $this->line("    [{$label}] склад {$id} ↔ B24 {$value}" . ($updated ? '' : ' (склад не найден или без изменений)'));
        }
    }

    private function baseRow(int $typeId, int $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => 0,
            'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 0, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 0, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0,
        ];
    }
}
