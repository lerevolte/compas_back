<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WarehouseLogisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseLogisticController extends Controller
{
    public function __construct(private WarehouseLogisticService $service)
    {
        $this->middleware('logistic.read:' . WarehouseLogisticService::SHIFTS);
    }

    public function config(): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return response()->json(['ready' => false]);
        }

        return response()->json([
            'ready' => true,
            'employee_field_id' => WarehouseLogisticService::fieldId(WarehouseLogisticService::SHIFTS, 'employee_id'),
            'company_field_id' => WarehouseLogisticService::fieldId('employees', 'company_id'),
            'shift_defaults' => WarehouseLogisticService::SHIFT_DEFAULTS,
            'default_duration' => WarehouseLogisticService::DEFAULT_DURATION,
        ]);
    }

    public function shift($id): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return $this->notInstalled();
        }
        $day = WarehouseLogisticService::shiftDay((int) $id);

        return $day ? response()->json($day) : response()->json(['message' => 'Смена не найдена'], 404);
    }

    public function store_shift(Request $request): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return $this->notInstalled();
        }
        $data = $request->validate([
            'date' => 'required|date',
            'employee_id' => 'nullable|integer',
            'storehouse_id' => 'nullable|integer',
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'break_start' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'break_end' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        return $this->respond($this->service->createShift(
            $data['date'],
            $data['employee_id'] ?? null,
            $data['storehouse_id'] ?? null,
            array_intersect_key($data, WarehouseLogisticService::SHIFT_DEFAULTS)
        ));
    }

    public function store_task(Request $request): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return $this->notInstalled();
        }
        $data = $request->validate([
            'work_shift_id' => 'required|integer',
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'end_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'name' => 'nullable|string|max:500',
        ]);

        return $this->respond($this->service->createTask((int) $data['work_shift_id'], $data['start_time'], $data['end_time'], $data['name'] ?? null));
    }

    public function schedule_task(Request $request, $id): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return $this->notInstalled();
        }
        $data = $request->validate([
            'work_shift_id' => 'nullable|integer',
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        return $this->respond($this->service->schedule(
            (int) $id,
            !empty($data['work_shift_id']) ? (int) $data['work_shift_id'] : null,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null
        ));
    }

    public function task_from_warehouse(Request $request): JsonResponse
    {
        if (!WarehouseLogisticService::ready()) {
            return $this->notInstalled();
        }
        $data = $request->validate([
            'warehouse_id' => 'required|integer',
            'work_shift_id' => 'required|integer',
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        return $this->respond($this->service->fromWarehouse((int) $data['warehouse_id'], (int) $data['work_shift_id'], $data['start_time']));
    }

    private function respond($result): JsonResponse
    {
        if (is_array($result) && isset($result['status']) && (int) $result['status'] >= 400) {
            return response()->json(['message' => $result['message'] ?? 'Ошибка'], (int) $result['status']);
        }

        return response()->json(['ok' => true, 'id' => is_array($result) ? ($result['id'] ?? null) : null]);
    }

    private function notInstalled(): JsonResponse
    {
        return response()->json(['message' => 'Складская логистика не установлена на портале'], 404);
    }
}
