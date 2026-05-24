<?php

namespace App\Services;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\Permission;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnalyticsService
{
    protected $settings;
    protected $tenantId;

    // Константы для магических чисел
    protected const STATUS_SUCCESS = 'success';
    protected const STATUS_INVOICE_PAID = 1482; // ID статуса оплаченного счета
    protected const COLOR_LIST_ID = 2844;       // ID списка цветов для аккаунтов
    protected const ACCOUNT_DEFAULT_COLOR = 1495;
    protected const DASHBOARD_ORDER_TYPE = 'dashboard_order'; // Тип записи для хранения порядка

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    protected function globalSettings()
    {
        if (!$this->settings) {
            if ($this->tenantId) {
                tenancy()->initialize($this->tenantId);
            }
            $this->settings = app('settings');
        }
        return $this->settings;
    }

    /**
     * Проверка прав и возврат массива прав
     */
    protected function checkPermissions()
    {
        $user = Auth::user();
        
        // Админ имеет все права
        if ($user->is_admin) {
            return [
                'read_p' => 'A',
                'create_p' => 'A',
                'update_p' => 'A',
                'delete_p' => 'A',
                'export_p' => 'A',
                'import_p' => 'A'
            ];
        }

        static $dataTypeId = null;
        if (!$dataTypeId) {
            $dataTypeId = DB::table('data_types')->where('slug', 'analytics')->value('id');
        }

        $permissions = [];
        
        if ($user->role_id) {
            $permissions = $user->role->permissions()->select([
                'read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'
            ])->where('entity_id', $dataTypeId)->first();
            
            if (!$permissions) {
                $this->initializePermissions($user->role_id, $dataTypeId);
                
                $permissions = $user->role->permissions()->select([
                    'read_p', 'create_p', 'update_p', 'delete_p', 'export_p', 'import_p'
                ])->where('entity_id', $dataTypeId)->first();
            }
            
            $permissions = $permissions ? $permissions->toArray() : [];
        }
            
        if ((isset($permissions['read_p']) && $permissions['read_p'] == 'N') || empty($permissions)) {
            abort(403, 'Forbidden');
        }
        
        return $permissions;
    }

    protected function initializePermissions($roleId, $dataTypeId)
    {
        $data_types = DB::table('data_types')->where('enable', 1)->where('hidden', 0)->pluck('id');
        $existing_permissions = Permission::whereNotNull('entity_id')
            ->where('role_id', $roleId)
            ->pluck('entity_id')
            ->toArray();

        $inserts = [];
        foreach ($data_types as $entity_id) {
            if (!in_array($entity_id, $existing_permissions)) {
                $inserts[] = ['entity_id' => $entity_id, 'role_id' => $roleId];
            }
        }

        if (!empty($inserts)) {
            DB::table('permissions')->insert($inserts);
        }
    }

    protected function getAnalyticRecord($type, $userId)
    {
        $res = DB::table('analytic')->where('user_id', $userId)->where('type', $type)->first();
        if (!$res) {
            $res = DB::table('analytic')->where('user_id', null)->first();
        }
        return $res;
    }

    protected function updateAnalyticRecord($type, $userId, $data)
    {
        $exists = DB::table('analytic')->where('user_id', $userId)->where('type', $type)->exists();

        if ($exists) {
            DB::table('analytic')->where('user_id', $userId)->where('type', $type)->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['type'] = $type;
            if (isset($data['active_rows']) && is_array($data['active_rows'])) {
                $data['active_rows'] = json_encode($data['active_rows']);
            }
            DB::table('analytic')->insert($data);
        }
    }

    protected function getColorMap($options)
    {
        $map = [];
        if (is_array($options)) {
            foreach ($options as $option) {
                $val = is_array($option) ? ($option['value'] ?? null) : ($option->value ?? null);
                $label = is_array($option) ? ($option['label'] ?? null) : ($option->label ?? null);
                
                $color = null;
                if (is_array($label)) {
                    $color = $label['color'] ?? null;
                } elseif (is_object($label)) {
                    $color = $label->color ?? null;
                }
                
                if ($val && $color) {
                    $map[$val] = $color;
                }
            }
        }
        return $map;
    }

    protected function getCommonParams($request, $type)
    {
        $res = $this->getAnalyticRecord($type, Auth::id());
        
        $defaults = [
            'entity' => $res->entity ?? null,
            'field' => $res->field ?? null,
            'group_by' => $res->group_by ?? null,
            'period_start' => $res->period_start ?? null,
            'period_end' => $res->period_end ?? null,
            'acondition' => $res->acondition ?? null,
            'sort_field' => $res->sort_field ?? null,
            'sort_order' => $res->sort_order ?? null,
            'active_rows' => isset($res->active_rows) ? json_decode($res->active_rows, true) : [],
            'config' => isset($res->config) ? json_decode($res->config, true) : [],
        ];

        $params = [
            'entity' => $request->entity ?: $defaults['entity'],
            'field' => $request->field ?: $defaults['field'],
            'group_by' => $request->group_by ?: $defaults['group_by'],
            'period_start' => $request->period['start'] ?? $defaults['period_start'],
            'period_end' => $request->period['end'] ?? $defaults['period_end'],
            'condition' => $request->condition ?: $defaults['acondition'],
            'sort_field' => $request->sort_field ?: $defaults['sort_field'],
            'sort_order' => $request->sort_order ?: $defaults['sort_order'],
            'active_rows' => $defaults['active_rows'],
            'config' => $defaults['config'],
        ];

        if (($request->entity && $request->entity != $defaults['entity']) ||
            ($request->field && $request->field != $defaults['field']) ||
            ($request->group_by && $request->group_by != $defaults['group_by']) ||
            ($request->condition && $request->condition != $defaults['acondition'])) {
            $params['active_rows'] = [];
        }

        return $params;
    }

