<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ValueHelper;
use Carbon\Carbon;
use App\Http\Requests\AnalyticsRequest;
use App\Services\AnalyticsService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalyticsExport;

class AnalyticsController extends Controller
{
	protected $analyticsService;

    public function __construct()
    {
        $tenantId = tenant('id');

        $this->analyticsService = new AnalyticsService($tenantId);

        $this->middleware('logistic.read')->only([
            'logistics_car_count',
            'logistics_order_stats',
            'logistics_route_mileage',
            'logistics_route_duration',
            'logistics_reserve_for_delivery',
            'logistics_delivery_price',
            'logistics_delivery_compare',
            'logistics_total_weight',
            'logistics_arrival_percent',
            'logistics_car_arrival_percent',
            'logistics_company_profit',
            'logistics_car_profit',
            'get_all_logistics_analytics',
            'logisticsDaySummary',
        ]);
    }

    public function gibdd(Request $request)
    {
        return response()->json($this->analyticsService->gibdd($request));
    }

    public function income(Request $request)
    {
        return response()->json($this->analyticsService->income($request));
    }

    public function income_moneta(Request $request)
    {
        return response()->json($this->analyticsService->incomeMoneta($request));
    }

    public function account_incomes(Request $request)
    {
        return response()->json($this->analyticsService->accountIncomes($request));
    }

    public function all_income(Request $request)
    {
        return response()->json($this->analyticsService->allIncome($request));
    }

    public function expense_moneta(Request $request)
    {
        return response()->json($this->analyticsService->expenseMoneta($request));
    }

    public function gibdd_queries(Request $request)
    {
        return response()->json($this->analyticsService->gibddQueries($request));
    }

    // В классе AnalyticsController
    public function logistics_car_count(Request $request)
    {
        $data = $this->analyticsService->logisticsCarCount($request);
        return response()->json($data);
    }

    public function logistics_order_stats(Request $request)
    {
        $data = $this->analyticsService->logisticsOrderStats($request);
        return response()->json($data);
    }

    public function logistics_route_mileage(Request $request)
    {
        $data = $this->analyticsService->logisticsRouteMileage($request);
        return response()->json($data);
    }

    public function logistics_route_duration(Request $request)
    {
        $data = $this->analyticsService->logisticsRouteDuration($request);
        return response()->json($data);
    }

    public function logistics_reserve_for_delivery(Request $request)
    {
        $data = $this->analyticsService->logisticsReserveForDelivery($request);
        return response()->json($data);
    }

    public function logistics_delivery_price(Request $request)
    {
        $data = $this->analyticsService->logisticsDeliveryPrice($request);
        return response()->json($data);
    }

    public function logistics_delivery_compare(Request $request)
    {
        $data = $this->analyticsService->logisticsDeliveryCompare($request);
        return response()->json($data);
    }

    public function logistics_total_weight(Request $request)
    {
        $data = $this->analyticsService->logisticsTotalWeight($request);
        return response()->json($data);
    }

    public function logistics_arrival_percent(Request $request)
    {
        $data = $this->analyticsService->logisticsArrivalPercent($request);
        return response()->json($data);
    }

    public function logistics_car_arrival_percent(Request $request)
    {
        $data = $this->analyticsService->logisticsCarArrivalPercent($request);
        return response()->json($data);
    }

    public function logistics_company_profit(Request $request)
    {
        $data = $this->analyticsService->logisticsCompanyProfit($request);
        return response()->json($data);
    }

    public function logistics_car_profit(Request $request)
    {
        $data = $this->analyticsService->logisticsCarProfit($request);
        return response()->json($data);
    }

    public function get_all_logistics_analytics(Request $request)
    {
        $data = $this->analyticsService->getAllLogisticsAnalytics($request);
        return response()->json($data);
    }

    public function logisticsDaySummary(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        try {
            $date = \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable $e) {
            $date = now()->format('Y-m-d');
        }

        $routes = \DB::table('routes')
            ->whereDate('date', $date)
            ->whereNull('deleted_at')
            ->get();
        
        if ($routes->isEmpty()) {
            return response()->json([
                'total' => $this->emptyStats(),
                'carriers' => []
            ]);
        }
        
        $routeIds = $routes->pluck('id');
        
        // Task statistics
        $tasks = \DB::table('logistic_tasks')
            ->whereIn('route_id', $routeIds)
            ->whereNull('deleted_at')
            ->get();
        
        // Замените блок с $statusField на:
        $settings = get_settings();

        $statusLabels = [];
        if (isset($settings['logistic_tasks']['fields']['point_status'])) {
            $fieldId = $settings['logistic_tasks']['fields']['point_status']->id;
            if (isset($settings['list_values'][$fieldId])) {
                foreach ($settings['list_values'][$fieldId] as $fv) {
                    $statusLabels[$fv['value']] = [
                        'name' => $fv['label']['text'] ?? "Статус {$fv['value']}",
                        'color' => $fv['label']['color'] ?? '#ccc'
                    ];
                }
            }
        }
        
        // Build total stats
        $total = $this->buildStats($routes, $tasks, $statusLabels);
        
        // Carriers: group by company_id from routes table
        $companyIds = $routes->pluck('company_id')->filter()->unique();
        $companies = \DB::table('companies')
            ->whereIn('id', $companyIds)
            ->get()
            ->keyBy('id');
        
        $carrierGroups = [];
        foreach ($routes as $route) {
            $companyId = $route->company_id ?? 0;
            $companyName = 'Без перевозчика';
            if (isset($companies[$companyId])) {
                $rawName = $companies[$companyId]->name;
                if (is_string($rawName) && str_starts_with(trim($rawName), '{')) {
                    $decoded = json_decode($rawName, true);
                    $companyName = $decoded['value'] ?? $rawName;
                } else {
                    $companyName = $rawName;
                }
            }
            
            if (!isset($carrierGroups[$companyId])) {
                $carrierGroups[$companyId] = [
                    'id' => $companyId,
                    'name' => $companyName,
                    'route_ids' => []
                ];
            }
            $carrierGroups[$companyId]['route_ids'][] = $route->id;
        }
        
        $carriers = [];
        foreach ($carrierGroups as $group) {
            $carrierRoutes = $routes->whereIn('id', $group['route_ids']);
            $carrierTasks = $tasks->whereIn('route_id', $group['route_ids']);
            $carriers[] = array_merge(
                ['id' => $group['id'], 'name' => $group['name']],
                $this->buildStats($carrierRoutes, $carrierTasks, $statusLabels)
            );
        }
        
        return response()->json([
            'total' => $total,
            'carriers' => array_values($carriers)
        ]);
    }

