<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use App\Models\Tenant;

/**
 * Устанавливает сущность «Справочник адресов» (addresses) в портал.
 *
 * Что ставит (метаданные сущности, живут в каждой тенант-БД и копируются в
 * новые порталы из admin_seeds функцией TenantService::create):
 *   - data_types        — сама сущность addresses (модель App\Models\Address);
 *   - field_sections    — два раздела: «Информация» и «Используемые поля в модуле»;
 *   - data_rows         — поля сущности (клонируются 1-в-1 из logistic_tasks);
 *   - sidebar_items     — пункт сайдбара «Справочник адресов»;
 *   - settings(menu)    — меню вкладок карточки (как у logistic_tasks, без «Товары и услуги»).
 * Плюс гарантирует наличие таблицы addresses (схему задаёт миграция
 * database/migrations/tenant/..._create_addresses_table.php).
 *
 * Команда ИДЕМПОТЕНТНА: повторный запуск сначала удаляет ранее поставленные
 * метаданные addresses и ставит заново («перезаписать»).
 *
 * Порядок выкатки (как в задаче):
 *   1) php artisan entity:install-addresses seeds            # база-шаблон admin_seeds
 *   2) php artisan entity:install-addresses admin_test4      # тестовый портал — проверка
 *   3) php artisan entity:install-addresses all-tenants      # все существующие порталы
 *
 * Таблицу для существующих порталов добавляет `php artisan tenants:migrate`.
 */
class InstallAddressesEntity extends Command
{
    protected $signature = 'entity:install-addresses
        {target=seeds : seeds | all-tenants | <tenant_id> (например admin_test4)}
        {--recreate-table : пересоздать таблицу addresses (DROP + CREATE), иначе только создать если нет}';

    protected $description = 'Установить сущность «Справочник адресов» (addresses) в admin_seeds / тенант / все тенанты';

    /** Поля сущности — берём из logistic_tasks (и/или из fallback ниже). */
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

        // конкретный тенант по id. Тенант идентифицируется именем портала
        // (например test4), а имя БД = prefix + id (admin_test4). Поэтому если
        // передали имя БД — отрезаем префикс из конфига tenancy.
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

