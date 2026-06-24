<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use App\Models\Tenant;

/**
 * Устанавливает сущность «Быстрые задачи» (warehouses) в портал — полный
 * аналог «Библиотеки задач» (addresses), но с собственной таблицей warehouses.
 *
 * Что ставит (живёт в каждой тенант-БД, копируется в новые порталы из
 * admin_seeds функцией TenantService::create):
 *   - data_types        — сама сущность warehouses (модель App\Models\Warehouse);
 *   - field_sections    — два раздела: «Информация» и «Используемые поля в модуле»;
 *   - data_rows         — поля сущности (клонируются из logistic_tasks);
 *   - sidebar_items     — пункт сайдбара «Быстрые задачи»;
 *   - settings(menu)    — меню вкладок карточки.
 * Плюс гарантирует наличие таблицы warehouses.
 *
 * Команда ИДЕМПОТЕНТНА.
 *
 * Порядок выкатки:
 *   1) php artisan entity:install-warehouses seeds
 *   2) php artisan entity:install-warehouses admin_test4
 *   3) php artisan entity:install-warehouses all-tenants
 */
class InstallWarehousesEntity extends Command
{
    protected $signature = 'entity:install-warehouses
        {target=seeds : seeds | all-tenants | <tenant_id> (например admin_test4)}
        {--recreate-table : пересоздать таблицу warehouses (DROP + CREATE), иначе только создать если нет}';

    protected $description = 'Установить сущность «Быстрые задачи» (warehouses) в admin_seeds / тенант / все тенанты';