    /**
     * Основной метод формирования ответа в нужном формате
     */
    protected function finalizeAnalytics($type, $request, $params, $resultMetadata, $legendData, $tableRows = null)
    {
        $active_rows = $params['active_rows'];
        $permissions = $this->checkPermissions();

        // 1. Сортировка Легенды (для графика)
        $collection = collect($legendData);
        if ($params['sort_field']) {
            if ($params['sort_order'] == 'desc') {
                $collection = $collection->sortByDesc($params['sort_field']);
            } elseif ($params['sort_field'] == 'sum') {
                $collection = $collection->sortBy($params['sort_field']);
            }
            $legendData = $collection->values()->toArray();
        }

        // 2. Применение активных строк к Легенде
        foreach ($legendData as $i => $object) {
            $isActive = true;
            if (isset($object['id']) && !in_array($object['id'], $active_rows) && !empty($active_rows) && !$request->enable_rows) {
                $isActive = false;
            }
            $legendData[$i]['active'] = $isActive ? 1 : 0;
        }

        // 3. Подготовка данных для Таблицы (List)
        if ($tableRows === null) {
            $isGroupedContext = (count($legendData) > 1) || (!empty($params['group_by']) && !empty($request->group_by));
            
            if ($isGroupedContext) {
                 // Берем легенду, убираем вложенный data
                 $tableRows = array_map(function($item) {
                     if (is_array($item)) {
                         if(isset($item['data'])) unset($item['data']);
                     }
                     return $item;
                 }, $legendData);
            } elseif (count($legendData) === 1 && isset($legendData[0]['data']) && is_array($legendData[0]['data'])) {
                 $tableRows = [];
                 $baseObject = $legendData[0];
                 foreach ($baseObject['data'] as $idx => $point) {
                     $tableRows[] = [
                         'date' => $point[0] ?? null,
                         'sum' => $point[1] ?? 0,
                         'active' => 1,
                         'id' => $idx + 1
                     ];
                 }
            } else {
                 $tableRows = [];
            }
        }

        // 4. Пагинация таблицы
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 25);
        $total = count($tableRows);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($tableRows, $offset, $perPage);
        $lastPage = max((int) ceil($total / $perPage), 1);

        // 5. Конфигурация колонок таблицы
        $isGroupAll = ($params['group_by'] === 'all');
    
        $isGroupedTable = !empty($paginatedItems) && isset($paginatedItems[0]['name']);
        if (empty($paginatedItems)) {
             $isGroupedTable = (count($legendData) > 1) || (!empty($params['group_by']) && !empty($request->group_by) && $params['group_by'] !== 'all');
        }

        // Передаем $isGroupAll в getDefaultTableColumns
        $defaultColumns = $this->getDefaultTableColumns($type, $isGroupedTable, $isGroupAll);
        $tableConfig = $this->getTableConfig($type, $defaultColumns);

        // 6. Обновление записи
        $this->updateAnalyticRecord($type, Auth::id(), [
            'entity' => $params['entity'],
            'field' => $params['field'],
            'group_by' => $params['group_by'],
            'period_start' => $params['period_start'],
            'period_end' => $params['period_end'],
            'acondition' => $params['condition'],
            'sort_field' => $params['sort_field'],
            'sort_order' => $params['sort_order'],
            'active_rows' => $active_rows
        ]);

