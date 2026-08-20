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
 * Сущность видима (data_types.hidden=0) и выводится в сайдбар
 * (sidebar_items, slug deals) — фича одобрена по задаче «вывести
 * Сделки/Контакты в сайдбар avixo».
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
    private array $excludeFields = ['route_id', 'employee_id', 'point_status', 'sort', 'service_time', 'plan_time', 'delivery_date', 'company_id', 'contact_id', 'shipment_company_id', 'saby_waybills', 'b24_id'];

    /** Кастомные поля logistic_tasks (по заголовку), которые НЕ переносим в сделки. */
    private array $excludeTitles = ['Склад отгрузки', 'Время прибытия', 'План. время прибытия', 'Планируемое время прибытия', 'Дата доставки'];

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

        $dealsIndexes = collect($db->select('SHOW INDEX FROM `deals`'))->pluck('Key_name');
        if (!$dealsIndexes->contains('deals_b24_id_index')) {
            $db->statement('ALTER TABLE `deals` ADD INDEX `deals_b24_id_index` (`b24_id`)');
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
            'hidden'               => 0,
        ]);

        $infoSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Информация', 'page' => 'deals',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
            'column_id' => 1, '_lft' => 0, '_rgt' => 0,
        ]);

        $this->cloneDataRows($db, $typeId, $infoSecId);
        $this->insertOwnRows($db, $typeId, $infoSecId);
        $this->patchRelatedEntities($db, $label);

        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null,
            'value' => json_encode([
                ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                ['title' => 'Товары и услуги', 'tab' => 'products', 'sort' => 1, 'enabled' => 1, 'id' => 1],
                ['title' => 'История изменений', 'tab' => 'history', 'sort' => 2, 'enabled' => true, 'id' => 2, 'has_roles_read' => false, 'roles_read' => null],
            ], JSON_UNESCAPED_SLASHES),
            'type' => 'menu', 'entity' => 'deals', 'user_id' => null,
        ]);

        if ($sb->hasTable('sidebar_items')) {
            $existingItem = $db->table('sidebar_items')->where('slug', 'deals')->first();
            if ($existingItem) {
                $db->table('sidebar_items')->where('slug', 'deals')
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => 'Сделки', 'slug' => 'deals',
                    'sort' => 0, 'link' => '/objects/deals',
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

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
            ->whereNotIn('title', $this->excludeTitles)
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
            if ($arr['field'] === 'user_id') {
                $arr['required'] = 1;
            }
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

    private function patchRelatedEntities($db, string $label): void
    {
        $targets = [
            'contacts'  => 'contact_id',
            'companies' => 'company_id',
        ];
        foreach ($targets as $slug => $relatedField) {
            $type = $db->table('data_types')
                ->where('slug', $slug)->orWhere('name', $slug)
                ->first();
            if (!$type) {
                continue;
            }
            if (!$db->getSchemaBuilder()->hasColumn($slug, 'deal_id')) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `deal_id` INT NULL");
            }
            $exists = $db->table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('field', 'deal_id')
                ->exists();
            if ($exists) {
                continue;
            }
            $sectionId = (int) ($db->table('field_sections')
                ->where('page', $slug)->where('name', 'Прикрепленные сущности')
                ->value('id')
                ?: $db->table('field_sections')
                    ->where('page', $slug)->orderBy('sort')
                    ->value('id')
                ?: 0);
            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $type->id)
                ->where('section_id', $sectionId)
                ->max('sort');
            $db->table('data_rows')->insert([
                'data_type_id' => $type->id, 'field' => 'deal_id', 'type' => 'relation',
                'title' => 'Сделки', 'required' => 0, 'details' => '{"table":"deals"}',
                'visible_always' => 1, 'label_color' => '', 'section_id' => $sectionId, 'group_id' => null,
                'sort' => $maxSort + 1, 'button_name' => 'Загрузить', 'show_file_image' => 0, 'hide' => 0,
                'is_plural' => 1, 'roles_read' => '', 'roles_write' => '', 'is_remove' => 0,
                'mobile_pages' => '', 'only_read' => 0, 'is_permanent' => 0, 'show_file_name' => 0,
                'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 1,
                'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
                'blocked_changes' => 0, 'permanent_required' => 0, 'permanent_name' => 0,
                'relation_table' => 'deals', 'related_field' => $relatedField, 'set_color' => 0,
                'is_unique' => 0, 'is_program' => 0,
            ]);
            $this->line("    [{$label}] {$slug}: добавлено поле deal_id (section={$sectionId})");
        }
    }
}
