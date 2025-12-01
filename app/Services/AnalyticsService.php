<?php

namespace App\Services;

use DB;
use Auth;
use Carbon\Carbon;
use App\Models\Permission;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;

class AnalyticsService
{
    // protected $settings;

    // public function __construct($tenantId = null)
    // {
    //     if ($tenantId) {
    //         tenancy()->initialize($tenantId);
    //     }
    //     $this->settings = \App\Models\Settings::get();
    // }
    protected $settings;
    protected $tenantId;

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

    protected function checkPermissions()
    {
        $data_type_id = DB::table('data_types')->where('slug', 'analytics')->first()->id;
        $permissions = [];
        
        if (Auth::user()->role_id) {
            $permissions = Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            
            if (!$permissions) {
                $data_types = DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                $permissions = [];
                $res = Permission::whereNotNull('entity_id')->where('role_id', Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach ($data_types as $entity_id => $entity) {
                    if (!array_key_exists($entity_id, $res)) {
                        DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                
                $permissions = Auth::user()->role->permissions()->select([
                    'read_p',
                    'create_p',
                    'update_p',
                    'delete_p',
                    'export_p',
                    'import_p'
                ])->where('entity_id', $data_type_id)->first();
            }
            
            $permissions = $permissions->toArray();
        }
            
        if (!Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || 
            !Auth::user()->is_admin && !isset($permissions)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        return true;
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
        if (DB::table('analytic')->where('user_id', $userId)->where('type', $type)->exists()) {
            DB::table('analytic')->where('user_id', $userId)->where('type', $type)->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['type'] = $type;
            info($data);
            if(is_array($data['active_rows']))
                $data['active_rows'] = json_encode($data);
            DB::table('analytic')->insert($data);
        }
    }

    public function gibdd($request)
    {
        if (!$this->isModuleEnabled('gibdd')) {
            return ['error' => 'Module gibdd is disabled'];
        }
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('gibdd', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters from request or use defaults
        $entity = $request->entity ?: $res->entity;

        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if certain parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table($entity);
        $date_field = 'created_at';
        
        $field_chain = [];
        if (strstr($nfield, '.')) {
            $field_chain = explode('.', $nfield);
            $field_name = $field_chain[0];
            $field = $settings[$entity]['fields'][$field_chain[0]];
        } else {
            $field = $settings[$entity]['fields'][$nfield];
            $field_name = $nfield;
        }

        // Select fields and apply conditions
        if ($group_by) {
            $data = $data->select($date_field, $field->field, $group_by);
        } else {
            $data = $data->select($date_field, $field->field);
        }
        
        $data = $data->orderBy($date_field)
            ->whereNotNull($field->field)
            ->whereBetween($date_field, [$period_start, $period_end]);

        if ($condition) {
            foreach ($condition as $field_condition => $value) {
                if (strstr($field_condition, '.')) {
                    $field_chain = explode('.', $nfield);
                    if (isset($settings[$entity]['fields'][$field_chain[0]])) {
                        $data = $data->whereJsonContains(str_replace('.', '->', $field_condition), is_numeric($value) ? (int)$value : $value);
                    }
                } else {
                    $data = $data->where($field_condition, $value);
                }
            }
        }

        $data = $data->get();
        
        // Process grouped data
        if ($group_by && $request->group_by) {
            $data = $data->groupBy($group_by);
            $field = $settings[$entity]['fields'][$group_by];
            
            if ($field->type == 'relation' && $field->relation_table) {
                $relation_objects = DB::table($field->relation_table)->get()->keyBy('id')->toArray();
                foreach ($relation_objects as $k => $value) {
                    $relation_objects[$k] = (array)$value;
                    if ($arr = json_decode($relation_objects[$k]['name'], true)) {
                        $relation_objects[$k]['name'] = isset($arr['value']) ? $arr['value'] : $arr;
                    }
                }
            }
        }


        $data = $data->toArray();
        $result = [];

        // Process data for response
        if ($group_by && $request->group_by) {
            $result['title'] = 'Штрафы в рублях по автопарку';
            $result['legend'] = [];
            
            if ($entity == 'fines_gibdd') {
                $car_fields = $settings['cars']['fields'];
                foreach ($car_fields as $cfield) {
                    if ($cfield->field == 'color_status') {
                        $car_field = $cfield->id;
                        $result['field_color_id'] = $car_field;
                    }
                }
            }
            
            foreach ($data as $k => $row) {
                $object = [];
                
                if (isset($relation_objects) && isset($relation_objects[$k])) {
                    $object['name'] = $relation_objects[$k]['name'];
                } elseif (!isset($relation_objects)) {
                    $object['name'] = $k;
                }
                
                if (count($object)) {
                    $object['data'] = [];
                    $object['sum'] = 0;
                    
                    foreach ($data[$k] as $group_key => $group) {
                        $object['data'][$group_key] = [];
                        
                        foreach ($group as $field => $value) {
                            if ($field != $group_by) {
                                if ($arr = json_decode($value, true)) {
                                    $object['data'][$group_key][] = $arr['value'];
                                } else {
                                    $object['data'][$group_key][] = $value;
                                }
                            }
                            
                            if ($field == $field_name) {
                                if ($arr = json_decode($value, true)) {
                                    $object['sum'] += (integer)$arr['value'];
                                } else {
                                    $object['sum'] += (integer)$value;
                                }
                            }
                        }
                    }
                    
                    $object['active'] = true;
                    
                    if ($entity == 'fines_gibdd' && $group_by == 'car_id') {
                        $car = DB::table('cars')->find($relation_objects[$k]['id']);
                        $list_values = $settings['list_values'][$car_field];
                        
                        if ($car) {
                            $object['color'] = [
                                'value' => (int)$car->color_status,
                                'localOptions' => $list_values
                            ];
                        }
                    }
                    
                    $object['id'] = $k;
                    $result['legend'][] = $object;
                }
            }
            
            foreach ($result['legend'] as $i => $object) {
                if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                    $result['legend'][$i]['active'] = false;
                }
            }
        } else {
            $result['title'] = 'Штрафы в рублях';
            $object = [
                'name' => 'Штрафы за период',
                'sum' => 0
            ];
            
            foreach ($data as $k => $row) {
                $object['data'][$k] = [];
                
                foreach ($row as $field => $value) {
                    if ($field != $group_by) {
                        if ($arr = json_decode($value, true)) {
                            $object['data'][$k][] = $arr['value'];
                        } else {
                            $object['data'][$k][] = $value;
                        }
                    }
                    
                    if ($field == $field_name) {
                        if ($arr = json_decode($value, true)) {
                            $object['sum'] += (integer)$arr['value'];
                        } else {
                            $object['sum'] += (integer)$value;
                        }
                    }
                }
            }
            
            $object['active'] = true;
            $result['legend'][] = $object;
        }

        // Sorting
        if ($sort_field && $sort_order == 'desc') {
            $result['legend'] = collect($result['legend'])->sortByDesc($sort_field)->toArray();
        } elseif ($sort_field == 'sum') {
            $result['legend'] = collect($result['legend'])->sortBy($sort_field)->toArray();
        }
        
        $result['legend'] = array_values($result['legend']);
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
        $result['config'] = $config;

        // Update analytic record
        $this->updateAnalyticRecord($request->type, Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function income($request)
    {
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('income', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Get and process data
        $arr = [];
        $expenses = Expense::orderBy('created_at')
            ->whereBetween('created_at', [$period_start, $period_end])
            ->get()
            ->groupBy('account_id');
            
        $invoices = Invoice::orderBy('created_at')
            ->whereBetween('created_at', [$period_start, $period_end])
            ->get()
            ->groupBy('account_id');

        foreach ($expenses as $account_id => $records) {
            if (!isset($arr[$account_id]['expenses'])) {
                $arr[$account_id] = ['expenses' => [], 'invoices' => []];
            }
            
            $arr[$account_id]['expenses'] = $records->groupBy(function ($record) {
                return Carbon::parse($record->created_at)->format('Y-m-d H:i');
            })->toArray();
        }

        foreach ($invoices as $account_id => $records) {
            if (!isset($arr[$account_id]['invoices'])) {
                $arr[$account_id] = ['expenses' => [], 'invoices' => []];
            }
            
            $arr[$account_id]['invoices'] = $records->groupBy(function ($record) {
                return Carbon::parse($record->created_at)->format('Y-m-d H:i');
            })->toArray();
        }

        $data = [];
        foreach ($arr as $account_id => $records) {
            foreach ($records['expenses'] as $date => $expenses) {
                foreach ($expenses as $expense) {
                    if (!isset($data[$account_id][$date])) {
                        $data[$account_id][$date] = 0;
                    }
                    $data[$account_id][$date] += $expense['sum'];
                }
            }
        }

        foreach ($arr as $account_id => $records) {
            foreach ($records['invoices'] as $date => $expenses) {
                foreach ($expenses as $expense) {
                    if (!isset($data[$account_id][$date])) {
                        $data[$account_id][$date] = 0;
                    }
                    $data[$account_id][$date] -= $expense['sum'];
                }
            }
        }

        // Build legend
        $legend = [];
        $accounts = DB::table('accounts')->get()->keyBy('id')->toArray();
        $color_values = $settings['list_values'][2844];

        foreach ($data as $account_id => $records) {
            if (!isset($accounts[$account_id])) {
                continue;
            }
            
            $name = json_decode($accounts[$account_id]->name, true);
            $name = $name['value'] ?? $accounts[$account_id]->name;
            
            $legend[$account_id] = [
                'name' => $name,
                'data' => [],
                'sum' => 0,
                'active' => true,
                'id' => $account_id,
                'color' => [
                    'value' => (int)$accounts[$account_id]->color_id,
                    'localOptions' => $color_values
                ]
            ];
            
            foreach ($records as $date => $expense) {
                $legend[$account_id]['data'][] = [$date, $expense];
                $legend[$account_id]['sum'] += $expense;
            }
        }

        // Update active status based on active_rows
        foreach ($legend as $i => $object) {
            if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                $legend[$i]['active'] = false;
            }
        }

        // Prepare response
        if (!count($active_rows)) {
            $active_rows = json_encode(array_keys($legend));
        }

        $result = [
            'title' => 'Чистая прибыль по порталам (безнал)',
            'legend' => array_values($legend),
            'field_color_id' => 2844,
            'row_order' => $active_rows,
            'config' => $config
        ];

        // Update analytic record
        $this->updateAnalyticRecord('income', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function incomeMoneta($request)
    {
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('income_moneta', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table('payments');
        $date_field = 'date';
        
        $data = $data->orderBy($date_field)
            ->where('status', 'success')
            ->whereNotNull('sum')
            ->whereBetween($date_field, [$period_start, $period_end]);

        $data = $data->get();
        
        // Group data if needed
        if ($group_by && $request->group_by) {
            $data = $data->groupBy('account_id');
        }

        $data = $data->toArray();
        $result = [];

        // Get accounts for color mapping
        $accounts = DB::table('accounts')->get()->keyBy('tenant_id')->toArray();
        $color_values = $settings['list_values'][2844];

        if ($group_by && $request->group_by) {
            $result['title'] = 'Чистая прибыль в рублях по порталам (монета)';
            $result['legend'] = [];
            
            foreach ($data as $k => $row) {
                $object = [
                    'name' => $k ?: 'Без портала',
                    'data' => [],
                    'sum' => 0,
                    'active' => true,
                    'id' => $k,
                    'color' => [
                        'value' => isset($accounts[$k]) ? (int)$accounts[$k]->color_id : 1495,
                        'localOptions' => $color_values
                    ]
                ];

                foreach ($data[$k] as $group_key => $g) {
                    $group = (array)$g;
                    $object['data'][$group_key] = [
                        $group['date'],
                        $group['amount'] - $group['moneta_comission'] - $group['sum']
                    ];
                    $object['sum'] += $group['amount'] - $group['moneta_comission'] - $group['sum'];
                }

                $result['legend'][] = $object;
            }

            // Update active status based on active_rows
            foreach ($result['legend'] as $i => $object) {
                if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                    $result['legend'][$i]['active'] = false;
                }
            }
        } else {
            $result['title'] = 'Чистая прибыль в рублях (монета)';
            $object = [
                'name' => 'Доходы за период',
                'data' => [],
                'sum' => 0,
                'active' => true
            ];

            foreach ($data as $k => $row) {
                $object['data'][$k] = [
                    $row->date,
                    $row->amount - $row->moneta_comission - $row->sum
                ];
                $object['sum'] += $row->amount - $row->moneta_comission - $row->sum;
            }

            $result['legend'][] = $object;
        }

        // Sorting
        if ($sort_field && $sort_order == 'desc') {
            $result['legend'] = collect($result['legend'])->sortByDesc($sort_field)->toArray();
        } elseif ($sort_field == 'sum') {
            $result['legend'] = collect($result['legend'])->sortBy($sort_field)->toArray();
        }

        $result['legend'] = array_values($result['legend']);
        $result['field_color_id'] = 2844;
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
        $result['config'] = $config;

        // Update analytic record
        $this->updateAnalyticRecord('income_moneta', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function accountIncomes($request)
    {
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('expenses', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table('invoices')
            ->orderBy('created_at')
            ->where('status', 1482)
            ->whereNotNull('account_id')
            ->whereNotNull('sum')
            ->whereBetween('created_at', [$period_start, $period_end])
            ->get();

        if ($group_by && $request->group_by) {
            $data = $data->groupBy('account_id');
        }

        $data = $data->toArray();
        $result = [];

        // Get accounts for color mapping
        $accounts = DB::table('accounts')->get()->keyBy('id')->toArray();
        $color_values = $settings['list_values'][2844];

        if ($group_by && $request->group_by) {
            $result['title'] = 'Расходы по порталам (безнал)';
            $result['legend'] = [];
            
            foreach ($data as $k => $row) {
                $name = json_decode($accounts[$k]->name, true);
                $name = $name['value'] ?? $accounts[$k]->name;
                
                $object = [
                    'name' => $name,
                    'data' => [],
                    'sum' => 0,
                    'active' => true,
                    'id' => $k,
                    'color' => [
                        'value' => (int)$accounts[$k]->color_id,
                        'localOptions' => $color_values
                    ]
                ];

                foreach ($data[$k] as $group_key => $g) {
                    $group = (array)$g;
                    $object['data'][$group_key] = [
                        $group['created_at'],
                        $group['sum']
                    ];
                    $object['sum'] += $group['sum'];
                }

                $result['legend'][] = $object;
            }

            // Update active status based on active_rows
            foreach ($result['legend'] as $i => $object) {
                if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                    $result['legend'][$i]['active'] = false;
                }
            }
        } else {
            $result['title'] = 'Расходы по порталам (безнал)';
            $object = [
                'name' => 'Расходы за период',
                'data' => [],
                'sum' => 0,
                'active' => true
            ];

            foreach ($data as $k => $row) {
                $object['data'][$k] = [
                    $row->created_at,
                    $row->sum
                ];
                $object['sum'] += $row->sum;
            }

            $result['legend'][] = $object;
        }

        // Sorting
        if ($sort_field && $sort_order == 'desc') {
            $result['legend'] = collect($result['legend'])->sortByDesc($sort_field)->toArray();
        } elseif ($sort_field == 'sum') {
            $result['legend'] = collect($result['legend'])->sortBy($sort_field)->toArray();
        }

        $result['legend'] = array_values($result['legend']);
        $result['field_color_id'] = 2844;
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
        $result['config'] = $config;

        // Update analytic record
        $this->updateAnalyticRecord('expenses', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function allIncome($request)
    {
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('all_income', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table('expenses')
            ->orderBy('created_at')
            ->whereNotNull('account_id')
            ->whereNotNull('sum')
            ->whereBetween('created_at', [$period_start, $period_end])
            ->get();

        if ($group_by && $request->group_by) {
            $data = $data->groupBy('account_id');
        }

        $data = $data->toArray();
        $result = [];

        // Get accounts for color mapping
        $accounts = DB::table('accounts')->get()->keyBy('id')->toArray();
        $color_values = $settings['list_values'][2844];

        if ($group_by && $request->group_by) {
            $result['title'] = 'Доходы с порталов';
            $result['legend'] = [];
            
            foreach ($data as $k => $row) {
                $name = json_decode($accounts[$k]->name, true);
                $name = $name['value'] ?? $accounts[$k]->name;
                
                $object = [
                    'name' => $name,
                    'data' => [],
                    'sum' => 0,
                    'active' => true,
                    'id' => $k,
                    'color' => [
                        'value' => (int)$accounts[$k]->color_id,
                        'localOptions' => $color_values
                    ]
                ];

                foreach ($data[$k] as $group_key => $g) {
                    $group = (array)$g;
                    $object['data'][$group_key] = [
                        $group['created_at'],
                        $group['sum']
                    ];
                    $object['sum'] += $group['sum'];
                }

                $result['legend'][] = $object;
            }

            // Update active status based on active_rows
            foreach ($result['legend'] as $i => $object) {
                if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                    $result['legend'][$i]['active'] = false;
                }
            }
        } else {
            $result['title'] = 'Доходы с порталов';
            $object = [
                'name' => 'Доходы за период',
                'data' => [],
                'sum' => 0,
                'active' => true
            ];

            foreach ($data as $k => $row) {
                $object['data'][$k] = [
                    $row->created_at,
                    $row->sum
                ];
                $object['sum'] += $row->sum;
            }

            $result['legend'][] = $object;
        }

        // Sorting
        if ($sort_field && $sort_order == 'desc') {
            $result['legend'] = collect($result['legend'])->sortByDesc($sort_field)->toArray();
        } elseif ($sort_field == 'sum') {
            $result['legend'] = collect($result['legend'])->sortBy($sort_field)->toArray();
        }

        $result['legend'] = array_values($result['legend']);
        $result['field_color_id'] = 2844;
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
        $result['config'] = $config;

        // Update analytic record
        $this->updateAnalyticRecord('all_income', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function expenseMoneta($request)
    {
        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('expense_moneta', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table('payments')
            ->orderBy('date')
            ->where('status', 'success')
            ->whereNotNull('account_id')
            ->whereNotNull('moneta_comission')
            ->whereBetween('date', [$period_start, $period_end])
            ->get();

        if ($group_by && $request->group_by) {
            $data = $data->groupBy('account_id');
        }

        $data = $data->toArray();
        $result = [];

        // Get accounts for color mapping
        $accounts = DB::table('accounts')->get()->keyBy('tenant_id')->toArray();
        $color_values = $settings['list_values'][2844];

        if ($group_by && $request->group_by) {
            $result['title'] = 'Расходы в рублях по порталам (монета)';
            $result['legend'] = [];
            
            foreach ($data as $k => $row) {
                $object = [
                    'name' => $k,
                    'data' => [],
                    'sum' => 0,
                    'active' => true,
                    'id' => $k,
                    'color' => [
                        'value' => (int)$accounts[$k]->color_id,
                        'localOptions' => $color_values
                    ]
                ];

                foreach ($data[$k] as $group_key => $g) {
                    $group = (array)$g;
                    $object['data'][$group_key] = [
                        $group['date'],
                        round($group['moneta_comission'], 1)
                    ];
                    $object['sum'] += round($group['moneta_comission'], 1);
                }

                $result['legend'][] = $object;
            }

            // Update active status based on active_rows
            foreach ($result['legend'] as $i => $object) {
                if (!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
                    $result['legend'][$i]['active'] = false;
                }
            }
        } else {
            $result['title'] = 'Расходы в рублях (монета)';
            $object = [
                'name' => 'Расходы за период',
                'data' => [],
                'sum' => 0,
                'active' => true
            ];

            foreach ($data as $k => $row) {
                $object['data'][$k] = [
                    $row->date,
                    round($row->moneta_comission, 1)
                ];
                $object['sum'] += round($row->moneta_comission, 1);
            }

            $result['legend'][] = $object;
        }

        // Sorting
        if ($sort_field && $sort_order == 'desc') {
            $result['legend'] = collect($result['legend'])->sortByDesc($sort_field)->toArray();
        } elseif ($sort_field == 'sum') {
            $result['legend'] = collect($result['legend'])->sortBy($sort_field)->toArray();
        }

        $result['legend'] = array_values($result['legend']);
        $result['field_color_id'] = 2844;
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
        $result['config'] = $config;

        // Update analytic record
        $this->updateAnalyticRecord('expense_moneta', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function gibddQueries($request)
    {
        if (!$this->isModuleEnabled('gibdd')) {
            return ['error' => 'Module gibdd is disabled'];
        }

        $settings = $this->globalSettings();
        $this->checkPermissions();
        
        $res = $this->getAnalyticRecord('gibdd_queries', Auth::user()->id);
        $config = json_decode($res->config, true);
        
        // Extract parameters
        $entity = $request->entity ?: $res->entity;
        $nfield = $request->field ?: $res->field;
        $group_by = $request->group_by ?: $res->group_by;
        $period_start = $request->period ? $request->period['start'] : $res->period_start;
        $period_end = $request->period ? $request->period['end'] : $res->period_end;
        $condition = $request->condition ?: $res->acondition;
        $sort_field = $request->sort_field ?: $res->sort_field;
        $sort_order = $request->sort_order ?: $res->sort_order;
        $active_rows = json_decode($res->active_rows, true);

        // Reset active rows if parameters change
        if (($request->entity && $request->entity != $res->entity) ||
            ($request->field && $request->field != $res->field) ||
            ($request->group_by && $request->group_by != $res->group_by) ||
            ($request->condition && $request->condition != $res->acondition)) {
            $active_rows = [];
        }

        // Query construction
        $data = DB::table('gibdd_queries')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$period_start, $period_end])
            ->get();

        if ($group_by && $request->group_by) {
            $data = $data->groupBy('created_at');
        }

        $data = $data->toArray();
        $result = [
            'title' => 'Проверки штрафов на сайте',
            'legend' => [],
            'field_color_id' => 2844,
            'config' => $config
        ];

        $object = [
            'name' => 'Проверки за период',
            'data' => [],
            'sum' => 0,
            'active' => true
        ];

        foreach ($data as $k => $row) {
            $object['data'][$k] = [
                $row->created_at,
                1
            ];
            $object['sum'] += 1;
        }

        $result['legend'][] = $object;
        $result['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;

        // Update analytic record
        $this->updateAnalyticRecord('gibdd_queries', Auth::user()->id, [
            'entity' => $entity,
            'field' => $nfield,
            'group_by' => $group_by,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'acondition' => $condition,
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'active_rows' => $active_rows
        ]);

        return $result;
    }

    public function logisticsCarCount(Request $request)
    {
        $dateField = 'date';
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');

        $routes = \DB::table('routes')
            ->select($dateField, \DB::raw('1 as count'))
            ->whereBetween($dateField, [$periodStart, $periodEnd])
            ->orderBy($dateField)
            ->get();

        $data = $routes->groupBy(function ($item) {
            return Carbon::parse($item->$dateField)->format('Y-m-d H:i');
        });

        return $this->formatLogisticsResponse(
            'Количество машин',
            $data,
            'count',
            $periodStart,
            $periodEnd
        );
    }

    public function logisticsOrderStats(Request $request)
    {
        //$res = $this->getAnalyticRecord('expense_moneta', Auth::user()->id);

        $dateField = 'delivery_date';
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');

        $tasks = \DB::table('logistic_tasks')
            ->select($dateField, 'point_status')
            ->whereNotNull('route_id')
            ->whereBetween($dateField, [$periodStart, $periodEnd])
            ->orderBy($dateField)
            ->get();

        $grouped = $tasks->groupBy('point_status');

        $result = [
            'title' => 'Статистика заказов по статусам',
            'legend' => [],
            'row_order' => [],
            'config' => null
        ];

        foreach ($grouped as $status => $items) {
            $data = $items->groupBy(function ($item) use ($dateField) {
                return Carbon::parse($item->$dateField)->format('Y-m-d H:i');
            });

            $result['legend'][] = [
                'name' => $status,
                'data' => $data->map(function ($group) {
                    return [
                        $group->first()->$dateField,
                        $group->count()
                    ];
                })->values()->toArray(),
                'sum' => $items->count(),
                'active' => true
            ];
        }

        $result['row_order'] = range(0, count($result['legend']) - 1);
        return $result;
    }

    public function logisticsRouteMileage(Request $request)
    {
        return $this->logisticsRouteMetric(
            $request,
            'mileage',
            'Длина маршрутов (км)',
            'date'
        );
    }

    public function logisticsRouteDuration(Request $request)
    {
        return $this->logisticsRouteMetric(
            $request,
            'time',
            'Длительность',
            'date'
        );
    }

    public function logisticsReserveForDelivery(Request $request)
    {
        return $this->logisticsRouteMetric(
            $request,
            'reserve_for_delivery',
            'Заложено на доставку',
            'date'
        );
    }

    public function logisticsDeliveryPrice(Request $request)
    {
        return $this->logisticsRouteMetric(
            $request,
            'delivery_price',
            'Цена доставки',
            'date'
        );
    }

    public function logisticsTotalWeight(Request $request)
    {
        return $this->logisticsRouteMetric(
            $request,
            'weight',
            'Общий вес',
            'date'
        );
    }

    // Аналогичные методы для других метрик:
    // logisticsRouteDuration, logisticsReserveForDelivery, 
    // logisticsDeliveryPrice, logisticsTotalWeight

    protected function logisticsRouteMetric(Request $request, $field, $title, $dateField)
    {
        $periodStart = $request->period['start'] ?? now()->subMonth()->format('Y-m-d');
        $periodEnd = $request->period['end'] ?? now()->format('Y-m-d');

        $routes = \DB::table('routes')
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
            $periodStart,
            $periodEnd
        );
    }

    protected function formatLogisticsResponse($title, $groupedData, $valueField, $periodStart, $periodEnd)
    {
        $legend = [];
        $sum = 0;

        foreach ($groupedData as $date => $items) {
            $value = $items->sum($valueField);
            $sum += $value;
            
            $legend[0]['data'][] = [
                $date,
                $value
            ];
        }

        return [
            'title' => $title,
            'legend' => [
                [
                    'name' => $title,
                    'data' => $legend[0]['data'] ?? [],
                    'sum' => $sum,
                    'active' => true
                ]
            ],
            'row_order' => [0],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'config' => null
        ];
    }

    public function getAllLogisticsAnalytics(Request $request)
    {
        return [
            'car_count' => $this->logisticsCarCount($request),
            'order_stats' => $this->logisticsOrderStats($request),
            'route_mileage' => $this->logisticsRouteMileage($request),
            'route_duration' => $this->logisticsRouteDuration($request),
            'reserve_for_delivery' => $this->logisticsReserveForDelivery($request),
            'delivery_price' => $this->logisticsDeliveryPrice($request),
            'total_weight' => $this->logisticsTotalWeight($request),
        ];
    }

    public function getAllAnalyticsData($request)
    {
        $results = [];
    
        $baseRequest = [
            'period' => $request->period ?? null
        ];
        $baseRequest = new Request($baseRequest);
        
        // Проверяем модуль gibdd перед добавлением его аналитик
        if ($this->isModuleEnabled('gibdd')) {
            $results['fines'] = $this->gibdd($baseRequest);
            if(!tenant('id')) {
                $results['gibdd_queries'] = $this->gibddQueries($baseRequest);
            }
        }
        

        if(!tenant('id')) {
            $results['income'] = $this->income($baseRequest);
            $results['income_moneta'] = $this->incomeMoneta($baseRequest);
            $results['account_incomes'] = $this->accountIncomes($baseRequest);
            $results['all_income'] = $this->allIncome($baseRequest);
            $results['expense_moneta'] = $this->expenseMoneta($baseRequest);
        }
        
        return $results;
    }


    public function updateSettings($request)
    {
        if ($request->type && $request->id) {
            $res = DB::table('analytic')
                ->where('user_id', Auth::user()->id)
                ->where('type', $request->type)
                ->first();
                
            $active_rows = json_decode($res->active_rows, true);
            
            if (in_array($request->id, $active_rows)) {
                unset($active_rows[array_search($request->id, $active_rows)]);
            } else {
                $active_rows[] = (int)$request->id;
            }
            
            $active_rows = array_values($active_rows);
            
            DB::table('analytic')
                ->where('user_id', Auth::user()->id)
                ->where('type', $request->type)
                ->update(['active_rows' => json_encode($active_rows)]);
                
        } elseif ($request->type && $request->config) {
            DB::table('analytic')
                ->where('user_id', Auth::user()->id)
                ->where('type', $request->type)
                ->update(['config' => json_encode($request->config)]);
        }
    }

    public function getSettings($request)
    {
        $res = DB::table('analytic')
            ->where('user_id', Auth::user()->id)
            ->where('type', $request->type)
            ->first();
            
        if ($res) {
            return json_decode($res->config, true);
        }
        
        return [];
    }

    protected function isModuleEnabled($slug)
    {
        return \DB::table('modules')
            ->where('slug', $slug)
            ->where('enabled', 1)
            ->exists();
    }
}