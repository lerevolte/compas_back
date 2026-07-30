<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Устанавливает сущность «Сделки» (deals) в портал.
 *
 * Сделки синхронизируются с Bitrix24 (направление CATEGORY_ID=0, кроме NEW):
 * поля — как у logistic_tasks (клонируются), плюс:
 *   - stage      (type deal_stages) — шкала стадий из Bitrix24;
 *   - contact_id (plural relation -> contacts);
 *   - company_id (plural relation -> companies).
 *
 * Сущность ставится СКРЫТОЙ (data_types.hidden=1, без sidebar_items):
 * доступна по прямому URL /objects/deals, не светится в меню/ролях/аналитике.
 * После одобрения — снять hidden и добавить пункт меню.
 *
 * Команда идемпотентна. Порядок выкатки (политика avixo, см. CLAUDE.md):
 *   1) php artisan entity:install-deals avixo        # тестовый портал
 *   2) после одобрения: seeds, затем all-tenants
 */
class InstallDealsEntity extends Command
{
    protected $signature = 'entity:install-deals
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить сущность «Сделки» (deals) в admin_seeds / тенант / все тенанты';

    /** Поля logistic_tasks, которые НЕ переносим в сделки. */
    private array $excludeFields = ['route_id', 'employee_id', 'point_status', 'sort'];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function installInto($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasTable('logistic_tasks')) {
            throw new \RuntimeException('нет таблицы logistic_tasks — эталон для deals');
        }

        $db->statement('CREATE TABLE IF NOT EXISTS `deals` LIKE `logistic_tasks`');
        foreach ([
            'stage'      => 'VARCHAR(64) NULL',
            'b24_id'     => 'VARCHAR(32) NULL',
            'contact_id' => 'TEXT NULL',
            'company_id' => 'TEXT NULL',
        ] as $col => $ddl) {
            if (!$sb->hasColumn('deals', $col)) {
                $db->statement("ALTER TABLE `deals` ADD COLUMN `{$col}` {$ddl}");
            }
        }

        $db->statement('CREATE TABLE IF NOT EXISTS `contact_deal` (
            `contact_id` INT NOT NULL, `deal_id` INT NOT NULL,
            UNIQUE KEY `contact_deal_unique` (`contact_id`, `deal_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $db->statement('CREATE TABLE IF NOT EXISTS `company_deal` (
            `company_id` INT NOT NULL, `deal_id` INT NOT NULL,
            UNIQUE KEY `company_deal_unique` (`company_id`, `deal_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        if ($sb->hasTable('companies') && !$sb->hasColumn('companies', 'deal_id')) {
            $db->statement('ALTER TABLE `companies` ADD COLUMN `deal_id` INT NULL');
        }

        $oldTypeIds = $db->table('data_types')
            ->where('name', 'deals')->orWhere('slug', 'deals')
            ->pluck('id');
        if ($oldTypeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->delete();
            $db->table('data_types')->whereIn('id', $oldTypeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'deals')->delete();
        $db->table('settings')->where('entity', 'deals')->where('type', 'menu')->delete();

        $now = now();

        $typeId = $db->table('data_types')->insertGetId([
            'name'                 => 'deals',
            'slug'                 => 'deals',
            'title_singular'       => 'Сделка',
            'title_plural'         => 'Сделки',
            'icon'                 => null,
            'model_name'           => 'App\\Models\\Deal',
            'generate_permissions' => 1,
            'server_side'          => 0,
            'created_at'           => $now,
            'updated_at'           => $now,
            'color'                => '#E8A33D',
            'enable'               => 1,
            'slug_singular'        => 'deal',
            'hidden'               => 1,
        ]);

        $infoSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Информация', 'page' => 'deals',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
            'column_id' => 1, '_lft' => 0, '_rgt' => 0,
        ]);

        $this->cloneDataRows($db, $typeId, $infoSecId);
        $this->insertOwnRows($db, $typeId, $infoSecId);

        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null,
            'value' => json_encode([
                ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                ['title' => 'История изменений', 'tab' => 'history', 'sort' => 1, 'enabled' => true, 'id' => 1, 'has_roles_read' => false, 'roles_read' => null],
            ], JSON_UNESCAPED_SLASHES),
            'type' => 'menu', 'entity' => 'deals', 'user_id' => null,
        ]);

        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }

        $this->line("    [{$label}] deals: data_type={$typeId}, section={$infoSecId}");
    }

    private function cloneDataRows($db, int $typeId, int $infoSecId): void
    {
        $src = $db->table('data_types')
            ->where('slug', 'logistic_tasks')->orWhere('name', 'logistic_tasks')
            ->first();
        if (!$src) {
            throw new \RuntimeException('нет сущности logistic_tasks');
        }

        $rows = $db->table('data_rows')
            ->where('data_type_id', $src->id)
            ->whereNotIn('field', $this->excludeFields)
            ->orderByRaw('group_id IS NULL DESC')
            ->orderBy('sort')
            ->get();

        $idMap = [];
        foreach ($rows as $row) {
            if ($row->type === 'status') {
                $this->warn("    пропущено поле-статус {$row->field}: его варианты в field_values привязаны к старому data_rows.id");
                continue;
            }
            $arr = (array) $row;
            $oldId = $arr['id'];
            unset($arr['id']);
            $arr['data_type_id'] = $typeId;
            $arr['section_id']   = $infoSecId;
            $arr['module']       = '';
            $arr['module_section_id'] = null;
            if (!empty($arr['group_id'])) {
                if (!isset($idMap[$arr['group_id']])) {
                    continue;
                }
                $arr['group_id'] = $idMap[$arr['group_id']];
            }
            if (!empty($arr['subfields'])) {
                $sub = json_decode($arr['subfields'], true);
                $arr['subfields'] = is_array($sub)
                    ? json_encode(array_values(array_filter(array_map(fn ($i) => $idMap[$i] ?? null, $sub))))
                    : null;
            }
            $idMap[$oldId] = $db->table('data_rows')->insertGetId($arr);
        }
    }

    private function insertOwnRows($db, int $typeId, int $infoSecId): void
    {
        $base = [
            'data_type_id' => $typeId, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $infoSecId, 'group_id' => null, 'sort' => 0,
            'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
            'is_plural' => 0, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
            'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0,
        ];

        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'stage', 'type' => 'deal_stages', 'title' => 'Стадия',
            'sort' => 4, 'only_read' => 1,
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'contact_id', 'type' => 'relation', 'title' => 'Контакт',
            'sort' => 5, 'is_plural' => 1, 'details' => '{"table":"contacts"}',
            'is_link' => 1, 'relation_table' => 'contacts', 'related_field' => 'deal_id',
        ]));
        $db->table('data_rows')->insert(array_merge($base, [
            'field' => 'company_id', 'type' => 'relation', 'title' => 'Компания',
            'sort' => 6, 'is_plural' => 1, 'details' => '{"table":"companies"}',
            'is_link' => 1, 'relation_table' => 'companies', 'related_field' => 'deal_id',
        ]));
    }
}
