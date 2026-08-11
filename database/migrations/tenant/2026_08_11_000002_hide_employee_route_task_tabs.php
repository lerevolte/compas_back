<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tabs = [
        'route_id' => ['title' => 'Маршруты', 'slug' => 'routes'],
        'logistic_task_id' => ['title' => 'Задачи логистики', 'slug' => 'logistic_tasks'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $fields = [];
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $employeesTypeId = DB::table('data_types')->where('slug', 'employees')->value('id');
            if ($employeesTypeId) {
                $fields = DB::table('data_rows')
                    ->where('data_type_id', $employeesTypeId)
                    ->whereIn('field', array_keys($this->tabs))
                    ->pluck('field')
                    ->all();
            }
        }

        $rows = DB::table('settings')
            ->where('entity', 'employees')
            ->where('type', 'menu')
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $menu = json_decode((string) $row->value, true);
            if (!is_array($menu)) {
                continue;
            }
            $changed = false;
            $present = [];
            $maxId = 0;
            foreach ($menu as $k => $item) {
                if (isset($item['id']) && $item['id'] > $maxId) {
                    $maxId = $item['id'];
                }
                if (isset($item['tab']) && isset($this->tabs[$item['tab']])) {
                    $present[$item['tab']] = true;
                    if (($item['enabled'] ?? null) != 0) {
                        $menu[$k]['enabled'] = 0;
                        $changed = true;
                    }
                }
            }
            foreach ($this->tabs as $tab => $meta) {
                if (isset($present[$tab]) || !in_array($tab, $fields, true)) {
                    continue;
                }
                $maxId++;
                $menu[] = [
                    'title' => $meta['title'],
                    'tab' => $tab,
                    'slug' => $meta['slug'],
                    'sort' => $maxId,
                    'enabled' => 0,
                    'id' => $maxId,
                ];
                $changed = true;
            }
            if ($changed) {
                DB::table('settings')->where('id', $row->id)->update([
                    'value' => json_encode($menu, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $rows = DB::table('settings')
            ->where('entity', 'employees')
            ->where('type', 'menu')
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $menu = json_decode((string) $row->value, true);
            if (!is_array($menu)) {
                continue;
            }
            $changed = false;
            foreach ($menu as $k => $item) {
                if (isset($item['tab']) && isset($this->tabs[$item['tab']]) && ($item['enabled'] ?? null) != 1) {
                    $menu[$k]['enabled'] = 1;
                    $changed = true;
                }
            }
            if ($changed) {
                DB::table('settings')->where('id', $row->id)->update([
                    'value' => json_encode($menu, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
