<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use App\Models\Tenant;

class SyncLogisticModuleFields extends Command
{
    protected $signature = 'logistic:sync-module-fields
        {target=all-tenants : seeds | all-tenants | <tenant_id> (например avixo)}';

    protected $description = 'Сверить и доделать вкладку «Модули» → «Логистика» в деталке сущностей логистики (секция, привязка полей, меню)';

    private function entitySpecs(): array
    {
        return [
            'routes' => [
                ['fields' => ['name'], 'titles' => ['Название']],
                ['fields' => ['date'], 'titles' => ['Дата доставки', 'Дата']],
                ['fields' => ['weight'], 'titles' => ['Вес маршрута', 'Вес, кг', 'Вес']],
                ['fields' => ['volume'], 'titles' => ['Объем маршрута', 'Объем, л', 'Объем']],
                ['fields' => ['loading_time'], 'titles' => ['Начало маршрута', 'Время загрузки']],
                ['fields' => ['time'], 'titles' => ['Время маршрута']],
                ['fields' => ['mileage'], 'titles' => ['Пробег маршрута', 'Пробег, км']],
                ['fields' => ['reserve_for_delivery'], 'titles' => ['Заложено на доставку', 'Заложено на доставку, руб']],
                ['fields' => ['delivery_price'], 'titles' => ['Цена доставки', 'Цена доставки, руб']],
                ['fields' => ['mileage_cost'], 'titles' => ['Общий расход за километраж']],
                ['fields' => ['delivery_cost'], 'titles' => ['Стоимость доставки']],
                ['fields' => ['task_id'], 'titles' => ['Задачи в маршруте', 'Задачи']],
                ['fields' => ['car_id'], 'titles' => ['Автопарк', 'Машина']],
                ['fields' => ['employee_id'], 'titles' => ['Сотрудник']],
            ],
            'companies' => [
                ['fields' => ['name'], 'titles' => ['Название']],
            ],
            'cars' => [
                ['fields' => ['name'], 'titles' => ['Название']],
                ['fields' => ['color'], 'titles' => ['Цвет машины', 'Цвет']],
                ['fields' => ['service_time'], 'titles' => ['Время обслуживания']],
                ['fields' => ['requirements'], 'titles' => ['Поддерживаемые требования к машине', 'Требования к машине']],
                ['fields' => ['company_id'], 'titles' => ['Компания']],
                ['fields' => ['employee_id'], 'titles' => ['Сотрудник']],
                ['fields' => ['weight_min'], 'titles' => ['Грузоподъемность, от', 'Грузоподьемность, от']],
                ['fields' => ['weight_max'], 'titles' => ['Грузоподъемность, до', 'Грузоподьемность, до']],
                ['fields' => ['volume_min'], 'titles' => ['Объем, от']],
                ['fields' => ['volume_max'], 'titles' => ['Объем, до']],
                ['fields' => ['price_per_km'], 'titles' => ['Стоимость 1 км, руб', 'Стоимость 1 км']],
            ],
            'employees' => [
                ['fields' => ['name'], 'titles' => ['Название']],
                ['fields' => ['color'], 'titles' => ['Цвет']],
                ['fields' => ['employee_requirements'], 'titles' => ['Поддерживаемые требования к сотруднику', 'Требования к сотруднику']],
                ['fields' => ['company_id'], 'titles' => ['Компания']],
                ['fields' => ['car_id'], 'titles' => ['Автопарк']],
            ],
            'logistic_tasks' => [
                ['fields' => ['name'], 'titles' => ['Название']],
                ['fields' => ['delivery_date'], 'titles' => ['Дата доставки']],
                ['fields' => ['point_status'], 'titles' => ['Статус точки']],
                ['fields' => ['weight'], 'titles' => ['Вес', 'Вес, кг']],
                ['fields' => ['volume'], 'titles' => ['Объем', 'Обьем', 'Объем, л']],
                ['fields' => ['address'], 'titles' => ['Адрес']],
                ['fields' => ['car_requirements'], 'titles' => ['Требования к машине']],
                ['fields' => ['employee_requirements'], 'titles' => ['Требования к сотруднику']],
                ['fields' => ['time'], 'titles' => ['Окно']],
                ['fields' => ['service_time'], 'titles' => ['Время обслуживания']],
                ['fields' => ['delivery_price'], 'titles' => ['Цена доставки', 'Цена доставки, руб']],
                ['fields' => ['plan_time'], 'titles' => ['План. время прибытия', 'Планируемое время прибытия']],
            ],
            'warehouses' => [
                ['fields' => ['name'], 'titles' => ['Название']],
                ['fields' => ['weight'], 'titles' => ['Вес', 'Вес, кг']],
                ['fields' => ['address'], 'titles' => ['Адрес']],
                ['fields' => ['car_requirements'], 'titles' => ['Требования к машине']],
                ['fields' => ['employee_requirements'], 'titles' => ['Требования к сотруднику']],
                ['fields' => ['time'], 'titles' => ['Окно']],
                ['fields' => ['service_time'], 'titles' => ['Время обслуживания']],
                ['fields' => ['delivery_price'], 'titles' => ['Цена доставки', 'Цена доставки, руб']],
                ['fields' => ['phone'], 'titles' => ['Телефон']],
                ['fields' => ['contact'], 'titles' => ['Контактное лицо']],
                ['fields' => ['comment'], 'titles' => ['Примечание']],
            ],
        ];
    }

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->info('Синхронизация базы-шаблона admin_seeds (connection: seeds)…');
            $this->sync(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $tenants = Tenant::get();
            $this->info('Синхронизация всех порталов: '.$tenants->count());
            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        $this->sync(\DB::connection(), (string) $tenant->id);
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
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $this->info("Синхронизация портала {$target}…");
        $tenant->run(function () use ($target) {
            $this->sync(\DB::connection(), (string) $target);
        });
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function sync(ConnectionInterface $db, string $label): void
    {
        $now = now();

        $this->ensureLogisticModuleRow($db, $label, $now);

        foreach ($this->entitySpecs() as $slug => $specs) {
            $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                $this->warn("    [{$label}] {$slug}: сущность не найдена — пропуск".($slug === 'warehouses' ? ' (сначала entity:install-warehouses)' : ''));
                continue;
            }

            $sectionId = $db->table('field_sections')
                ->where('page', $slug)
                ->where('module', 'logistic')
                ->value('id');
            if (!$sectionId) {
                $sectionId = $db->table('field_sections')->insertGetId([
                    'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => $slug,
                    'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
                    'column_id' => 1, 'module' => 'logistic', 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
                ]);
                $this->line("    [{$label}] {$slug}: создана секция модуля #{$sectionId}");
            }

            $rows = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('is_remove', 0)
                ->get(['id', 'field', 'title', 'module', 'module_section_id']);

            $attached = 0;
            foreach ($specs as $spec) {
                $row = $this->matchRow($rows, $spec);
                if (!$row) {
                    $this->warn("    [{$label}] {$slug}: поле не найдено — ".implode('/', $spec['titles'])." (".implode('/', $spec['fields']).")");
                    continue;
                }
                if ($this->attachToModule($db, $row, $sectionId)) {
                    $attached++;
                }
            }

            $this->syncMenu($db, $slug);

            $db->table('local_cache')->where('url', "fields/{$slug}")->update(['updated_at' => $now]);

            $this->line("    [{$label}] {$slug}: привязано новых полей — {$attached}");
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    private function normalize(?string $title): string
    {
        $t = mb_strtolower(trim((string) $title));
        $t = str_replace(['ё', 'ъ'], ['е', 'ь'], $t);
        return preg_replace('/\s+/u', ' ', $t);
    }

    private function matchRow($rows, array $spec)
    {
        foreach ($spec['fields'] as $field) {
            $found = $rows->first(fn ($r) => $r->field === $field);
            if ($found) {
                return $found;
            }
        }
        $titles = array_map(fn ($t) => $this->normalize($t), $spec['titles']);
        return $rows->first(fn ($r) => in_array($this->normalize($r->title), $titles, true));
    }

    private function attachToModule(ConnectionInterface $db, $row, int $sectionId): bool
    {
        $modules = $this->decodeJsonList($row->module);
        $sections = array_map('intval', $this->decodeJsonList($row->module_section_id));

        $changed = false;
        if (!in_array('logistic', $modules, true)) {
            $modules[] = 'logistic';
            $changed = true;
        }
        if (!in_array($sectionId, $sections, true)) {
            $sections[] = $sectionId;
            $changed = true;
        }

        if ($changed) {
            $db->table('data_rows')->where('id', $row->id)->update([
                'module' => json_encode(array_values($modules)),
                'module_section_id' => json_encode(array_values($sections)),
            ]);
        }

        return $changed;
    }

    private function decodeJsonList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($v) => $v !== null && $v !== ''));
        }
        return [(string) $value];
    }

    private function syncMenu(ConnectionInterface $db, string $slug): void
    {
        $logisticChild = ['title' => 'Логистика', 'sort' => 1, 'enabled' => 1, 'id' => 0, 'alias' => 'logistic'];

        $menu = $db->table('settings')->where(['type' => 'menu', 'entity' => $slug])->first();

        if (!$menu) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    [
                        'title' => 'Модули', 'tab' => 'modules', 'sort' => 1, 'enabled' => 1, 'id' => 1,
                        'childs' => [$logisticChild],
                        'component' => ['name' => 'AsyncComponentWrapper'],
                        'roles_read' => [], 'has_roles_read' => false,
                    ],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => $slug, 'user_id' => null,
            ]);
            return;
        }

        $tabs = json_decode($menu->value, true);
        if (!is_array($tabs)) {
            $tabs = [];
        }

        $modulesKey = null;
        foreach ($tabs as $k => $tab) {
            if (($tab['tab'] ?? null) === 'modules') {
                $modulesKey = $k;
                break;
            }
        }

        if ($modulesKey === null) {
            $maxSort = 0;
            $maxId = 0;
            foreach ($tabs as $tab) {
                $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                $maxId = max($maxId, (int) ($tab['id'] ?? 0));
            }
            $tabs[] = [
                'title' => 'Модули', 'tab' => 'modules', 'sort' => $maxSort + 1, 'enabled' => 1, 'id' => $maxId + 1,
                'childs' => [$logisticChild],
                'component' => ['name' => 'AsyncComponentWrapper'],
                'roles_read' => [], 'has_roles_read' => false,
            ];
        } else {
            $tabs[$modulesKey]['enabled'] = 1;
            $childs = $tabs[$modulesKey]['childs'] ?? [];
            $hasLogistic = false;
            foreach ($childs as $ck => $child) {
                if (($child['alias'] ?? null) === 'logistic') {
                    $childs[$ck]['enabled'] = 1;
                    $hasLogistic = true;
                }
            }
            if (!$hasLogistic) {
                $childs[] = $logisticChild;
            }
            $tabs[$modulesKey]['childs'] = array_values($childs);
        }

        $db->table('settings')->where('id', $menu->id)->update([
            'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function ensureLogisticModuleRow(ConnectionInterface $db, string $label, $now): void
    {
        if ($db->table('modules')->where('slug', 'logistic')->exists()) {
            return;
        }

        $seed = null;
        try {
            if ($db->getName() !== 'seeds') {
                $seed = \DB::connection('seeds')->table('modules')->where('slug', 'logistic')->first();
            }
        } catch (\Throwable $e) {
        }

        $db->table('modules')->insert([
            'name' => $seed->name ?? 'Логистика',
            'config' => $seed->config ?? '',
            'entities' => $seed->entities ?? '',
            'slug' => 'logistic',
            'enabled' => 1,
        ]);
        $this->line("    [{$label}] добавлена запись модуля logistic в modules");
    }
}