    protected function buildStats($routes, $tasks, $statusLabels)
    {
        $validStatusIds = array_keys($statusLabels);
        $defaultStatus = !empty($validStatusIds) ? $validStatusIds[0] : null;
        
        // Group tasks by status, fixing null/deleted statuses
        $tasksByStatus = $tasks->groupBy(function($task) use ($validStatusIds, $defaultStatus) {
            $status = (int) $task->point_status;
            if (!$status || !in_array($status, $validStatusIds)) {
                return $defaultStatus ?? 0;
            }
            return $status;
        });
        
        $orderStats = [];
        foreach ($tasksByStatus as $status => $items) {
            $orderStats[] = [
                'status_id' => $status,
                'name' => $statusLabels[$status]['name'] ?? "Статус $status",
                'color' => $statusLabels[$status]['color'] ?? '#ccc',
                'count' => $items->count()
            ];
        }
        
        $totalReserve = $routes->sum('reserve_for_delivery');
        $totalDeliveryPrice = $routes->sum('delivery_price');
        
        return [
            'order_stats' => $orderStats,
            'total_orders' => $tasks->count(),
            'car_count' => $routes->count(),
            'mileage' => (int) round($routes->sum('mileage')),
            'duration' => $routes->sum('time'),
            'reserve_for_delivery' => $routes->sum('reserve_for_delivery'),
            'delivery_price' => $routes->sum('delivery_price'),
            'total_weight' => $routes->sum('weight'),
            // Прибыль = (заложено − фактическая цена) / заложено. Плюс, когда
            // потратили меньше заложенного (8587).
            'arrival_percent' => $totalReserve > 0
                ? round(($totalReserve - $totalDeliveryPrice) / $totalReserve * 100, 1)
                : 0,
        ];
    }

    protected function emptyStats()
    {
        return [
            'order_stats' => [],
            'total_orders' => 0,
            'car_count' => 0,
            'mileage' => 0,
            'duration' => 0,
            'reserve_for_delivery' => 0,
            'delivery_price' => 0,
            'total_weight' => 0,
            'arrival_percent' => 0,
        ];
    }

    public function get_all_analytics(AnalyticsRequest $request)
	{
	    $data = $this->analyticsService->getAllAnalyticsData($request);
	    
	    return response()->json($data);
	}

    public function settings(Request $request)
    {
        $this->analyticsService->updateSettings($request);
    }

    public function get_settings(Request $request)
    {
        return response()->json($this->analyticsService->getSettings($request));
    }

    public function export(Request $request, $type)
    {
        $now = time();
        $filename = "export_{$type}_{$now}.xlsx";
        
        // Сохраняем файл
        Excel::store(new AnalyticsExport($type, $request), $filename, 'public');
        
        // Формируем ссылку для скачивания
        if (tenant('id')) {
            $tenantId = tenant('id');
            $link = "https://{$tenantId}.compas.pro/storage/tenant{$tenantId}/app/public/{$filename}";
        } else {
            $link = "https://compas.pro/storage/app/public/{$filename}";
        }
        
        return response()->json([
            'success' => true,
            'link' => $link,
            'filename' => $filename
        ]);
    }
	// public function index(AnalyticsRequest $request)
    // {
    // 	$settings = app('settings');
    // 	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    //     $permissions = array();
    //     if(\Auth::user()->role_id) {
    //         $permissions = \Auth::user()->role->permissions()->select([
    //             'read_p',
    //             'create_p',
    //             'update_p',
    //             'delete_p',
    //             'export_p',
    //             'import_p'
    //         ])->where('entity_id', $data_type_id)->first();
    //         if(!$permissions) {
    //             $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
    //             //$permissions = $role->permissions_tables();
    //             $permissions = array();
    //             $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

    //             $new_permissions_exist = false;
    //             foreach($data_types as $entity_id => $entity) {
    //                 if(!array_key_exists($entity_id, $res)) {
    //                     \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
    //                     $new_permissions_exist = true;
    //                 }
    //             }
    //             $permissions = \Auth::user()->role->permissions()->select([
    //                 'read_p',
    //                 'create_p',
    //                 'update_p',
    //                 'delete_p',
    //                 'export_p',
    //                 'import_p'
    //             ])->where('entity_id', $data_type_id)->first();
    //         }
    //         $permissions = $permissions->toArray();
    //     }
            
