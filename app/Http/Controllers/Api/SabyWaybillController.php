<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
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

        $waybills = SabyWaybill::where('route_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($item) => $this->present($item));

        return response()->json([
            'enabled' => true,
            'data' => $waybills,
        ]);
    }

    public function store($id)
    {
        $service = SabyWaybillService::make();
        if (!$service) {
            return response()->json(['message' => 'Модуль Saby не настроен'], 422);
        }

        $route = Route::find($id);
        if (!$route) {
            return response()->json(['message' => 'Маршрут не найден'], 404);
        }

        try {
            $waybill = $service->create($route);
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

        $route = Route::find($id);
        if (!$route) {
            return response()->json(['message' => 'Маршрут не найден'], 404);
        }

        return response()->json([
            'enabled' => true,
            'errors' => $service->validate($route),
        ]);
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

    private function present(SabyWaybill $waybill): array
    {
        return [
            'id' => $waybill->id,
            'route_id' => $waybill->route_id,
            'doc_id' => $waybill->doc_id,
            'number' => $waybill->number,
            'date' => $waybill->date,
            'status' => $waybill->status,
            'pdf_url' => $waybill->pdf_url,
            'cabinet_url' => $waybill->cabinet_url,
            'qr_url' => $waybill->qr_url,
            'created_at' => optional($waybill->created_at)->format('d.m.Y H:i'),
        ];
    }
}
