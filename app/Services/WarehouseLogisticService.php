<?php

namespace App\Services;

use App\Models\WarehouseTask;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseLogisticService
{
    public const SHIFTS = 'work_shifts';
    public const TASKS = 'warehouse_tasks';
    public const DEFAULT_DURATION = 60;

    public const SHIFT_DEFAULTS = [
        'start_time' => '09:00',
        'end_time' => '18:00',
        'break_start' => '13:00',
        'break_end' => '14:00',
    ];

    public static function ready(): bool
    {
        return Schema::hasTable(self::SHIFTS) && Schema::hasTable(self::TASKS)
            && DB::table('data_types')->whereIn('slug', [self::SHIFTS, self::TASKS])->count() === 2;
    }

    public static function minutes($value): ?int
    {
        if (!is_string($value) || !preg_match('/^\s*(\d{1,2})[:.](\d{2})\s*$/', $value, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h > 24 || $i > 59 || ($h === 24 && $i > 0)) {
            return null;
        }

        return $h * 60 + $i;
    }

    public static function time(int $minutes): string
    {
        $minutes = max(0, min(24 * 60, $minutes));

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function relationId($value): ?int
    {
        return RelationFieldsService::ids($value)[0] ?? null;
    }

    public static function shiftIdVariants(int $shiftId): array
    {
        return [(string) $shiftId, '[' . $shiftId . ']', '["' . $shiftId . '"]'];
    }

    public static function shiftName($shift): string
    {
        $date = $shift->shift_date ? date('d.m.Y', strtotime((string) $shift->shift_date)) : null;
        $employeeId = self::relationId($shift->employee_id);
        $employee = $employeeId ? DB::table('employees')->where('id', $employeeId)->value('name') : null;
        $parts = array_filter([$date ? 'Смена ' . $date : 'Смена', $employee]);

        return implode(', ', $parts);
    }

    public static function syncTaskDates(int $shiftId, $date): void
    {
        DB::table(self::TASKS)
            ->whereIn('work_shift_id', self::shiftIdVariants($shiftId))
            ->whereNull('deleted_at')
            ->update(['task_date' => $date ? date('Y-m-d', strtotime((string) $date)) : null]);
    }

    public static function statusFieldId(): ?int
    {
        $typeId = DB::table('data_types')->where('slug', self::TASKS)->value('id');

        return $typeId ? (int) DB::table('data_rows')->where('data_type_id', $typeId)->where('field', 'status')->value('id') ?: null : null;
    }

    public static function defaultTaskStatus(): ?int
    {
        $fieldId = self::statusFieldId();

        return $fieldId ? DB::table('field_values')->where('field_id', $fieldId)->where('is_hidden', '!=', 1)
            ->orderBy('sort')->orderBy('id')->value('id') : null;
    }

    public static function normalizeTask(WarehouseTask $task): void
    {
        foreach (['start_time', 'end_time'] as $field) {
            $minutes = self::minutes((string) $task->{$field});
            if ($minutes !== null) {
                $task->{$field} = self::time($minutes);
            }
        }

        if ($task->isDirty('work_shift_id')) {
            $shiftId = self::relationId($task->work_shift_id);
            if ($shiftId) {
                $date = DB::table(self::SHIFTS)->where('id', $shiftId)->value('shift_date');
                if ($date) {
                    $task->task_date = $date;
                }
            }
        }

        $start = self::minutes((string) $task->start_time);
        $end = self::minutes((string) $task->end_time);
        if ($start !== null && $end !== null && $end > $start) {
            $task->duration = $end - $start;
        } elseif ($start !== null && $end === null && (int) $task->duration > 0) {
            $task->end_time = self::time($start + (int) $task->duration);
        }
    }

    public static function statusMap(): array
    {
        $fieldId = self::statusFieldId();
        if (!$fieldId) {
            return [];
        }

        return DB::table('field_values')->where('field_id', $fieldId)->get(['id', 'value', 'color'])
            ->mapWithKeys(fn ($v) => [(int) $v->id => ['id' => (int) $v->id, 'value' => $v->value, 'color' => $v->color]])
            ->all();
    }

    public static function shiftDay(int $shiftId): ?array
    {
        $shift = DB::table(self::SHIFTS)->where('id', $shiftId)->whereNull('deleted_at')->first();
        if (!$shift) {
            return null;
        }
        $employeeId = self::relationId($shift->employee_id);
        $employee = $employeeId ? DB::table('employees')->where('id', $employeeId)->first(['id', 'name']) : null;
        $statuses = self::statusMap();

        $tasks = DB::table(self::TASKS)
            ->whereIn('work_shift_id', self::shiftIdVariants($shiftId))
            ->whereNull('deleted_at')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get(['id', 'name', 'start_time', 'end_time', 'duration', 'status', 'color'])
            ->map(fn ($t) => [
                'id' => (int) $t->id,
                'name' => $t->name,
                'start_time' => $t->start_time,
                'end_time' => $t->end_time,
                'duration' => $t->duration !== null && $t->duration !== '' ? (int) $t->duration : null,
                'status' => $statuses[(int) self::relationId($t->status)] ?? null,
            ])
            ->values()
            ->all();

        return [
            'shift' => [
                'id' => (int) $shift->id,
                'name' => $shift->name,
                'shift_date' => $shift->shift_date,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'break_start' => $shift->break_start,
                'break_end' => $shift->break_end,
                'employee' => $employee ? ['id' => (int) $employee->id, 'name' => $employee->name] : null,
            ],
            'tasks' => $tasks,
        ];
    }

    public static function fieldId(string $slug, string $field): ?int
    {
        $typeId = DB::table('data_types')->where('slug', $slug)->value('id');

        return $typeId ? ((int) DB::table('data_rows')->where('data_type_id', $typeId)->where('field', $field)->value('id') ?: null) : null;
    }

    public function createShift(string $date, ?int $employeeId, ?int $storehouseId = null, array $times = []): array
    {
        $row = [
            'id' => 0,
            'shift_date' => date('Y-m-d', strtotime($date)),
            'employee_id' => $employeeId,
        ];
        foreach (self::SHIFT_DEFAULTS as $field => $default) {
            $minutes = self::minutes($times[$field] ?? null);
            $row[$field] = $minutes !== null ? self::time($minutes) : $default;
        }
        if ($storehouseId && Schema::hasColumn(self::SHIFTS, 'storehouse_id')) {
            $row['storehouse_id'] = $storehouseId;
        }

        return app(CrudService::class)->batch(self::SHIFTS, [$row]);
    }

    public function createTask(int $shiftId, string $start, string $end, ?string $name = null): array
    {
        return app(CrudService::class)->batch(self::TASKS, [[
            'id' => 0,
            'name' => $name ?: 'Новая задача',
            'work_shift_id' => $shiftId,
            'start_time' => $start,
            'end_time' => $end,
        ]]);
    }

    public function schedule(int $taskId, ?int $shiftId, ?string $start, ?string $end): array
    {
        $row = [
            'id' => $taskId,
            'work_shift_id' => $shiftId,
            'start_time' => $shiftId ? $start : null,
            'end_time' => $shiftId ? $end : null,
        ];

        return app(CrudService::class)->batch(self::TASKS, [$row]);
    }

    public function fromWarehouse(int $warehouseId, int $shiftId, string $start): array
    {
        $warehouse = DB::table('warehouses')->where('id', $warehouseId)->whereNull('deleted_at')->first();
        if (!$warehouse) {
            return ['status' => 404, 'message' => 'Быстрая задача не найдена'];
        }
        $startMinutes = self::minutes($start) ?? 9 * 60;
        $duration = is_numeric($warehouse->service_time ?? null) && (int) $warehouse->service_time > 0
            ? (int) $warehouse->service_time
            : self::DEFAULT_DURATION;

        $row = [
            'id' => 0,
            'name' => $warehouse->name ?: 'Новая задача',
            'work_shift_id' => $shiftId,
            'start_time' => self::time($startMinutes),
            'end_time' => self::time($startMinutes + $duration),
            'comment' => $warehouse->comment ?? null,
            'photo' => $warehouse->photo ?? null,
        ];
        $result = app(CrudService::class)->batch(self::TASKS, [$row]);
        if (!empty($result['id'])) {
            \App\Models\ObjectRelation::link('warehouses', $warehouseId, self::TASKS, (int) $result['id']);
        }

        return $result;
    }
}
