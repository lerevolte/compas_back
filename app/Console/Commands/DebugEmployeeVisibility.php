<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class DebugEmployeeVisibility extends Command
{
    protected $signature = 'debug:employee-8720 {tenant}';
    protected $description = 'Диагностика 8720: поле Сотрудник в деталке задач и привязка сотрудник→пользователь';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (!$tenant) {
            $this->error('Тенант не найден: '.$this->argument('tenant'));
            return self::FAILURE;
        }

        $tenant->run(function () {
            $db = \DB::connection();
            $out = [];

            $tasksTypeId = $db->table('data_types')->where('slug', 'logistic_tasks')->value('id');
            $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
            $empTypeId = $db->table('data_types')->where('slug', 'employees')->value('id');

            $out['type_ids'] = ['logistic_tasks' => $tasksTypeId, 'routes' => $routesTypeId, 'employees' => $empTypeId];
            $out['has_column_lt_employee_id'] = \Schema::hasColumn('logistic_tasks', 'employee_id');
            $out['pivot_lt_employee_exists'] = \Schema::hasTable('logistic_task_employee');

            $out['lt_employee_field_row'] = $db->table('data_rows')
                ->where('data_type_id', $tasksTypeId)->where('field', 'employee_id')
                ->first(['id', 'section_id', 'module', 'module_section_id', 'hide', 'is_remove', 'group_id', 'is_plural', 'relation_table', 'related_field', 'type', 'title', 'details', 'roles_read', 'sort', 'only_read']);

            $out['lt_sections'] = $db->table('field_sections')->where('page', 'logistic_tasks')
                ->get(['id', 'name', 'column_id', 'module', 'hide', 'parent_id'])->toArray();

            $out['routes_task_id_row'] = $db->table('data_rows')
                ->where('data_type_id', $routesTypeId)->where('field', 'task_id')
                ->first(['id', 'is_plural', 'relation_table', 'details', 'type', 'hide', 'is_remove', 'section_id']);

            $out['employees_option_fields'] = $db->table('data_rows')
                ->where('data_type_id', $empTypeId)
                ->whereIn('type', ['relation', 'select_dropdown'])
                ->get(['id', 'field', 'title', 'type', 'relation_table', 'details', 'is_plural', 'hide', 'is_remove'])->toArray();

            $out['user_link_columns'] = \App\Models\Employee::userLinkColumns();

            $out['employees'] = $db->table('employees')->whereNull('deleted_at')
                ->get(['id', 'name', 'user_id', 'related_user_id'])->toArray();

            $out['users'] = $db->table('users')->get(['id', 'email', 'is_admin', 'employee_id'])->toArray();

            foreach ($out['users'] as $u) {
                $userModel = \App\Models\User::find($u->id);
                $out['employee_ids_for_user'][$u->id] = $userModel ? \App\Models\Employee::idsForUser($userModel) : null;
            }

            $sampleRoute = $db->table('routes')->whereNull('deleted_at')
                ->whereIn('id', function ($q) {
                    $q->select('route_id')->from('logistic_tasks')->whereNotNull('route_id');
                })
                ->orderByDesc('id')->first(['id', 'task_id']);
            if ($sampleRoute) {
                $out['sample_route'] = [
                    'id' => $sampleRoute->id,
                    'task_id_column_raw' => $sampleRoute->task_id,
                    'tasks_by_sort' => $db->table('logistic_tasks')->where('route_id', $sampleRoute->id)->orderBy('sort')->pluck('id', 'sort')->toArray(),
                ];
            }

            $out['entity_permissions'] = $db->table('permissions')
                ->whereIn('entity_id', array_filter([$tasksTypeId, $routesTypeId]))
                ->get()->toArray();

            $this->line(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        });

        return self::SUCCESS;
    }
}
