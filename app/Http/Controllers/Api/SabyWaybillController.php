<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\SabyWaybill;
use App\Services\Saby\SabyException;
use App\Services\Saby\SabyValidationException;
use App\Services\Saby\SabyWaybillService;
use Illuminate\Http\Request;

class SabyWaybillController extends Controller
{
    public function index($id)
    {
        if (!SabyWaybillService::ready()) {
            return response()->json(['enabled' => false, 'data' => []]);
        }

        $waybills = SabyWaybill::where('task_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($item) => $this->present($item));

        return response()->json([
            'enabled' => true,
            'data' => $waybills,
        ]);
    }

    public function store($id, Request $request)
    {
        $service = SabyWaybillService::make();
        if (!$service) {
            return response()->json(['message' => 'Модуль Saby не настроен'], 422);
        }

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Задача не найдена'], 404);
        }

        $loadingTask = null;
        if ($request->loading_task_id) {
            $loadingTask = Task::find($request->loading_task_id);
            if (!$loadingTask) {
                return response()->json(['message' => 'Точка погрузки не найдена'], 422);
            }
            if ($task->route_id && (int) $loadingTask->route_id !== (int) $task->route_id) {
                return response()->json(['message' => 'Точка погрузки должна быть из маршрута задачи'], 422);
            }
        }

        $massMethod = $request->mass_method !== null && $request->mass_method !== '' ? (string) $request->mass_method : null;
        if ($massMethod !== null && !isset(SabyWaybillService::MASS_METHODS[$massMethod])) {
            return response()->json(['message' => 'Неизвестный метод определения массы'], 422);
        }

        try {
            $waybill = $service->create($task, $loadingTask, $massMethod);
        } catch (SabyValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (SabyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->present($waybill)]);
    }

    public function check($id)
    {
        $service = SabyWaybillService::make();
        if (!$service) {
            return response()->json(['enabled' => false, 'errors' => []]);
        }

        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Задача не найдена'], 404);
        }

        return response()->json([
            'enabled' => true,
            'errors' => $service->validate($task),
        ]);
    }

    public function routeTasks($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Задача не найдена'], 404);
        }
        if (!$task->route_id) {
            return response()->json(['data' => [], 'route_id' => null, 'mass_methods' => $this->massMethods()]);
        }

        $tasks = Task::where('route_id', $task->route_id)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(function ($item) use ($task) {
                $name = (string) $item->name;
                $decoded = json_decode($name, true);
                if (is_array($decoded) && array_key_exists('value', $decoded)) {
                    $name = (string) $decoded['value'];
                }
                $address = '';
                $rawAddress = json_decode((string) $item->address, true);
                if (is_array($rawAddress)) {
                    $address = trim((string) ($rawAddress['text'] ?? ''));
                } elseif (is_string($item->address)) {
                    $address = trim($item->address);
                }
                return [
                    'id' => $item->id,
                    'name' => $name,
                    'address' => $address,
                    'plan_time' => $item->plan_time,
                    'is_current' => $item->id === $task->id,
                ];
            })
            ->values();

        return response()->json(['data' => $tasks, 'route_id' => $task->route_id, 'mass_methods' => $this->massMethods()]);
    }

    private function massMethods(): array
    {
        $out = [];
        foreach (SabyWaybillService::MASS_METHODS as $value => $label) {
            $out[] = ['value' => (string) $value, 'label' => $label];
        }
        return $out;
    }

    public function refresh($waybillId)
    {
        $service = SabyWaybillService::make();
        if (!$service) {
            return response()->json(['message' => 'Модуль Saby не настроен'], 422);
        }

        $waybill = SabyWaybill::find($waybillId);
        if (!$waybill) {
            return response()->json(['message' => 'Накладная не найдена'], 404);
        }

        try {
            $waybill = $service->refresh($waybill);
        } catch (SabyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->present($waybill)]);
    }

    public function destroy($waybillId)
    {
        $waybill = SabyWaybill::find($waybillId);
        if (!$waybill) {
            return response()->json(['message' => 'Накладная не найдена'], 404);
        }

        $service = SabyWaybillService::make();
        if ($service) {
            $service->delete($waybill);
        } else {
            $waybill->delete();
        }

        return response()->json(['success' => true]);
    }

    public function presentPublic(SabyWaybill $waybill): array
    {
        return $this->present($waybill);
    }

    public function presentLoadingTaskPublic($taskId): ?array
    {
        return $this->presentLoadingTask($taskId);
    }

    private function present(SabyWaybill $waybill): array
    {
        return [
            'id' => $waybill->id,
            'task_id' => $waybill->task_id,
            'route_id' => $waybill->route_id,
            'doc_id' => $waybill->doc_id,
            'number' => $waybill->number,
            'date' => $waybill->date,
            'status' => $waybill->status,
            'pdf_url' => $waybill->pdf_url,
            'cabinet_url' => $waybill->cabinet_url,
            'qr_url' => $waybill->qr_url,
            'created_at' => optional($waybill->created_at)->format('d.m.Y H:i'),
            'loading_task' => $this->presentLoadingTask($waybill->loading_task_id),
            'mass_method' => $waybill->mass_method ?? null,
            'mass_method_label' => SabyWaybillService::MASS_METHODS[(string) ($waybill->mass_method ?? '')] ?? null,
        ];
    }

    private function presentLoadingTask($taskId): ?array
    {
        if (!$taskId) {
            return null;
        }
        $task = Task::find($taskId);
        if (!$task) {
            return null;
        }
        $name = (string) $task->name;
        $decoded = json_decode($name, true);
        if (is_array($decoded) && array_key_exists('value', $decoded)) {
            $name = (string) $decoded['value'];
        }

        return ['id' => $task->id, 'name' => $name];
    }
}