    //     if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
    //         return response()->json([
    //             'message' => 'Forbidden'
    //         ], 403);
	//     	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->first();
	//     	if(!$res)
	//     		$res = \DB::table('analytic')->where('user_id', null)->first();
	//     	$config = json_decode($res->config, true);
	//     	$entity = $request->entity ? $request->entity : $res->entity;
	//     	$nfield = $request->field ? $request->field : $res->field;
	//     	$group_by = $request->group_by ? $request->group_by : $res->group_by;
	//     	$period_start = $request->period ? $request->period['start'] : $res->period_start;
	//     	$period_end = $request->period ? $request->period['end'] : $res->period_end;
	//     	$condition = $request->condition ? $request->condition : $res->acondition;
	//     	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
	//     	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

	//     	$active_rows = json_decode($res->active_rows, true);

	//     	if($request->entity && $request->entity != $res->entity) {
	//     		$active_rows = [];
	//     	}
	//     	if($request->field && $request->field != $res->field) {
	//     		$active_rows = [];
	//     	}
	//     	if($request->group_by && $request->group_by != $res->group_by) {
	//     		$active_rows = [];
	//     	}
	//     	if($request->condition && $request->condition != $res->acondition) {
	//     		$active_rows = [];
	//     	}

    // 	$data = \DB::table($entity);

    // 	$date_field = 'created_at';
    	
    // 	$field_chain = array();
    // 	if(strstr($nfield, '.')) {
    // 		$field_chain = explode('.', $nfield);
    // 		$field_name = $field_chain[0];
    // 		$field = $settings[$entity]['fields'][$field_chain[0]];
    // 	} else {
    // 		$field = $settings[$entity]['fields'][$nfield];
    // 		$field_name = $nfield;
    // 	};

    // 	if($group_by) {
    // 		$data = $data->select($date_field, $field->field, $group_by);
    // 	} else {
    // 		$data = $data->select($date_field, $field->field);
    // 	}
    // 	$data = $data->orderBy($date_field)->whereNotNull($field->field)->whereBetween($date_field, [$period_start, $period_end]);

    // 	if($condition) {
    // 		foreach ($condition as $field_condition => $value) {
    // 			if(strstr($field_condition, '.')) {
	// 	    		$field_chain = explode('.', $nfield);
	// 	    		if(isset($settings[$entity]['fields'][$field_chain[0]])) {
	// 	    			$data = $data->whereJsonContains(str_replace('.', '->', $field_condition), is_numeric($value) ? (int)$value : $value);
	// 	    		}
	// 	    	} else {
	// 	    		$data = $data->where($field_condition, $value);
	// 	    	};
    // 		}
    // 	}

    // 	$data = $data->get();
    // 	if($group_by && $request->group_by) {
    // 		$data = $data->groupBy($group_by);
    // 		info($entity);
    //         info($settings[$entity]['fields']);
    // 		$field = $settings[$entity]['fields'][$group_by];
	// 		if($field->type == 'relation' && $field->relation_table) {
	// 			$relation_objects = \DB::table($field->relation_table)->get()->keyBy('id')->toArray();//->pluck('name', 'id');
	// 			foreach($relation_objects as $k => $value) {
	// 				$relation_objects[$k] = (array)$value;
	// 			};
	// 			foreach($relation_objects as $k => $value) {
	// 				if($arr = json_decode($value['name'], true)) {
	// 					if(isset($arr['value']))
	// 						$relation_objects[$k]['name'] = $arr['value'];
	// 					else
	// 						$relation_objects[$k]['name'] = $arr;
	// 				}
	// 			}
	// 		}
    // 	};

    // 	$data = $data->toArray();

    // 	$res = array();

    // 	if ($group_by && $request->group_by) {
    // 		$res['title'] = 'Штрафы в рублях по автопарку';
    // 		$res['legend'] = array();
    		
    // 		if($entity == 'fines_gibdd') {
    // 			$car_fields = $settings['cars']['fields'];
    // 			foreach($car_fields as $cfield) {
    // 				if($cfield->field == 'color_status') {
    // 					$car_field = $cfield->id;
    // 					$res['field_color_id'] = $car_field;
    // 				}
    // 			}
    // 		}
    // 		foreach($data as $k => $row) {

    // 			$object = array();
    // 			if(isset($relation_objects) && isset($relation_objects[$k])) {
    // 				$object['name'] = $relation_objects[$k]['name'];
	//     		} elseif(!isset($relation_objects)) {
	//     			$object['name'] = $k;
	//     		}
	    		
	//     		if(count($object)) {
	//     			$object['data'] = array();
	//     			$object['sum'] = 0;
	//     			foreach($data[$k] as $group_key => $group) {
	//     				$object['data'][$group_key] = array();
	//     				foreach($group as $field => $value) {
	// 	    				if($field != $group_by) {
	// 	    					if($arr = json_decode($value, true)) {
	// 								$object['data'][$group_key][] = $arr['value'];
	// 							} else {
	// 								$object['data'][$group_key][] = $value;
	// 							}
	// 	    				}
	// 	    				if($field == $field_name) {
	// 	    					if($arr = json_decode($value, true)) {
	// 								$object['sum']+= (integer)$arr['value'];
	// 							} else {
	// 								$object['sum']+= (integer)$value;
	// 							}
	// 	    				}
	//     				}
	    				