        return [
            'title' => $resultMetadata['title'] ?? null,
            'legend' => $legendData, 
            'list' => [
                'count' => count($paginatedItems),
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'data' => $paginatedItems,
                'buttons' => [],
                'sort_field' => $params['sort_field'] ?? 'id',
                'sort_order' => $params['sort_order'] ?? 'desc',
                'restrictions' => [],
                'empty_text' => 'Нет данных',
                'category_slug' => null,
                'guide' => null
            ],
            'table' => $tableConfig,
            'fields' => null,
            'entities' => null,
            'filters' => null,
            'categories' => null,
            'permissions' => $permissions,
            'tabs' => [],
            'config' => $params['config']
        ];
    }

    protected function getTableConfig($key, $defaultColumns)
    {
        $user = Auth::user();
        $tables = Table::get($key);//$user->tables ? json_decode($user->tables, true) : [];
        info('tables');
        info($key);
        info($tables);
        $config = $defaultColumns;

        if (isset($tables[$key])) {
            info($tables);
            $config = isset($tables[$key]['fields']) ? $tables[$key]['fields'] : $tables[$key];
        } else {
            $role = $user->role;
            if ($role && $role->tables) {
                $roleTables = json_decode($role->tables, true);
                if (isset($roleTables[$key])) {
                    $config = $roleTables[$key];
                }
            } else {
                $settings = DB::table('settings')->where('key', 'tables')->first();
                if ($settings && $settings->value) {
                    $globalTables = json_decode($settings->value, true);
                    if (isset($globalTables[$key])) {
                        $config = $globalTables[$key];
                    }
                }
            }
        }

        // Инъекция localOptions из defaultColumns в config
        $defaultColorOptions = [];
        foreach($defaultColumns as $dCol) {
            if (in_array($dCol['key'], ['color', 'color_status']) && !empty($dCol['localOptions'])) {
                $defaultColorOptions = $dCol['localOptions'];
                break;
            }
        }

        if (!empty($defaultColorOptions)) {
            foreach($config as &$col) {
                if (in_array($col['key'], ['color', 'color_status'])) {
                    $col['localOptions'] = $defaultColorOptions;
                }
            }
        }

        return $config;
    }

    protected function getDefaultTableColumns($type, $isGrouped, $isGroupAll = false)
    {
        $activeCol = [
            "id" => 1,
            "title" => "Отображение",
            "key" => "active",
            "width" => "20px",
            "enabled" => true,
            "type" => "checkbox",
            "can_edit" => 0,
            "read_only" => 0,
            "visible_always" => 1,
        ];

        $sumCol = [
            "id" => 4,
            "title" => "Сумма",
            "key" => "sum",
            "width" => "150px",
            "enabled" => true,
            "type" => "number",
            "read_only" => 1,
            "visible_always" => 1,
            "unit" => "руб."
        ];

        $options = $meta['color_options'] ?? [];

        if ($isGrouped) {
            $columns = [$activeCol];

            if ($type === 'gibdd') {
                $columns[] = [
                    "id" => 2,
                    "title" => "Цвет",
                    "key" => "color_status",
                    "width" => "40px",
                    "enabled" => true,
                    "type" => "status",
                    "read_only" => 1,
                    "localOptions" => $options
                ];
            } elseif (in_array($type, ['income', 'income_moneta', 'expenses', 'all_income', 'expense_moneta', 'accountIncomes'])) {
                $columns[] = [
                    "id" => 2,
                    "title" => "Цвет",
                    "key" => "color",
                    "width" => "40px",
                    "enabled" => true,
                    "type" => "color",
                    "read_only" => 1,
                    "localOptions" => $options
                ];
            }

            $columns[] = [
                "id" => 3,
                "title" => "Название",
                "key" => "name",
                "width" => "300px",
                "enabled" => true,
                "type" => "text",
                "read_only" => 1,
                "visible_always" => 1
            ];
            
            $columns[] = $sumCol;

            $columns[] = [
                "id" => 999, 
                "title" => "ID",
                "key" => "id",
                "width" => "0px",
                "enabled" => true,
                "type" => "text",
                "read_only" => 1,
                "is_hidden" => 1,
                "visible_always" => 0
            ];

            return $columns;
        }

        return [
            $activeCol,
            [
                "id" => 5,
                "title" => "Дата",
                "key" => "date",
                "width" => "200px",
                "enabled" => true,
                "type" => $isGroupAll ? "text" : "date",
                "read_only" => 1,
                "visible_always" => 1
            ],
            $sumCol,
            [
                "id" => 999, 
                "title" => "ID",
                "key" => "id",
                "width" => "50px",
                "enabled" => true,
                "type" => "text",
                "read_only" => 1,
                "is_hidden" => 1, 
                "visible_always" => 0
            ]
        ];
    }

    protected function findOptionByValue($value, $options) {
        foreach ($options as $opt) {
            $val = is_array($opt) ? ($opt['value'] ?? null) : ($opt->value ?? null);
            if ($val == $value) {
                return $opt;
            }
        }
        return null;
    }

    public function gibdd($request)
    {
        if (!$this->isModuleEnabled('gibdd')) {
            return ['error' => 'Module gibdd is disabled'];
        }
        $settings = $this->globalSettings();
        $params = $this->getCommonParams($request, 'gibdd');

        $query = DB::table($params['entity']);
        $date_field = 'created_at';
        
        $field_chain = [];
        if (strstr($params['field'], '.')) {
            $field_chain = explode('.', $params['field']);
            $field_name = $field_chain[0];
            $field = $settings[$params['entity']]['fields'][$field_chain[0]];
        } else {
            $field = $settings[$params['entity']]['fields'][$params['field']];
            $field_name = $params['field'];
        }

        $selects = ['id', $date_field, $field->field];
        if ($params['group_by']) {
            $selects[] = $params['group_by'];
        }
        $query->select($selects);
        
        $query->orderBy($date_field)
            ->whereNotNull($field->field)
            ->whereBetween($date_field, [$params['period_start'], $params['period_end']]);

        if ($params['condition']) {
            foreach ($params['condition'] as $field_condition => $value) {
                if (strstr($field_condition, '.')) {
                     if (isset($settings[$params['entity']]['fields'][$field_chain[0] ?? ''])) {
                        $query->whereJsonContains(str_replace('.', '->', $field_condition), is_numeric($value) ? (int)$value : $value);
                    }
                } else {
                    $query->where($field_condition, $value);
                }
            }
        }

        $data = $query->get();
        $rawData = $data; 

        $relation_objects = [];
        if ($params['group_by'] && $request->group_by) {
            $data = $data->groupBy($params['group_by']);
            $groupField = $settings[$params['entity']]['fields'][$params['group_by']];
            
            if ($groupField->type == 'relation' && $groupField->relation_table) {
                $relation_objects = DB::table($groupField->relation_table)->get()->keyBy('id')->map(function($item) {
                     $arr = json_decode($item->name, true);
                     $item->name = isset($arr['value']) ? $arr['value'] : ($arr ?: $item->name);
                     return (array)$item;
                })->toArray();
            }
        } else {
            $data = $data->toArray(); 
        }
        
        if ($params['group_by'] && $request->group_by) {
            $data = $data->toArray(); 
        }

        $legend = [];
        $resultMetadata = [];
        $tableRows = [];

        if ($params['group_by'] && $request->group_by) {
            $resultMetadata['title'] = 'Штрафы в рублях по автопарку';
            
            $cars = [];
            $allColorOptions = [];
            
            if ($params['entity'] == 'fines_gibdd' && $params['group_by'] == 'car_id') {
                $carIds = [];
                foreach ($data as $k => $row) {
                    if(isset($relation_objects[$k]['id'])) $carIds[] = $relation_objects[$k]['id'];
                }
                if (!empty($carIds)) {
                    $cars = DB::table('cars')->whereIn('id', $carIds)->get()->keyBy('id');
                }
                
                foreach ($settings['cars']['fields'] as $cfield) {
                    if ($cfield->field == 'color_status') {
                        $resultMetadata['field_color_id'] = $cfield->id;
                        if (isset($settings['list_values'][$cfield->id])) {
                            $allColorOptions = $settings['list_values'][$cfield->id];
                        }
                    }
                }
            }
            
            foreach ($data as $k => $row) {
                $object = [];
                $object['name'] = isset($relation_objects[$k]) ? $relation_objects[$k]['name'] : $k;
                
                if (count($object)) {
                    $object['data'] = [];
                    $object['sum'] = 0;
                    
                    foreach ($row as $group_key => $group) {
                        $group = (array)$group;
                        $object['data'][$group_key] = [];
                        
                        foreach ($group as $field_key => $value) {
                            if ($field_key != $params['group_by'] && $field_key != 'id') {
                                $arr = json_decode($value, true);
                                $val = is_array($arr) ? ($arr['value'] ?? $value) : $value;
                                $object['data'][$group_key][] = $val;
                            }
                            
                            if ($field_key == $field_name) {
                                $arr = json_decode($value, true);
                                $val = is_array($arr) ? ($arr['value'] ?? $value) : $value;
                                $object['sum'] += (integer)$val;
                            }
                        }
                    }
                    
                    $object['id'] = $k;
                    $rowItem = $object; // Копия для таблицы (потом уберем data)

                    if (isset($cars[$relation_objects[$k]['id'] ?? 0])) {
                        $car = $cars[$relation_objects[$k]['id']];
                        $colorStatusId = (int)$car->color_status;
                        $singleOption = $this->findOptionByValue($colorStatusId, $allColorOptions);
                        
                        // Legend gets string color
                        $object['color'] = $singleOption['label']['color'] ?? null;
                        
                        // Table row gets object color_status
                        $rowItem['color_status'] = [
                            'value' => $colorStatusId,
                            'localOptions' => $singleOption ? [$singleOption] : []
                        ];
                        // Also set string color for table if needed, but table config uses color_status
                        $rowItem['color'] = $object['color']; 
                    }
                    
                    $legend[] = $object;
                    unset($rowItem['data']);
                    $tableRows[] = $rowItem;
                }
            }

        } else {
            $resultMetadata['title'] = 'Штрафы в рублях';
            $object = [
                'name' => 'Штрафы за период',
                'sum' => 0,
                'data' => [],
                'id' => 1
            ];
            
            foreach ($rawData as $k => $row) {
                $val = $row->{$field_name};
                $arr = json_decode($val, true);
                $sumVal = is_array($arr) ? ($arr['value'] ?? $val) : $val;
                $sumVal = (int)$sumVal;

                $object['data'][] = [$row->created_at, $sumVal]; 
                $object['sum'] += $sumVal;

                $tableRows[] = [
                    'id' => $row->id,
                    'date' => $row->created_at,
                    'sum' => $sumVal,
                    'active' => 1
                ];
            }
            $legend[] = $object;
        }

        return $this->finalizeAnalytics('gibdd', $request, $params, $resultMetadata, $legend, $tableRows);
    }

    public function income($request)
    {
        $settings = $this->globalSettings();
        $params = $this->getCommonParams($request, 'income');

        $expenses = Expense::select('account_id', 'created_at', 'sum')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$params['period_start'], $params['period_end']])
            ->get();
            
        $invoices = Invoice::select('account_id', 'created_at', 'sum')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$params['period_start'], $params['period_end']])
            ->get();

        $isGrouped = $params['group_by'] && $request->group_by;

        if ($isGrouped) {
            $expenses = $expenses->groupBy('account_id');
            $invoices = $invoices->groupBy('account_id');
        } else {
            $expenses = collect([0 => $expenses]);
            $invoices = collect([0 => $invoices]);
        }

        $arr = [];
        $dateFormat = 'Y-m-d H:i';

        $processCollection = function($collection, $keyName) use (&$arr, $dateFormat) {
            foreach ($collection as $account_id => $records) {
                if (!isset($arr[$account_id])) $arr[$account_id] = ['expenses' => [], 'invoices' => []];
                $arr[$account_id][$keyName] = $records->groupBy(fn($r) => $r->created_at->format($dateFormat));
            }
        };

        $processCollection($expenses, 'expenses');
        $processCollection($invoices, 'invoices');

        $data = [];
        foreach ($arr as $account_id => $records) {
            foreach ($records['expenses'] as $date => $items) {
                $data[$account_id][$date] = ($data[$account_id][$date] ?? 0) + $items->sum('sum');
            }
            foreach ($records['invoices'] as $date => $items) {
                $data[$account_id][$date] = ($data[$account_id][$date] ?? 0) - $items->sum('sum');
            }
        }

        $accounts = DB::table('accounts')->get()->keyBy('id');
        $allColorOptions = $settings['list_values'][self::COLOR_LIST_ID] ?? [];
        $legend = [];
        $tableRows = [];

        foreach ($data as $account_id => $records) {
            $item = [
                'data' => [],
                'sum' => 0,
                'id' => $account_id,
                'active' => true
            ];

            $rowItem = $item; // Copy structure

            if ($isGrouped) {
                if (!$accounts->has($account_id)) continue;
                $account = $accounts[$account_id];
                $nameArr = json_decode($account->name, true);
                $item['name'] = $nameArr['value'] ?? $account->name;
                
                $colorId = (int)$account->color_id;
                $singleOption = $this->findOptionByValue($colorId, $allColorOptions);
                
                // Legend gets string
                $item['color'] = $singleOption['label']['color'] ?? null;
                
                // Table row gets object
                $rowItem['name'] = $item['name'];
                $rowItem['color'] = [
                    'value' => $colorId,
                    'localOptions' => $singleOption ? [$singleOption] : []
                ];
            } else {
                $item['name'] = 'Чистая прибыль (общая)';
                $item['id'] = 1; 
                $rowItem = $item;
            }
            
            foreach ($records as $date => $val) {
                $item['data'][] = [$date, $val];
                $item['sum'] += $val;
            }
            
            $legend[] = $item;
            
            // Build Table Rows
            if ($isGrouped) {
                $rowItem['sum'] = $item['sum'];
                $tableRows[] = $rowItem; // Already removed data from copy logic? no, need unset
            }
        }

        $resultMetadata = [
            'title' => 'Чистая прибыль по порталам (безнал)',
            'field_color_id' => self::COLOR_LIST_ID,
        ];

        if (!$isGrouped && !empty($legend)) {
            $tableRows = [];
            foreach ($legend[0]['data'] as $idx => $point) {
                $tableRows[] = [
                    'date' => $point[0] ?? null,
                    'sum' => $point[1] ?? 0,
                    'active' => 1,
                    'id' => $idx + 1
                ];
            }
        }

        return $this->finalizeAnalytics('income', $request, $params, $resultMetadata, $legend, $tableRows);
    }

    public function incomeMoneta($request)
    {
        $settings = $this->globalSettings();
        $params = $this->getCommonParams($request, 'income_moneta');

        $data = DB::table('payments')
            ->orderBy('date')
            ->where('status', self::STATUS_SUCCESS)
            ->whereNotNull('sum')
            ->whereBetween('date', [$params['period_start'], $params['period_end']])
            ->get();
            
        $rawData = $data;

        if ($params['group_by'] && $request->group_by) {
            $data = $data->groupBy('account_id');
        } else {
            $data = collect([0 => $data]);
        }

        $accounts = DB::table('accounts')->get()->keyBy('tenant_id');
        $allColorOptions = $settings['list_values'][self::COLOR_LIST_ID] ?? [];
        $legend = [];
        $tableRows = [];
        $resultMetadata = [
            'field_color_id' => self::COLOR_LIST_ID
        ];

        if ($params['group_by'] && $request->group_by) {
            $resultMetadata['title'] = 'Чистая прибыль в рублях по порталам (монета)';
            
            foreach ($data as $k => $rows) {
                $calcSum = 0;
                $dataPoints = [];
                foreach ($rows as $row) {
                    $val = $row->amount - $row->moneta_comission - $row->sum;
                    $dataPoints[] = [$row->date, $val];
                    $calcSum += $val;
                }

                $colorId = isset($accounts[$k]) ? (int)$accounts[$k]->color_id : self::ACCOUNT_DEFAULT_COLOR;
                $singleOption = $this->findOptionByValue($colorId, $allColorOptions);

                // Legend Item
                $legend[] = [
                    'name' => $k ?: 'Без портала',
                    'data' => $dataPoints,
                    'sum' => $calcSum,
                    'id' => $k,
                    'color' => $singleOption['label']['color'] ?? null
                ];
                
                // Table Row
                $tableRows[] = [
                    'name' => $k ?: 'Без портала',
                    'sum' => $calcSum,
                    'id' => $k,
                    'active' => true,
                    'color' => [
                        'value' => $colorId,
                        'localOptions' => $singleOption ? [$singleOption] : []
                    ]
                ];
            }
        } else {
            $resultMetadata['title'] = 'Чистая прибыль в рублях (монета)';
            $rows = $data->first();
            $calcSum = 0;
            $dataPoints = [];
            
            foreach ($rawData as $row) {
                $val = $row->amount - $row->moneta_comission - $row->sum;
                $dataPoints[] = [$row->date, $val];
                $calcSum += $val;
                
                $tableRows[] = [
                    'id' => $row->id,
                    'date' => $row->date,
                    'sum' => $val,
                    'active' => 1
                ];
            }
            
            $legend[] = [
                'name' => 'Доходы за период',
                'data' => $dataPoints,
                'sum' => $calcSum,
                'id' => 1
            ];
        }

        return $this->finalizeAnalytics('income_moneta', $request, $params, $resultMetadata, $legend, $tableRows);
    }

    public function accountIncomes($request)
    {
        return $this->genericIncomeExpenseReport($request, 'expenses', 'invoices', 'Расходы по порталам (безнал)', 'Расходы за период', true);
    }

    public function allIncome($request)
    {
        return $this->genericIncomeExpenseReport($request, 'all_income', 'expenses', 'Доходы с порталов', 'Доходы за период', false);
    }

    protected function genericIncomeExpenseReport($request, $type, $tableName, $titleGrouped, $titleSingle, $checkStatus)
    {
        $settings = $this->globalSettings();
        $params = $this->getCommonParams($request, $type);

        $query = DB::table($tableName)
            ->orderBy('created_at')
            ->whereNotNull('account_id')
            ->whereNotNull('sum')
            ->whereBetween('created_at', [$params['period_start'], $params['period_end']]);

        if ($checkStatus) {
            $query->where('status', self::STATUS_INVOICE_PAID);
        }

        $data = $query->get();
        $rawData = $data;

        if ($params['group_by'] && $request->group_by) {
            $data = $data->groupBy('account_id');
        } else {
             $data = collect([0 => $data]);
        }

        $accounts = DB::table('accounts')->get()->keyBy('id');
        $allColorOptions = $settings['list_values'][self::COLOR_LIST_ID] ?? [];
        $legend = [];
        $tableRows = [];
        $resultMetadata = ['field_color_id' => self::COLOR_LIST_ID];

        if ($params['group_by'] && $request->group_by) {
            $resultMetadata['title'] = $titleGrouped;
            
            foreach ($data as $k => $rows) {
                $accountName = 'Unknown';
                $colorId = self::ACCOUNT_DEFAULT_COLOR;
                
                if (isset($accounts[$k])) {
                    $nameData = json_decode($accounts[$k]->name, true);
                    $accountName = $nameData['value'] ?? $accounts[$k]->name;
                    $colorId = (int)$accounts[$k]->color_id;
                }

                $sum = 0;
                $dataPoints = [];
                foreach ($rows as $row) {
                    $dataPoints[] = [$row->created_at, $row->sum];
                    $sum += $row->sum;
                }

                $singleOption = $this->findOptionByValue($colorId, $allColorOptions);

                $legend[] = [
                    'name' => $accountName,
                    'data' => $dataPoints,
                    'sum' => $sum,
                    'id' => $k,
                    'color' => $singleOption['label']['color'] ?? null
                ];
                
                $tableRows[] = [
                    'name' => $accountName,
                    'sum' => $sum,
                    'id' => $k,
                    'active' => true,
                    'color' => [
                        'value' => $colorId,
                        'localOptions' => $singleOption ? [$singleOption] : []
                    ]
                ];
            }
        } else {
            $resultMetadata['title'] = $titleSingle;
            $sum = 0;
            $dataPoints = [];
            
            foreach ($rawData as $row) {
                $dataPoints[] = [$row->created_at, $row->sum];
                $sum += $row->sum;
                
                $tableRows[] = [
                    'id' => $row->id,
                    'date' => $row->created_at,
                    'sum' => $row->sum,
                    'active' => 1
                ];
            }
            
            $legend[] = [
                'name' => $titleSingle,
                'data' => $dataPoints,
                'sum' => $sum,
                'id' => 1
            ];
        }

        return $this->finalizeAnalytics($type, $request, $params, $resultMetadata, $legend, $tableRows);
    }

    public function expenseMoneta($request)
    {
        $settings = $this->globalSettings();
        $params = $this->getCommonParams($request, 'expense_moneta');

        $data = DB::table('payments')
            ->orderBy('date')
            ->where('status', self::STATUS_SUCCESS)
            ->whereNotNull('account_id')
            ->whereNotNull('moneta_comission')
            ->whereBetween('date', [$params['period_start'], $params['period_end']])
            ->get();
            
        $rawData = $data;

        if ($params['group_by'] && $request->group_by) {
            $data = $data->groupBy('account_id');
        } else {
            $data = collect([0 => $data]);
        }

        $accounts = DB::table('accounts')->get()->keyBy('tenant_id');
        $allColorOptions = $settings['list_values'][self::COLOR_LIST_ID] ?? [];
        $legend = [];
        $tableRows = [];
        $resultMetadata = ['field_color_id' => self::COLOR_LIST_ID];

        if ($params['group_by'] && $request->group_by) {
            $resultMetadata['title'] = 'Расходы в рублях по порталам (монета)';
            
            foreach ($data as $k => $rows) {
                $sum = 0;
                $dataPoints = [];
                foreach ($rows as $row) {
                    $val = round($row->moneta_comission, 1);
                    $dataPoints[] = [$row->date, $val];
                    $sum += $val;
                }

                $colorId = isset($accounts[$k]) ? (int)$accounts[$k]->color_id : self::ACCOUNT_DEFAULT_COLOR;
                $singleOption = $this->findOptionByValue($colorId, $allColorOptions);

                $legend[] = [
                    'name' => $k,
                    'data' => $dataPoints,
                    'sum' => $sum,
                    'id' => $k,
                    'color' => $singleOption['label']['color'] ?? null
                ];
                
                $tableRows[] = [
                    'name' => $k,
                    'sum' => $sum,
                    'id' => $k,
                    'active' => true,
                    'color' => [
                        'value' => $colorId,
                        'localOptions' => $singleOption ? [$singleOption] : []
                    ]
                ];
            }
        } else {
            $resultMetadata['title'] = 'Расходы в рублях (монета)';
            $sum = 0;
            $dataPoints = [];
            
            foreach ($rawData as $row) {
                $val = round($row->moneta_comission, 1);
                $dataPoints[] = [$row->date, $val];
                $sum += $val;
                
                $tableRows[] = [
                    'id' => $row->id,
                    'date' => $row->date,
                    'sum' => $val,
                    'active' => 1
                ];
            }
            
            $legend[] = [
                'name' => 'Расходы за период',
                'data' => $dataPoints,
                'sum' => $sum,
                'id' => 1
            ];
        }

        return $this->finalizeAnalytics($type, $request, $params, $resultMetadata, $legend, $tableRows);
    }

    public function gibddQueries($request)
    {
        if (!$this->isModuleEnabled('gibdd')) {
            return ['error' => 'Module gibdd is disabled'];
        }

        $this->globalSettings();
        $params = $this->getCommonParams($request, 'gibdd_queries');

        $data = DB::table('gibdd_queries')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$params['period_start'], $params['period_end']])
            ->get();

        $data = $data->toArray();
        $legend = [];
        $dataPoints = [];
        $sum = 0;
        $tableRows = [];

        foreach ($data as $k => $row) {
            $dataPoints[$k] = [$row->created_at, 1];
            $sum++;
            $tableRows[] = [
                'id' => $row->id,
                'date' => $row->created_at,
                'sum' => 1,
                'active' => 1
            ];
        }

        $legend[] = [
            'name' => 'Проверки за период',
            'data' => $dataPoints,
            'sum' => $sum,
            'id' => 1
        ];

        $resultMetadata = [
            'title' => 'Проверки штрафов на сайте',
            'field_color_id' => self::COLOR_LIST_ID,
        ];
        
        return $this->finalizeAnalytics('gibdd_queries', $request, $params, $resultMetadata, $legend, $tableRows);
    }


    // Logistics methods
    public function logisticsCarCount(Request $request) { return $this->wrapLogistics('Количество маршрутов за период', $request, 'logistics-car-count', fn($r) => $this->logisticsCarCountLogic($r)); }
    public function logisticsOrderStats(Request $request) { return $this->wrapLogistics('Статистика по заказам', $request, 'logistics-order-stats', fn($r) => $this->logisticsOrderStatsLogic($r)); }
    public function logisticsRouteMileage(Request $request) { return $this->wrapLogistics('Длина маршрутов за период', $request, 'logistics-route-mileage', fn($r) => $this->logisticsRouteMetricLogic($r, 'mileage', 'Длина маршрутов (км)', 'date')); }
    public function logisticsRouteDuration(Request $request) { return $this->wrapLogistics('Длительность маршрутов за период', $request, 'logistics-route-duration', fn($r) => $this->logisticsRouteMetricLogic($r, 'time', 'Длительность', 'date')); }
    public function logisticsReserveForDelivery(Request $request) { return $this->wrapLogistics('Заложено на доставку за период', $request, 'logistics-reserve-for-delivery', fn($r) => $this->logisticsRouteMetricLogic($r, 'reserve_for_delivery', 'Заложено на доставку', 'date')); }
    public function logisticsDeliveryPrice(Request $request) { return $this->wrapLogistics('Цена доставки за период', $request, 'logistics-delivery-price', fn($r) => $this->logisticsRouteMetricLogic($r, 'delivery_price', 'Цена доставки', 'date')); }
    public function logisticsTotalWeight(Request $request) { return $this->wrapLogistics('Общий вес за период', $request, 'logistics-total-weight', fn($r) => $this->logisticsRouteMetricLogic($r, 'weight', 'Общий вес', 'date')); }

    protected function wrapLogistics($title, Request $request, $key, $callback)
    {
        $oldResult = $callback($request);
        $groupBy = $request->input('group_by');
        
        $tableRows = null;

        if ($groupBy === 'all') {
            $totalSum = 0;
            foreach ($oldResult['legend'] as $item) {
                $totalSum += $item['sum'] ?? 0;
            }

            $tableRows = [[
                'date' => 'Всего за период',
                'sum' => $totalSum,
                'active' => 1,
                'id' => 1
            ]];
        } elseif (count($oldResult['legend']) == 1 && isset($oldResult['legend'][0]['data'])) {
            $tableRows = [];
            foreach($oldResult['legend'][0]['data'] as $idx => $pt) {
                $tableRows[] = [
                    'date' => $pt[0],
                    'sum' => $pt[1],
                    'active' => 1,
                    'id' => $idx + 1
                ];
            }
        }

        

        $params = [
            'active_rows' => [],
            'sort_field' => null,
            'sort_order' => null,
            'entity' => null,
            'field' => null,
            'group_by' => $groupBy,
            'period_start' => $request->period['start'] ?? null,
            'period_end' => $request->period['end'] ?? null,
            'condition' => null,
            'config' => $this->fetchConfig($key)
        ];

        return $this->finalizeAnalytics($key, $request, $params, ['title' => $title], $oldResult['legend'], $tableRows);
    }

    protected function logisticsCarCountLogic(Request $request)
    {
        $dateField = 'date';
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');

        $routes = DB::table('routes')
            ->select($dateField)
            ->whereBetween($dateField, [$periodStart, $periodEnd])
            ->orderBy($dateField)
            ->get();

        $data = $routes->groupBy(function ($item) use ($dateField) {
            return Carbon::parse($item->$dateField)->format('Y-m-d H:i');
        });

        return $this->formatLogisticsResponse('Количество машин', $data, 'count', $request);
    }

    protected function logisticsOrderStatsLogic(Request $request)
    {
        $dateField = 'delivery_date';
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');
        $groupBy = $request->input('group_by');

        $tasks = DB::table('logistic_tasks')
            ->select($dateField, 'point_status')
            ->whereNotNull('route_id')
            ->whereBetween($dateField, [$periodStart, $periodEnd])
            ->orderBy($dateField)
            ->get();

        if ($groupBy === 'all') {
            return [
                'legend' => [[
                    'name' => 'Все заказы',
                    'sum' => $tasks->count(),
                    'data' => [['Всего', $tasks->count()]],
                    'id' => 'total_all'
                ]]
            ];
        }

        $grouped = $tasks->groupBy('point_status');
        $legend = [];

        foreach ($grouped as $status => $items) {
            $data = $items->groupBy(function ($item) use ($dateField) {
                return Carbon::parse($item->$dateField)->format('Y-m-d H:i');
            });

            $legend[] = [
                'name' => $status,
                'data' => $data->map(function ($group) use ($dateField) {
                    return [$group->first()->$dateField, $group->count()];
                })->values()->toArray(),
                'sum' => $items->count(),
                'id' => $status
            ];
        }
        return ['legend' => $legend];
    }

    protected function logisticsRouteMetricLogic(Request $request, $field, $title, $dateField)
    {
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');

        $routes = DB::table('routes')
            ->select($dateField, $field)
            ->whereBetween($dateField, [$periodStart, $periodEnd])
            ->orderBy($dateField)
            ->get();

        return $this->formatLogisticsResponse(
            $title,
            $routes->groupBy(function ($item) use ($dateField) {
                return Carbon::parse($item->$dateField)->format('Y-m-d H:i');
            }),
            $field,
            $request
        );
    }

    protected function formatLogisticsResponse($title, $groupedData, $valueField)
    {
        $legendData = [];
        $sum = 0;

        foreach ($groupedData as $date => $items) {
            $value = ($valueField === 'count') ? $items->count() : $items->sum($valueField);
            $sum += $value;
            $legendData[] = [$date, $value];
        }

        return [
            'legend' => [
                [
                    'name' => $title,
                    'data' => $legendData,
                    'sum' => $sum,
                    'id' => 1
                ]
            ]
        ];
    }

    public function getAllLogisticsAnalytics(Request $request)
    {
        $map = [
            'logistics-car-count' => 'logisticsCarCount',
            //'logistics-order-stats' => 'logisticsOrderStats',
            'logistics-route-mileage' => 'logisticsRouteMileage',
            'logistics-route-duration' => 'logisticsRouteDuration',
            'logistics-reserve-for-delivery' => 'logisticsReserveForDelivery',
            'logistics-delivery-price' => 'logisticsDeliveryPrice',
            'logistics-total-weight' => 'logisticsTotalWeight',
        ];

        $results = [];
        foreach ($map as $type => $method) {
            $response = $this->$method($request);
            
            // Суммируем значения из легенды
            $totalSum = collect($response['legend'] ?? [])->sum('sum');

            $results[] = [
                'title' => $response['title'] ?? '',
                'type' => $type,
                'sum' => $totalSum
            ];
        }

        return $results;
    }

    public function getAllAnalyticsData($request)
    {
        $list = [];
        $defaultOrder = 0;

        // 1. Получаем сохраненный порядок из базы данных
        $savedOrder = $this->fetchConfig(self::DASHBOARD_ORDER_TYPE);
        $orderMap = is_array($savedOrder) ? array_flip($savedOrder) : [];

        $collect = function($key, $dbType, $title) use (&$list, &$defaultOrder, $orderMap) {
            $config = $this->fetchConfig($dbType);
            
            // Если ключ есть в сохраненном порядке, берем его индекс, иначе ставим в конец
            $sortOrder = isset($orderMap[$key]) ? $orderMap[$key] : 1000 + $defaultOrder++;

            $list[] = [
                'key' => $key,
                'title' => $title,
                'config' => $config,
                'order' => $sortOrder
            ];
        };

        if ($this->isModuleEnabled('gibdd')) {
            $collect('fines', 'gibdd', 'Штрафы');
            if (!tenant('id')) {
                $collect('gibdd_queries', 'gibdd_queries', 'Проверки штрафов на сайте');
            }
        }

        if (!tenant('id')) {
            $collect('income', 'income', 'Чистая прибыль (безнал)');
            $collect('income_moneta', 'income_moneta', 'Чистая прибыль (монета)');
            $collect('account_incomes', 'expenses', 'Расходы (безнал)');
            $collect('all_income', 'all_income', 'Доходы с порталов');
            $collect('expense_moneta', 'expense_moneta', 'Расходы (монета)');
        } else {
            $collect('logistics-car-count', 'logistics-car-count', 'Количество машин');
            $collect('logistics-route-mileage', 'logistics-route-mileage', 'Длина маршрутов');
            $collect('logistics-route-duration', 'logistics-route-duration', 'Время маршрутов');
            $collect('logistics-reserve-for-delivery', 'logistics-reserve-for-delivery', 'Заложено на доставку');
            $collect('logistics-delivery-price', 'logistics-delivery-price', 'Цена доставки');
            $collect('logistics-total-weight', 'logistics-total-weight', 'Общий вес');
        }

        // 2. Сортируем список по полю order
        usort($list, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $list;
    }

    public function updateSettings($request)
    {
        $userId = Auth::id();
        
        // Добавлен новый случай: сохранение порядка графиков
        if ($request->order && is_array($request->order)) {
            DB::table('analytic')->updateOrInsert(
                ['user_id' => $userId, 'type' => self::DASHBOARD_ORDER_TYPE],
                ['config' => json_encode($request->order)]
            );
        } elseif ($request->type && $request->id) {
            $currentActive = DB::table('analytic')
                ->where('user_id', $userId)
                ->where('type', $request->type)
                ->value('active_rows');
                
            $active_rows = $currentActive ? json_decode($currentActive, true) : [];
            
            $key = array_search($request->id, $active_rows);
            if ($key !== false) {
                unset($active_rows[$key]);
            } else {
                $active_rows[] = (int)$request->id;
            }
            
            $active_rows = array_values($active_rows);
            
            DB::table('analytic')->updateOrInsert(
                ['user_id' => $userId, 'type' => $request->type],
                ['active_rows' => json_encode($active_rows)]
            );
                
        } elseif ($request->type && $request->config) {
            DB::table('analytic')->updateOrInsert(
                ['user_id' => $userId, 'type' => $request->type],
                ['config' => json_encode($request->config)]
            );
        }
    }

    public function getSettings($request)
    {
        return $this->fetchConfig($request->type);
    }

    protected function fetchConfig($type)
    {
        $config = DB::table('analytic')
            ->where('user_id', Auth::id())
            ->where('type', $type)
            ->value('config');
            
        return $config ? json_decode($config, true) : [];
    }

    protected function isModuleEnabled($slug)
    {
        return DB::table('modules')
            ->where('slug', $slug)
            ->where('enabled', 1)
            ->exists();
    }
}