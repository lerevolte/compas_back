<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Task;
use App\Models\FactRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\RouteAnalysisService;

class MapController extends Controller
{
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function show(Request $request)
    {
        // \App\Models\SidebarItem::fixTree();
        // $s = app('settings');

        // if(!$s)
        //     $s = \App\Models\Settings::update();
        // $sidebar_items2 = $s['settings']['sidebar_items'];
        // dd(\App\Models\SidebarItem::defaultOrder()->where('enabled', 1)->get()->toTree()->toArray());
        // $sidebar_items = [];
        // foreach($sidebar_items2 as $item) {
        //     $sidebar_items[$item['id']] = $item;
        // }
        // $user_id = 1;
        // //$item = cache('sidebar-'.$user_id);
        // $cache_name = tenant('id').':sidebarmenu-'.$user_id;
        // $item = cache()->getMemcached()->get($cache_name);
        // // if($this->role)
        // //     $permissions = $this->role->permissions->keyBy('entity_id');
        // if(!$item) {
        //     // $item = cache()->rememberForever('sidebar-'.$user_id, function() use ($user_id)
        //     // {
        //         $item = \DB::table('settings')->where([
        //             'type' => 'sidebar',
        //             'user_id' => $user_id
        //         ])->first();
        //         cache()->getMemcached()->add($cache_name, $item);
        //         //return $data;
        //     //});
        // }
        // $tariff = \App\Models\Tariff::current();
        // $blocked_pages = [];
        // if($tariff->restrictions) {
        //     $restrictions_tariff = json_decode($tariff->restrictions,true);
        //     if(isset($restrictions_tariff['blocked_pages'])) {
        //         $blocked_pages = $restrictions_tariff['blocked_pages'];
        //     }
        // }
        // $sidebar_ids = array();

        // if(!$item) {
        //     // if($this->role_id) {
        //     //     $menu = $this->role->sidebar;
        //     // }
        //     if(!isset($menu) || !$menu) {
        //         $all_menu = \DB::table('settings')->where([
        //             'type' => 'sidebar',
        //             'user_id' => 1
        //         ])->first();
        //         if($all_menu)
        //             $menu = $all_menu->value;
        //         else
        //             $menu = json_encode($sidebar_items, JSON_UNESCAPED_UNICODE);
        //     }
            
        //     \DB::table('settings')->insert([
        //         'key' => 'sidebar',
        //         'display_name' => 'Sidemenu',
        //         'value' => $menu,
        //         'type' => 'sidebar',
        //         'user_id' => $this->id
        //     ]);
        //     $item = \DB::table('settings')->where([
        //         'type' => 'sidebar',
        //         'user_id' => $this->id
        //     ])->first();
        //     $menu = json_decode($item->value, true);
        // } else {
        //     $need_create = false;
        //     $menu = json_decode($item->value, true);
        //     $max_id = 0;
        //     $ids = array();
        //     if(is_array($menu))
        //         foreach($menu as $k => $menu_item) {
        //             if(isset($menu_item['id']))
        //                 $ids[] = $menu_item['id'];
        //         }
        //     else
        //         $menu = array();
        //     foreach($sidebar_items as $sidebar_item) {
        //         $sidebar_ids[] = $sidebar_item['id'];
        //         if(!in_array($sidebar_item['id'], $ids)) {
        //             $need_create = true;
        //             $menu[] = $sidebar_item;
        //         }
        //     }
        //     if($need_create) {
        //         \DB::table('settings')->where('id', $item->id)->update(['value' => json_encode($menu)]);
        //     }
        // }

        // foreach($menu as $k => $item) {
            
        //     if(isset($item['children'])) {
        //         foreach($item['children'] as $i => $child) {
        //             if(!in_array($child['id'], $sidebar_ids) || in_array($child['slug'], $blocked_pages)) {
        //                 unset($menu[$k]['children'][$i]);
        //             }
        //             if(isset($sidebar_items[$child['id']]) && $sidebar_items[$child['id']]['enabled'] && isset($item['children'][$i])) {
        //                 //$menu[$k]['children'][$i]['enabled'] = 1;
        //             }
                    
        //             if(isset($child['slug']) && isset($s['models'][$child['slug']]) && 
        //                 isset($permissions[$s['models'][$child['slug']]->id]) && 
        //                 $permissions[$s['models'][$child['slug']]->id]->read_p == 'N'
        //             ) {
        //                 unset($menu[$k]['children'][$i]);
        //             }
        //             if(isset($child['slug']) && ($child['slug'] == 'settings' || $child['slug'] == 'modules' || $child['slug'] == 'trash' || $child['slug'] == 'roles' || $child['slug'] == 'tariffs') && !$this->isAdmin()) {
        //                 unset($menu[$k]['children'][$i]);
        //             }
        //         }
        //     }
        //     if(isset($item['is_group']) && $item['is_group']) {
        //         $menu[$k]['children'] = array_values($menu[$k]['children']);
        //         if(!count($menu[$k]['children']))
        //             unset($menu[$k]);
        //         continue;
        //     }
        //     if(!in_array($item['id'], $sidebar_ids) || in_array($item['slug'], $blocked_pages)) {
        //         unset($menu[$k]);
        //     }
        //     if(isset($sidebar_items[$item['id']]) && $sidebar_items[$item['id']]['enabled'] && isset($menu[$k])) {
        //         //$menu[$k]['enabled'] = 1;
        //     }
            
        //     if(isset($item['slug']) && isset($s['models'][$item['slug']]) && 
        //         isset($permissions[$s['models'][$item['slug']]->id]) && 
        //         $permissions[$s['models'][$item['slug']]->id]->read_p == 'N'
        //     ) {
        //         unset($menu[$k]);
        //     }
        // }
        // // if(!$this->isAdmin())
        // //     foreach($menu as $k => $item) {
        // //         if(isset($item['slug']) && ($item['slug'] == 'settings' || $item['slug'] == 'modules' || $item['slug'] == 'trash' || $item['slug'] == 'roles' || $item['slug'] == 'tariffs')) {
        // //             unset($menu[$k]);
        // //         }
        // //     }

        // return array_values($menu);

        $radius = 500;
        $date = $request->date;
        try {
            $validatedDate = Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            abort(404, 'Неверный формат даты.');
        }

        $routesFromDb = Route::where('date', $validatedDate)
                             ->with(['tasks', 'tasks.status'])
                             ->get();
        
        $routesForJs = $routesFromDb->map(function ($route) use ($validatedDate, $radius) {

            $actualPathData = [];
            if ($route->employee_id) {
                $factDate = Carbon::parse($validatedDate)->format('d.m.Y');

                // Теперь получаем не только координаты, но и скорость со временем
                $actualPathData = FactRoute::where('user_id', $route->employee_id)
                    ->where('date', $factDate)
                    ->orderBy('time', 'asc')
                    ->get(['latitude', 'longitude', 'speed', 'time']) // Получаем нужные поля
                    ->map(function ($point) {
                        // Форматируем в удобный объект
                        return [
                            'lat'   => (float)$point->latitude,
                            'lon'   => (float)$point->longitude,
                            'speed' => (float)$point->speed,
                            'time'  => Carbon::parse($point->time)->format('H:i'),
                        ];
                    })
                    ->toArray();
            }

            $tasksForJs = $route->tasks->map(function ($task, $key) {
                $taskName = mb_convert_encoding($task->name, 'UTF-8', 'UTF-8');
                $addressJson = mb_convert_encoding($task->address, 'UTF-8', 'UTF-8');

                if ($this->isJson($taskName)) {
                    $decodedName = json_decode($taskName, true);
                    $taskName = $decodedName['value'] ?? 'Без названия';
                }

                return [
                    'id' => $task->id,
                    'order' => $key + 1,//$task->order, 
                    'name' => $taskName,
                    'address' => $task->address, // Это уже JSON-строка
                    'factTime' => $task->fact_time ?? '', // Если есть фактическое время
                    'statusColor' => $task->status->color ?? '#ccc',
                    'service_time' => $task->service_time ?? 0
                ];
            });
            $allTasksForRoute = $tasksForJs;
            //dd($allTasksForRoute);
            $analysisService = new RouteAnalysisService($allTasksForRoute->toArray(), $radius);
            $analysisResult = $analysisService->analyze($route->employee_id, Carbon::parse($validatedDate)->format('d.m.Y'));

            return [
                'id' => $route->id,
                'name' => $route->name,
                'loading_time' => $route->loading_time ?? '7:00',
                'color' => $route->color ?? '#8601ff',
                'tasks' => $tasksForJs,
                'actual_path' => $actualPathData,
                'service_stops' => $analysisResult['serviceStops'],
                'parking_stops' => $analysisResult['parkingStops'],
            ];
        });
        
        $unassignedTasksFromDb = Task::whereNull('route_id')
                                     ->where('delivery_date', $validatedDate)
                                     ->with('status')
                                     ->get();

        $unassignedTasksForJs = $unassignedTasksFromDb->map(function ($task) {
            $taskName = $task->name;
            if ($this->isJson($taskName)) {
                $decodedName = json_decode($taskName, true);
                $taskName = $decodedName['value'] ?? 'Без названия';
            }
            
            // Убедитесь, что у задачи есть поля planned_time и fact_time
            return [
                'id' => $task->id,
                'name' => $taskName,
                'address' => $task->address,
                'planned_time' => $task->planned_time ? Carbon::parse($task->planned_time)->format('H:i') : null,
                'fact_time' => $task->fact_time ? Carbon::parse($task->fact_time)->format('H:i') : null,
                'statusColor' => $task->status->color ?? '#ccc',
            ];
        });

        return view('leaflet.show', [
            'routes' => $routesForJs,
            'unassignedTasks' => $unassignedTasksForJs,
            'radius' => $radius
        ]);
    }
}