	//     			}
	//     			$object['active'] = true;
	    			
	//     			if($entity == 'fines_gibdd' && $group_by == 'car_id') {
	//     				$car = \DB::table('cars')->find($relation_objects[$k]['id']);
	//     				// if($car->color_status && $color = \DB::table('field_values')->where('id', $car->color_status)->first()) {
	//     				// 	$object['color'] = $color->value;
	//     				// } else {
	//     				// 	$object['color'] = $car->color;
	//     				// }
	    				
	//     				$list_values = $settings['list_values'][$car_field];
	//     				if($car) {
	//     					$object['color'] = array(
	//                             'value' => (int)$car->color_status,
	//                             'localOptions' => $list_values
	//                         );
	    					
	//     				}
	    				
	//     			}
	//     			$object['id'] = $k;
	//     			$res['legend'][] = $object;
	//     		}
    // 		};
	// 		foreach($res['legend'] as $i => $object) {
    // 			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
    // 				$res['legend'][$i]['active'] = false;
    // 			}
	// 		}    		
    // 	} else {
    // 		$res['title'] = 'Штрафы в рублях';
    // 		$object = array();
	// 		$object['name'] = 'Штрафы за период';
	// 		$object['sum'] = 0;
    // 		foreach($data as $k => $row) {
	// 			$object['data'][$k] = array();
	// 			foreach($row as $field => $value) {
    // 				if($field != $group_by) {
    // 					if($arr = json_decode($value, true)) {
	// 						$object['data'][$k][] = $arr['value'];
	// 					} else {
	// 						$object['data'][$k][] = $value;
	// 					}
    // 				}
    // 				if($field == $field_name) {
    // 					if($arr = json_decode($value, true)) {
	// 						$object['sum']+= (integer)$arr['value'];
	// 					} else {
	// 						$object['sum']+= (integer)$value;
	// 					}
    // 				}
	// 			}
				
    // 		};
    		
    // 		$object['active'] = true;
    // 		$res['legend'][] = $object;
    		
    // 	}

    // 	if($sort_field && $sort_order == 'desc') {
    // 		$res['legend'] = collect($res['legend'])->sortByDesc($sort_field)->toArray();
    // 	} elseif($sort_field == 'sum') {
    // 		$res['legend'] = collect($res['legend'])->sortBy($sort_field)->toArray();
    // 	}
    // 	$res['legend'] = array_values($res['legend']);
    // 	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    // 	$res['config'] = $config;
    // 	// if(!count($active_rows))
	// 	// 	$active_rows = json_encode(array_keys($data));
	// 	// if(!$request->group_by)
	// 	// 	$active_rows = json_encode([0]);
	// 	//$active_rows = json_encode(array_keys($data));
	// 	if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->exists())
	//     	\DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->update([
	//     		'entity' => $entity,
	// 	    	'field' => $nfield,
	// 	    	'group_by' => $group_by,
	// 	    	'period_start' => $period_start,
	// 	    	'period_end' => $period_end,
	// 	    	'acondition' => $condition,
	// 	    	'sort_field' => $sort_field,
	// 	    	'sort_order' => $sort_order,
	// 	    	'active_rows' => $active_rows
	//     	]);
	//     else
	//     	\DB::table('analytic')->insert([
	//     		'entity' => $entity,
	// 	    	'field' => $nfield,
	// 	    	'group_by' => $group_by,
	// 	    	'period_start' => $period_start,
	// 	    	'period_end' => $period_end,
	// 	    	'acondition' => $condition,
	// 	    	'sort_field' => $sort_field,
	// 	    	'sort_order' => $sort_order,
	// 	    	'active_rows' => json_encode(array_keys($data)),
	// 	    	'user_id' => \Auth::user()->id,
	// 	    	'type' => $request->type
	//     	]);

    // 	return response()->json($res);
    	