        // --- идемпотентность: чистим прошлую установку addresses ---
        $oldTypeIds = $db->table('data_types')
            ->where('name', 'addresses')->orWhere('slug', 'addresses')
            ->pluck('id');
        if ($oldTypeIds->isNotEmpty()) {
            $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->delete();
            $db->table('data_types')->whereIn('id', $oldTypeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'addresses')->delete();
        $db->table('sidebar_items')->where('slug', 'addresses')->delete();
        $db->table('settings')->where('entity', 'addresses')->where('type', 'menu')->delete();

        $now = now();

        // --- 1. data_types: сама сущность ---
        $typeId = $db->table('data_types')->insertGetId([
            'name'                 => 'addresses',
            'slug'                 => 'addresses',
            'title_singular'       => 'Задача',
            'title_plural'         => 'Библиотека задач',
            'icon'                 => null,
            'model_name'           => 'App\\Models\\Address',
            'policy_name'          => null,
            'controller'           => null,
            'description'          => null,
            'generate_permissions' => 1,
            'server_side'          => 0,
            'details'              => null,
            'created_at'           => $now,
            'updated_at'           => $now,
            'color'                => '#989BFF',
            'menu'                 => null,
            'enable'               => 1,
            'slug_singular'        => 'address',
            'hidden'               => 0,
            'empty_text'           => null,
        ]);

        // --- 2. field_sections: два раздела ---
        $infoSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Информация', 'domain_key' => null, 'page' => 'addresses',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
            'column_id' => 1, 'module' => null, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
        ]);
        $moduleSecId = $db->table('field_sections')->insertGetId([
            'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => 'addresses',
            'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
            'column_id' => 1, 'module' => 'logistic', 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
        ]);

        // --- 3. data_rows: поля (клон из logistic_tasks + fallback) ---
        $this->installDataRows($db, $typeId, $infoSecId, $moduleSecId);

        // --- 4. sidebar_items: пункт сайдбара ---
        $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
        $db->table('sidebar_items')->insert([
            'created_at' => $now, 'updated_at' => $now,
            'name' => 'Библиотека задач', 'slug' => 'addresses',
            'sort' => 0, 'link' => '/objects/addresses',
            '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
            'is_hidden' => 0, 'enabled' => 1,
        ]);

        // --- 5. settings(menu): меню карточки ---
        $db->table('settings')->insert([
            'key' => 'menu', 'display_name' => null, 'value' => $this->menuJson(),
            'type' => 'menu', 'entity' => 'addresses', 'user_id' => null,
        ]);

        $this->line("    [{$label}] data_type={$typeId}, sections=[{$infoSecId},{$moduleSecId}]");
    }

    /**
     * Клонирует data_rows нужных полей из logistic_tasks, перенаправляя
     * data_type_id / section_id / module_section_id на новую сущность.
     * Для полей, которых вдруг нет в logistic_tasks, использует fallback.
     */
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
                // если поле было «используется в модуле» — указываем модульный раздел адресов
                $arr['module_section_id'] = $this->isNonEmptyJsonArray($arr['module_section_id'] ?? null)
                    ? '['.$moduleSecId.']'
                    : ($arr['module_section_id'] ?? null);
                $db->table('data_rows')->insert($arr);
                $cloned[$row->field] = true;
            }
        }

        // fallback для отсутствующих полей
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

    /** Базовая строка data_rows со значениями по умолчанию. */
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

    /** Резервные определения полей (если logistic_tasks недоступен). */
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
            'time'                  => $base(['field' => 'time', 'type' => 'text', 'title' => 'Окно', 'sort' => 8, 'module' => '["logistic"]', 'module_section_id' => $mod, 'mask' => '##:## - ##:##']),
            'car_requirements'      => $base(['field' => 'car_requirements', 'type' => 'select_dropdown', 'title' => 'Требования к машине', 'sort' => 9, 'is_plural' => 1, 'details' => '{"options":[{"label":"Гидролифт","value":0},{"label":"Манипулятор","value":1},{"label":"Ручная","value":2}]}']),
            'employee_requirements' => $base(['field' => 'employee_requirements', 'type' => 'select_dropdown', 'title' => 'Требования к сотруднику', 'sort' => 10, 'is_plural' => 1, 'details' => '{"options":[{"label":"Гражданство РФ","value":0}]}']),
            'client_id'             => $base(['field' => 'client_id', 'type' => 'relation', 'title' => 'Клиент', 'sort' => 11, 'is_plural' => 1, 'details' => '{"table": "clients"}', 'is_link' => 1, 'relation_table' => 'clients', 'related_field' => 'task_id']),
            'user_id'               => $base(['field' => 'user_id', 'type' => 'relation', 'title' => 'Ответственный', 'sort' => 12, 'required' => 1, 'details' => '{"table":"users"}', 'is_link' => 1, 'relation_table' => 'users', 'related_field' => 'task_id', 'is_inactive' => 1]),
            'comment'               => $base(['field' => 'comment', 'type' => 'text', 'title' => 'Примечание', 'sort' => 50]),
            'contact'               => $base(['field' => 'contact', 'type' => 'text', 'title' => 'Контактное лицо', 'sort' => 51]),
        ];
    }

    /** Меню карточки сущности (как у logistic_tasks, без вкладки «Товары и услуги»). */
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

    /** Гарантирует наличие таблицы addresses на данном соединении. */
    private function ensureTable(ConnectionInterface $db): void
    {
        if ($this->option('recreate-table')) {
            $db->statement('DROP TABLE IF EXISTS `addresses`');
        }
        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `addresses` (
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