    private array $fields = [
        'id', 'created_at', 'updated_at', 'name', 'photo', 'service_time',
        'address', 'phone', 'time', 'car_requirements', 'employee_requirements',
        'client_id', 'user_id', 'comment', 'contact',
    ];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->info('Установка в базу-шаблон admin_seeds (connection: seeds)…');
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $tenants = Tenant::get();
            $this->info('Установка во все порталы: '.$tenants->count());
            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        $this->installInto(\DB::connection(), (string) $tenant->id);
                    });
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: ".$e->getMessage());
                }
            }
            $this->info('Готово: all-tenants');
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден (id портала — это имя без префикса '".config('tenancy.database.prefix')."', например test4)");
            return self::FAILURE;
        }
        $this->info("Установка в портал {$target}…");
        $tenant->run(function () use ($target) {
            $this->installInto(\DB::connection(), (string) $target);
        });
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function installInto(ConnectionInterface $db, string $label): void
    {
        $this->ensureTable($db);

        $oldTypeIds = $db->table('data_types')
            ->where('name', 'warehouses')->orWhere('slug', 'warehouses')
            ->pluck('id');
        if ($oldTypeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->delete();
            $db->table('data_types')->whereIn('id', $oldTypeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'warehouses')->delete();
        $db->table('sidebar_items')->where('slug', 'warehouses')->delete();
        $db->table('settings')->where('entity', 'warehouses')->where('type', 'menu')->delete();

        $now = now();

        $typeId = $db->table('data_types')->insertGetId([
            'name'                 => 'warehouses',
            'slug'                 => 'warehouses',
            'title_singular'       => 'Быстрая задача',
            'title_plural'         => 'Быстрые задачи',
            'icon'                 => null,
            'model_name'           => 'App\\Models\\Warehouse',
            'policy_name'          => null,
            'controller'           => null,
            'description'          => null,
            'generate_permissions' => 1,
            'server_side'          => 0,
            'details'              => null,
            'created_at'           => $now,
            'updated_at'           => $now,
            'color'                => '#7FD1B9',
            'menu'                 => null,
            'enable'               => 1,
            'slug_singular'        => 'warehouse',
            'hidden'               => 0,
            'empty_text'           => null,
        ]);

        $infoSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Информация', 'domain_key' => null, 'page' => 'warehouses',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
            'column_id' => 1, 'module' => null, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
        ]);
        $moduleSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => 'warehouses',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
            'column_id' => 1, 'module' => 'logistic', 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
        ]);

        $this->installDataRows($db, $typeId, $infoSecId, $moduleSecId);

        $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
        $db->table('sidebar_items')->insert([
            'created_at' => $now, 'updated_at' => $now,
            'name' => 'Быстрые задачи', 'slug' => 'warehouses',
            'sort' => 0, 'link' => '/objects/warehouses',
            '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
            'is_hidden' => 0, 'enabled' => 1,
        ]);

        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null, 'value' => $this->menuJson(),
            'type' => 'menu', 'entity' => 'warehouses', 'user_id' => null,
        ]);

        $this->line("    [{$label}] data_type={$typeId}, sections=[{$infoSecId},{$moduleSecId}]");
    }

    private function installDataRows(ConnectionInterface $db, int $typeId, int $infoSecId, int $moduleSecId): void
    {
        $cloned = [];
        $src = $db->table('data_types')
            ->where('slug', 'logistic_tasks')->orWhere('name', 'logistic_tasks')
            ->first();

        if ($src) {
            $rows = $db->table('data_rows')
                ->where('data_type_id', $src->id)
                ->whereIn('field', $this->fields)
                ->get();

            foreach ($rows as $row) {
                $arr = (array) $row;
                unset($arr['id']);
                $arr['data_type_id'] = $typeId;
                $arr['section_id']   = $infoSecId;
                $arr['module_section_id'] = $this->isNonEmptyJsonArray($arr['module_section_id'] ?? null)
                    ? '['.$moduleSecId.']'
                    : ($arr['module_section_id'] ?? null);
                $db->table('data_rows')->insert($arr);
                $cloned[$row->field] = true;
            }
        }

        foreach ($this->fallbackRows($typeId, $infoSecId, $moduleSecId) as $field => $def) {
            if (!isset($cloned[$field])) {
                $db->table('data_rows')->insert($def);
            }
        }
    }

    private function isNonEmptyJsonArray($v): bool
    {
        if ($v === null) {
            return false;
        }
        $v = trim((string) $v);
        if ($v === '' || $v === '0' || $v === '[]') {
            return false;
        }
        $decoded = json_decode($v, true);
        return is_array($decoded) && count($decoded) > 0;
    }

    private function baseRow(int $typeId, int $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'field' => null, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => 0,
            'created_at' => null, 'updated_at' => null, 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ];
    }

    private function fallbackRows(int $typeId, int $infoSecId, int $moduleSecId): array
    {
        $mod = '['.$moduleSecId.']';
        $base = fn (array $o) => array_merge($this->baseRow($typeId, $infoSecId), $o);

        return [
            'id'                    => $base(['field' => 'id', 'type' => 'number', 'title' => 'ID', 'sort' => 0, 'label_color' => '#032491ff', 'only_read' => 1, 'is_default' => 1, 'is_program' => 1]),
            'created_at'            => $base(['field' => 'created_at', 'type' => 'date', 'title' => 'Дата создания', 'sort' => 1, 'mobile_pages' => '0', 'only_read' => 1, 'is_default' => 1]),
            'updated_at'            => $base(['field' => 'updated_at', 'type' => 'date', 'title' => 'Дата изменения', 'sort' => 2, 'mobile_pages' => '0', 'only_read' => 1, 'is_default' => 1]),
            'name'                  => $base(['field' => 'name', 'type' => 'text', 'title' => 'Название', 'sort' => 3, 'label_color' => '#487304ff', 'details' => '{"options":{"":null}}', 'module' => '["logistic"]', 'module_section_id' => $mod, 'is_default' => 1, 'is_unique' => 1]),
            'photo'                 => $base(['field' => 'photo', 'type' => 'file', 'title' => 'Фото', 'sort' => 4, 'roles_read' => '[4,18]', 'roles_write' => '[4,18]', 'is_remove' => 1, 'is_default' => 1]),
            'service_time'          => $base(['field' => 'service_time', 'type' => 'number', 'title' => 'Время обслуживания (мин)', 'sort' => 7]),
            'address'               => $base(['field' => 'address', 'type' => 'address', 'title' => 'Адрес', 'sort' => 7, 'module' => '["logistic"]', 'module_section_id' => $mod]),
            'phone'                 => $base(['field' => 'phone', 'type' => 'text', 'title' => 'Телефон', 'sort' => 8, 'module' => '["logistic"]', 'module_section_id' => $mod]),
            'time'                  => $base(['field' => 'time', 'type' => 'text', 'title' => 'Окно', 'sort' => 8, 'module' => '["logistic"]', 'module_section_id' => $mod]),
            'car_requirements'      => $base(['field' => 'car_requirements', 'type' => 'select_dropdown', 'title' => 'Требования к машине', 'sort' => 9, 'is_plural' => 1, 'details' => '{"options":[{"label":"Гидролифт","value":0},{"label":"Манипулятор","value":1},{"label":"Ручная","value":2}]}']),
            'employee_requirements' => $base(['field' => 'employee_requirements', 'type' => 'select_dropdown', 'title' => 'Требования к сотруднику', 'sort' => 10, 'is_plural' => 1, 'details' => '{"options":[{"label":"Гражданство РФ","value":0}]}']),
            'client_id'             => $base(['field' => 'client_id', 'type' => 'relation', 'title' => 'Клиент', 'sort' => 11, 'is_plural' => 1, 'details' => '{"table": "clients"}', 'is_link' => 1, 'relation_table' => 'clients', 'related_field' => 'task_id']),
            'user_id'               => $base(['field' => 'user_id', 'type' => 'relation', 'title' => 'Ответственный', 'sort' => 12, 'required' => 1, 'details' => '{"table":"users"}', 'is_link' => 1, 'relation_table' => 'users', 'related_field' => 'task_id', 'is_inactive' => 1]),
            'comment'               => $base(['field' => 'comment', 'type' => 'text', 'title' => 'Примечание', 'sort' => 50]),
            'contact'               => $base(['field' => 'contact', 'type' => 'text', 'title' => 'Контактное лицо', 'sort' => 51]),
        ];
    }

    private function menuJson(): string
    {
        return json_encode([
            ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
            [
                'title' => 'Модули', 'tab' => 'modules', 'sort' => 1, 'enabled' => 1, 'id' => 1,
                'childs' => [
                    ['title' => 'Логистика', 'sort' => 1, 'enabled' => 1, 'id' => 0, 'alias' => 'logistic'],
                ],
                'component' => ['name' => 'AsyncComponentWrapper'],
                'roles_read' => [], 'has_roles_read' => false,
            ],
            ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
            ['title' => 'Клиент', 'tab' => 'client_id', 'slug' => 'clients', 'sort' => 3, 'enabled' => 1, 'id' => 4],
        ], JSON_UNESCAPED_SLASHES);
    }

    private function ensureTable(ConnectionInterface $db): void
    {
        if ($this->option('recreate-table')) {
            $db->statement('DROP TABLE IF EXISTS `warehouses`');
        }
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `choosed_at` timestamp NULL DEFAULT NULL,
  `address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `name` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT NULL,
  `client_id` varchar(255) DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `car_requirements` text DEFAULT NULL,
  `employee_requirements` text DEFAULT NULL,
  `service_time` int(11) DEFAULT 0,
  `phone` text DEFAULT NULL,
  `time` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `contact` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
}