    // }
    /*
    public function income(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                //$permissions = $role->permissions_tables();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
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
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'income')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$arr = [];
    	$expenses = \App\Models\Expense::orderBy('created_at')->whereBetween('created_at', [$period_start, $period_end])->get()->groupBy('account_id');
    	$invoices = \App\Models\Invoice::orderBy('created_at')->whereBetween('created_at', [$period_start, $period_end])->get()->groupBy('account_id');
    	foreach($expenses as $account_id => $records) {
    		if(!isset($arr[$account_id]['expenses']))
    			$arr[$account_id] = array('expenses' => [], 'invoices' => []);
    		$arr[$account_id]['expenses'] = $records->groupBy(function ($record) {
		        return Carbon::parse($record->created_at)->format('Y-m-d H:i');
		    })->toArray();
    	}
    	foreach($invoices as $account_id => $records) {
    		if(!isset($arr[$account_id]['invoices']))
    			$arr[$account_id] = array('expenses' => [], 'invoices' => []);
    		$arr[$account_id]['invoices'] = $records->groupBy(function ($record) {
		        return Carbon::parse($record->created_at)->format('Y-m-d H:i');
		    })->toArray();
    	}
    	$data = [];
    	foreach($arr as $account_id => $records) {
    		foreach($records['expenses'] as $date => $expenses) {
    			foreach($expenses as $k => $expense) {
    				if(!isset($data[$account_id][$date]))
    					$data[$account_id][$date] = 0;
    				$data[$account_id][$date]+= $expense['sum'];
    			}
    		}
    	}
    	foreach($arr as $account_id => $records) {
    		foreach($records['invoices'] as $date => $expenses) {
    			foreach($expenses as $k => $expense) {
    				if(!isset($data[$account_id][$date]))
    					$data[$account_id][$date] = 0;
    				$data[$account_id][$date]-= $expense['sum'];
    			}
    		}
    	};
    	$legend = [];
    	$accounts = \DB::table('accounts')->get()->keyBy('id')->toArray();
    	foreach($data as $account_id => $records) {
    		if(!isset($accounts[$account_id]))
    			continue;
    		if(!isset($legend[$account_id]))
    			$name = json_decode($accounts[$account_id]->name, true);
			$name = $name['value'] ?? $accounts[$account_id]->name;
			$color_values = $settings['list_values'][2844];

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
			
			// if(!in_array($account_id, $active_rows))
			// 	$legend[$account_id]['active'] = false;
    		foreach($records as $date => $expense) {
    			$legend[$account_id]['data'][] = [
    				$date,
    				$expense
    			];
    			$legend[$account_id]['sum']+= $expense;
    		};
    	};
    	
    	foreach($legend as $i => $object) {
			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
				$legend[$i]['active'] = false;
			}
		}
    	
    	

    	$data['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;

    	if(!count($active_rows))
			$active_rows = json_encode(array_keys($legend));

		$data = [
    		'title' => 'Чистая прибыль по порталам (безнал)',
    		'legend' => array_values($legend),
    		'field_color_id' => 2844,
    		'row_order' => $active_rows,
    		'config' => $config
    	];
    	//$active_rows = json_encode(array_keys($legend));
    	if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'income')->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'income')->update([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => $active_rows,
	    	]);
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'income'
	    	]);
    	return response()->json($data);
    	
    }

    public function income_moneta(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                //$permissions = $role->permissions_tables();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
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
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'income_moneta')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$data = \DB::table('payments');

    	$date_field = 'date';
    
    	$data = $data->orderBy($date_field)->where('status', 'success')->whereNotNull('sum')->whereBetween($date_field, [$period_start, $period_end]);

    	$data = $data->get();
    	if($group_by && $request->group_by) {
    		$data = $data->groupBy('account_id');
    	};

    	$data = $data->toArray();

    	$res = array();

    	$accounts = \DB::table('accounts')->get()->keyBy('tenant_id')->toArray();
    	$color_values = $settings['list_values'][2844];

    	if ($group_by && $request->group_by) {
    		$res['title'] = 'Чистая прибыль в рублях по порталам (монета)';
    		$res['legend'] = array();
    		
    		foreach($data as $k => $row) {

    			$object = array();
    			$object['name'] = $k ? $k : 'Без портала';
	    		
	    		if(count($object)) {
	    			$object['data'] = array();
	    			$object['sum'] = 0;

	    			foreach($data[$k] as $group_key => $g) {

	    				$group = (array)$g;
	    				if(!isset($object['data'][$group_key]))
	    					$object['data'][$group_key] = array();
	    				$object['data'][$group_key][] = $group['date'];
	    				$object['data'][$group_key][] = $group['amount'] - $group['moneta_comission'] - $group['sum'];
	    				$object['sum']+= $group['amount'] - $group['moneta_comission'] - $group['sum'];

	    				// $object['data'][$group_key] = array();
	    				// foreach($group as $field => $value) {
		    			// 	if($field == $group_by) {
						// 		$object['data'][$group_key][] = $value;
		    			// 	}
		    			// 	if($field == $field_name) {
						// 		$object['sum']+= (integer)$value;
		    			// 	}
	    				// }
	    				
	    			}
	    			$object['active'] = true;
	    			
	    			$object['color'] = array(
                        'value' => isset($accounts[$k]) ? (int)$accounts[$k]->color_id : 1495,
	                    'localOptions' => $color_values
                    );
	    			$object['id'] = $k;
	    			$res['legend'][] = $object;
	    		}
    		};
			foreach($res['legend'] as $i => $object) {
    			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
    				$res['legend'][$i]['active'] = false;
    			}
			}    		
    	} else {
    		$res['title'] = 'Чистая прибыль в рублях (монета)';
    		$object = array();
			$object['name'] = 'Доходы за период';
			$object['sum'] = 0;
    		foreach($data as $k => $row) {
				$object['data'][$k] = array();
				$object['data'][$k][] = $row->date;
				$object['data'][$k][] = $row->amount - $row->moneta_comission - $row->sum;
				$object['sum']+= $row->amount - $row->moneta_comission - $row->sum;
				
    		};
    		
    		$object['active'] = true;
    		$res['legend'][] = $object;
    		
    	}

    	if($sort_field && $sort_order == 'desc') {
    		$res['legend'] = collecT($res['legend'])->sortByDesc($sort_field)->toArray();
    	} elseif($sort_field == 'sum') {
    		$res['legend'] = collecT($res['legend'])->sortBy($sort_field)->toArray();
    	}
    	$res['legend'] = array_values($res['legend']);
    	$res['field_color_id'] = 2844;
    	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    	$res['config'] = $config;

    	// if(!count($active_rows))
		// 	$active_rows = json_encode(array_keys($data));
		// if(!$request->group_by)
		// 	$active_rows = json_encode([0]);
		//$active_rows = json_encode(array_keys($data));
		if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->update([
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
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'income_moneta'
	    	]);

    	return response()->json($res);
    	
    }

    public function account_incomes(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                //$permissions = $role->permissions_tables();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
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
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'expenses')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$data = \DB::table('invoices');

    	$date_field = 'created_at';
    
    	$data = $data->orderBy($date_field)->where('status', 1482)->whereNotNull('account_id')->whereNotNull('sum')->whereBetween($date_field, [$period_start, $period_end]);

    	$data = $data->get();
    	if($group_by && $request->group_by) {
    		$data = $data->groupBy('account_id');
    	};

    	$data = $data->toArray();

    	$res = array();

    	$accounts = \DB::table('accounts')->get()->keyBy('id')->toArray();
    	$color_values = $settings['list_values'][2844];

    	if ($group_by && $request->group_by) {
    		$res['title'] = 'Расходы по порталам (безнал)';
    		$res['legend'] = array();
    		
    		foreach($data as $k => $row) {

    			$object = array();
	    		$name = json_decode($accounts[$k]->name, true);
				$name = $name['value'] ?? $accounts[$k]->name;
    			$object['name'] = $name;
	    		
	    		if(count($object)) {
	    			$object['data'] = array();
	    			$object['sum'] = 0;

	    			foreach($data[$k] as $group_key => $g) {

	    				$group = (array)$g;
	    				if(!isset($object['data'][$group_key]))
	    					$object['data'][$group_key] = array();
	    				$object['data'][$group_key][] = $group['created_at'];
	    				$object['data'][$group_key][] = $group['sum'];
	    				$object['sum']+= $group['sum'];

	    				// $object['data'][$group_key] = array();
	    				// foreach($group as $field => $value) {
		    			// 	if($field == $group_by) {
						// 		$object['data'][$group_key][] = $value;
		    			// 	}
		    			// 	if($field == $field_name) {
						// 		$object['sum']+= (integer)$value;
		    			// 	}
	    				// }
	    				
	    			}
	    			$object['active'] = true;
	    			
	    			$object['color'] = array(
                        'value' => (int)$accounts[$k]->color_id,
	                    'localOptions' => $color_values
                    );
	    			$object['id'] = $k;
	    			$res['legend'][] = $object;
	    		}
    		};
			foreach($res['legend'] as $i => $object) {
    			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
    				$res['legend'][$i]['active'] = false;
    			}
			}    		
    	} else {
    		$res['title'] = 'Расходы по порталам (безнал)';
    		$object = array();
			$object['name'] = 'Расходы за период';
			$object['sum'] = 0;
    		foreach($data as $k => $row) {
				$object['data'][$k] = array();
				$object['data'][$k][] = $row->created_at;
				$object['data'][$k][] = $row->sum;
				$object['sum']+= $row->sum;
				
    		};
    		
    		$object['active'] = true;
    		$res['legend'][] = $object;
    		
    	}

    	if($sort_field && $sort_order == 'desc') {
    		$res['legend'] = collecT($res['legend'])->sortByDesc($sort_field)->toArray();
    	} elseif($sort_field == 'sum') {
    		$res['legend'] = collecT($res['legend'])->sortBy($sort_field)->toArray();
    	}
    	$res['legend'] = array_values($res['legend']);
    	$res['field_color_id'] = 2844;
    	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    	$res['config'] = $config;

    	// if(!count($active_rows))
		// 	$active_rows = json_encode(array_keys($data));
		// if(!$request->group_by)
		// 	$active_rows = json_encode([0]);
		//$active_rows = json_encode(array_keys($data));
		if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->update([
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
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'expenses'
	    	]);

    	return response()->json($res);
    	
    }

    public function all_income(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
        }
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'all_income')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$data = \DB::table('expenses');

    	$date_field = 'created_at';
    
    	$data = $data->orderBy($date_field)->whereNotNull('account_id')->whereNotNull('sum')->whereBetween($date_field, [$period_start, $period_end]);

    	$data = $data->get();
    	if($group_by && $request->group_by) {
    		$data = $data->groupBy('account_id');
    	};

    	$data = $data->toArray();

    	$res = array();

    	$accounts = \DB::table('accounts')->get()->keyBy('id')->toArray();
    	$color_values = $settings['list_values'][2844];

    	if ($group_by && $request->group_by) {
    		$res['title'] = 'Доходы с порталов';
    		$res['legend'] = array();
    		
    		foreach($data as $k => $row) {

    			$object = array();
	    		$name = json_decode($accounts[$k]->name, true);
				$name = $name['value'] ?? $accounts[$k]->name;
    			$object['name'] = $name;
	    		
	    		if(count($object)) {
	    			$object['data'] = array();
	    			$object['sum'] = 0;

	    			foreach($data[$k] as $group_key => $g) {

	    				$group = (array)$g;
	    				if(!isset($object['data'][$group_key]))
	    					$object['data'][$group_key] = array();
	    				$object['data'][$group_key][] = $group['created_at'];
	    				$object['data'][$group_key][] = $group['sum'];
	    				$object['sum']+= $group['sum'];

	    				// $object['data'][$group_key] = array();
	    				// foreach($group as $field => $value) {
		    			// 	if($field == $group_by) {
						// 		$object['data'][$group_key][] = $value;
		    			// 	}
		    			// 	if($field == $field_name) {
						// 		$object['sum']+= (integer)$value;
		    			// 	}
	    				// }
	    				
	    			}
	    			$object['active'] = true;
	    			
	    			$object['color'] = array(
                        'value' => (int)$accounts[$k]->color_id,
	                    'localOptions' => $color_values
                    );
	    			$object['id'] = $k;
	    			$res['legend'][] = $object;
	    		}
    		};
			foreach($res['legend'] as $i => $object) {
    			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
    				$res['legend'][$i]['active'] = false;
    			}
			}    		
    	} else {
    		$res['title'] = 'Доходы с порталов';
    		$object = array();
			$object['name'] = 'Доходы за период';
			$object['sum'] = 0;
    		foreach($data as $k => $row) {
				$object['data'][$k] = array();
				$object['data'][$k][] = $row->created_at;
				$object['data'][$k][] = $row->sum;
				$object['sum']+= $row->sum;
				
    		};
    		
    		$object['active'] = true;
    		$res['legend'][] = $object;
    		
    	}

    	if($sort_field && $sort_order == 'desc') {
    		$res['legend'] = collecT($res['legend'])->sortByDesc($sort_field)->toArray();
    	} elseif($sort_field == 'sum') {
    		$res['legend'] = collecT($res['legend'])->sortBy($sort_field)->toArray();
    	}
    	$res['legend'] = array_values($res['legend']);
    	$res['field_color_id'] = 2844;
    	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    	$res['config'] = $config;

    	// if(!count($active_rows))
		// 	$active_rows = json_encode(array_keys($data));
		// if(!$request->group_by)
		// 	$active_rows = json_encode([0]);
		//$active_rows = json_encode(array_keys($data));
		if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->update([
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
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'all_income'
	    	]);

    	return response()->json($res);
    	
    }

    public function expense_moneta(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
        }
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'expense_moneta')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$data = \DB::table('payments');

    	$date_field = 'date';
    
    	$data = $data->orderBy($date_field)->where('status', 'success')->whereNotNull('account_id')->whereNotNull('moneta_comission')->whereBetween($date_field, [$period_start, $period_end]);

    	$data = $data->get();
    	if($group_by && $request->group_by) {
    		$data = $data->groupBy('account_id');
    	};

    	$data = $data->toArray();
    	//return response()->json($data);
    	$res = array();

    	$accounts = \DB::table('accounts')->get()->keyBy('tenant_id')->toArray();
    	$color_values = $settings['list_values'][2844];

    	if ($group_by && $request->group_by) {
    		$res['title'] = 'Расходы в рублях по порталам (монета)';
    		$res['legend'] = array();
    		
    		foreach($data as $k => $row) {

    			$object = array();
    			$object['name'] = $k;
	    		
	    		if(count($object)) {
	    			$object['data'] = array();
	    			$object['sum'] = 0;

	    			foreach($data[$k] as $group_key => $g) {

	    				$group = (array)$g;
	    				if(!isset($object['data'][$group_key]))
	    					$object['data'][$group_key] = array();
	    				$object['data'][$group_key][] = $group['date'];
	    				$object['data'][$group_key][] = round($group['moneta_comission'], 1);
	    				$object['sum']+= round($group['moneta_comission'], 1);

	    				// $object['data'][$group_key] = array();
	    				// foreach($group as $field => $value) {
		    			// 	if($field == $group_by) {
						// 		$object['data'][$group_key][] = $value;
		    			// 	}
		    			// 	if($field == $field_name) {
						// 		$object['sum']+= (integer)$value;
		    			// 	}
	    				// }
	    				
	    			}
	    			$object['active'] = true;
	    			
	    			$object['color'] = array(
                        'value' => (int)$accounts[$k]->color_id,
	                    'localOptions' => $color_values
                    );
	    			$object['id'] = $k;
	    			$res['legend'][] = $object;
	    		}
    		};
			foreach($res['legend'] as $i => $object) {
    			if(!in_array($object['id'], $active_rows) && count($active_rows) && !$request->enable_rows) {
    				$res['legend'][$i]['active'] = false;
    			}
			}    		
    	} else {
    		$res['title'] = 'Расходы в рублях (монета)';
    		$object = array();
			$object['name'] = 'Расходы за период';
			$object['sum'] = 0;
    		foreach($data as $k => $row) {
				$object['data'][$k] = array();
				$object['data'][$k][] = $row->date;
				$object['data'][$k][] = round($row->moneta_comission,1);
				$object['sum']+= round($row->moneta_comission,1);
				
    		};
    		
    		$object['active'] = true;
    		$res['legend'][] = $object;
    		
    	}

    	if($sort_field && $sort_order == 'desc') {
    		$res['legend'] = collecT($res['legend'])->sortByDesc($sort_field)->toArray();
    	} elseif($sort_field == 'sum') {
    		$res['legend'] = collecT($res['legend'])->sortBy($sort_field)->toArray();
    	}
    	$res['legend'] = array_values($res['legend']);
    	$res['field_color_id'] = 2844;
    	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    	$res['config'] = $config;

    	// if(!count($active_rows))
		// 	$active_rows = json_encode(array_keys($data));
		// if(!$request->group_by)
		// 	$active_rows = json_encode([0]);
		//$active_rows = json_encode(array_keys($data));
		if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->update([
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
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'expense_moneta'
	    	]);

    	return response()->json($res);
    	
    }

    public function gibdd_queries(Request $request)
    {
    	$settings = app('settings');
    	$data_type_id = \DB::table('data_types')->where('slug', 'analytics')->first()->id;
    	$permissions = array();
        if(\Auth::user()->role_id) {
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $data_type_id)->first();
            if(!$permissions) {
                $data_types = \DB::table('data_types')->where('enable', 1)->where('hidden', 0)->get()->keyBy('id')->toArray();
                //$permissions = $role->permissions_tables();
                $permissions = array();
                $res = \App\Models\Permission::whereNotNull('entity_id')->where('role_id', \Auth::user()->role_id)->get()->keyBy('entity_id')->toArray();

                $new_permissions_exist = false;
                foreach($data_types as $entity_id => $entity) {
                    if(!array_key_exists($entity_id, $res)) {
                        \DB::table('permissions')->insert([['entity_id' => $entity_id, 'role_id' => \Auth::user()->role_id]]);
                        $new_permissions_exist = true;
                    }
                }
                $permissions = \Auth::user()->role->permissions()->select([
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
            
        if(!\Auth::user()->is_admin && isset($permissions['read_p']) && $permissions['read_p'] == 'N' || !\Auth::user()->is_admin && !isset($permissions))
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', 'gibdd_queries')->first();
    	if(!$res)
    		$res = \DB::table('analytic')->where('user_id', null)->first();
    	$config = json_decode($res->config, true);
    	$entity = $request->entity ? $request->entity : $res->entity;
    	$nfield = $request->field ? $request->field : $res->field;
    	$group_by = $request->group_by ? $request->group_by : $res->group_by;
    	$period_start = $request->period ? $request->period['start'] : $res->period_start;
    	$period_end = $request->period ? $request->period['end'] : $res->period_end;
    	$condition = $request->condition ? $request->condition : $res->acondition;
    	$sort_field = $request->sort_field ? $request->sort_field : $res->sort_field;
    	$sort_order = $request->sort_order ? $request->sort_order : $res->sort_order;

    	$active_rows = json_decode($res->active_rows, true);
    	if(!is_array($active_rows))
    		$active_rows = [];

    	if($request->entity && $request->entity != $res->entity) {
    		$active_rows = [];
    	}
    	if($request->field && $request->field != $res->field) {
    		$active_rows = [];
    	}
    	if($request->group_by && $request->group_by != $res->group_by) {
    		$active_rows = [];
    	}
    	if($request->condition && $request->condition != $res->acondition) {
    		$active_rows = [];
    	}
    	$data = \DB::table('gibdd_queries');

    	$date_field = 'created_at';
    
    	$data = $data->orderBy($date_field)->whereBetween($date_field, [$period_start, $period_end]);

    	$data = $data->get();
    	if($group_by && $request->group_by) {
    		$data = $data->groupBy('created_at');
    	};

    	$data = $data->toArray();

    	$res = array();

    		$res['title'] = 'Проверки штрафов на сайте';
    		$object = array();
			$object['name'] = 'Проверки за период';
			$object['sum'] = 0;
    		foreach($data as $k => $row) {
				$object['data'][$k] = array();
				$object['data'][$k][] = $row->created_at;
				$object['data'][$k][] = 1;
				$object['sum']+= 1;
				
    		};
    		
    		$object['active'] = true;
    		$res['legend'][] = $object;
    		

    	if($sort_field && $sort_order == 'desc') {
    		$res['legend'] = collecT($res['legend'])->sortByDesc($sort_field)->toArray();
    	} elseif($sort_field == 'sum') {
    		$res['legend'] = collecT($res['legend'])->sortBy($sort_field)->toArray();
    	}
    	$res['legend'] = array_values($res['legend']);
    	$res['field_color_id'] = 2844;
    	$res['row_order'] = !count($active_rows) ? array_keys($data) : $active_rows;
    	$res['config'] = $config;

    	// if(!count($active_rows))
		// 	$active_rows = json_encode(array_keys($data));
		// if(!$request->group_by)
		// 	$active_rows = json_encode([0]);
		//$active_rows = json_encode(array_keys($data));
		if(\DB::table('analytic')->where('user_id', \Auth::user()->id)->exists())
	    	\DB::table('analytic')->where('user_id', \Auth::user()->id)->update([
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
	    else
	    	\DB::table('analytic')->insert([
	    		'entity' => $entity,
		    	'field' => $nfield,
		    	'group_by' => $group_by,
		    	'period_start' => $period_start,
		    	'period_end' => $period_end,
		    	'acondition' => $condition,
		    	'sort_field' => $sort_field,
		    	'sort_order' => $sort_order,
		    	'active_rows' => json_encode(array_keys($data)),
		    	'user_id' => \Auth::user()->id,
		    	'type' => 'gibdd_queries'
	    	]);

    	return response()->json($res);
    	
    }

    public function settings(Request $request)
    {
    	if($request->type && $request->id) {
    		$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type);
	    	$res = $res->first();
	    	$active_rows = json_decode($res->active_rows, true);
	    	if(in_array($request->id, $active_rows)) {
	    		unset($active_rows[array_search($request->id, $active_rows)]);
	    	} else {
	    		$active_rows[] = (int)$request->id;
	    	}
	    	$active_rows = array_values($active_rows);
    		$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->update(['active_rows' => json_encode($active_rows)]);
    	} elseif($request->type && $request->config) {
    		$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->update(['config' => json_encode($request->config)]);
    	}
    }

    public function get_settings(Request $request)
    {
    	$res = \DB::table('analytic')->where('user_id', \Auth::user()->id)->where('type', $request->type)->first();
    	if($res) {
	    	$res = json_decode($res->config, true);
		    	
		    return response()->json($res);
	    } else {
	    	return response()->json([]);
	    }
    }*/

}