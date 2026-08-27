<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SabyOrder;
use App\Models\SabyWaybill;
use App\Models\Task;
use App\Services\Saby\SabyException;
use App\Services\Saby\SabyOrderService;
use App\Services\Saby\SabyValidationException;
use App\Services\Saby\SabyWaybillService;
use Illuminate\Http\Request;

class SabyOrderController extends Controller
{
    public function index($id)
    {
        if (!SabyOrderService::ready() || !SabyOrderService::tableReady()) {
            return response()->json(['enabled' => false, 'data' => [], 'waybills' => []]);
        }

        $orders = SabyOrder::where('task_id', $id)->orderByDesc('id')->get()->map(fn ($o) => $this->present($o));
        $waybills = [];
        try {
            $waybills = SabyWaybill::where('task_id', $id)->orderByDesc('id')->get()
                ->map(fn ($w) => app(SabyWaybillController::class)->presentPublic($w))->all();
        } catch (\Throwable $e) {
        }

        return response()->json(['enabled' => true, 'data' => $orders, 'waybills' => $waybills]);
    }

    public function store($id, Request $request)
    {
        $service = SabyOrderService::make();
        if (!$service || !SabyOrderService::tableReady()) {
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
            $order = $service->createOrder($task, $loadingTask, $massMethod);
        } catch (SabyValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (SabyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->present($order)]);
    }

    public function check($id)
    {
        $service = SabyOrderService::make();
        if (!$service) {
            return response()->json(['enabled' => false, 'errors' => []]);
        }
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Задача не найдена'], 404);
        }

        return response()->json(['enabled' => true, 'errors' => $service->validateOrder($task)]);
    }

    public function refresh($orderId)
    {
        $service = SabyOrderService::make();
        if (!$service) {
            return response()->json(['message' => 'Модуль Saby не настроен'], 422);
        }
        $order = SabyOrder::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }
        try {
            $order = $service->refreshOrder($order);
        } catch (SabyException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->present($order)]);
    }

    public function destroy($orderId)
    {
        $order = SabyOrder::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }
        $service = SabyOrderService::make();
        if ($service) {
            $service->deleteOrder($order);
        } else {
            $order->delete();
        }

        return response()->json(['success' => true]);
    }

    private function present(SabyOrder $order): array
    {
        $code = (string) ($order->state_code ?? '0');
        $waybill = null;
        if ($order->waybill_doc_id) {
            $waybill = [
                'doc_id' => $order->waybill_doc_id,
                'number' => $order->waybill_number,
                'date' => $order->waybill_date,
                'state' => $order->waybill_state,
                'stage' => $order->waybill_stage,
                'pdf_url' => $order->waybill_pdf_url,
                'cabinet_url' => $order->waybill_cabinet_url,
                'archive_url' => $order->waybill_archive_url,
                'qr_url' => $order->waybill_qr_url,
                'checked_at' => optional($order->waybill_checked_at ? \Carbon\Carbon::parse($order->waybill_checked_at) : null)?->format('d.m.Y H:i'),
            ];
        }

        return [
            'id' => $order->id,
            'task_id' => $order->task_id,
            'doc_id' => $order->doc_id,
            'number' => $order->number,
            'date' => $order->date,
            'state_code' => $code,
            'state_name' => $order->state_name,
            'state_note' => $order->state_note,
            'state_label' => SabyOrderService::ORDER_STATES[$code] ?? trim((string) ($order->state_name . ($order->state_note ? ' — ' . $order->state_note : ''))),
            'last_event' => $order->last_event,
            'pdf_url' => $order->pdf_url,
            'cabinet_url' => $order->cabinet_url,
            'archive_url' => $order->archive_url,
            'created_at' => optional($order->created_at)->format('d.m.Y H:i'),
            'synced_at' => optional($order->synced_at ? \Carbon\Carbon::parse($order->synced_at) : null)?->format('d.m.Y H:i'),
            'loading_task' => app(SabyWaybillController::class)->presentLoadingTaskPublic($order->loading_task_id),
            'mass_method' => $order->mass_method,
            'mass_method_label' => SabyWaybillService::MASS_METHODS[(string) ($order->mass_method ?? '')] ?? null,
            'waybill' => $waybill,
        ];
    }
}
