<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CarrierSalaryService;
use Illuminate\Http\Request;

class CarrierSalaryController extends Controller
{
    public function companies(Request $request, CarrierSalaryService $service)
    {
        [$from, $to] = $service->period($request);

        return response()->json($service->companies($from, $to));
    }

    public function cars(Request $request, CarrierSalaryService $service)
    {
        [$from, $to] = $service->period($request);
        $companyId = (int) $request->company_id;
        if (!$companyId) {
            return response()->json(['all' => null, 'none' => null, 'cars' => []]);
        }

        return response()->json($service->cars($from, $to, $companyId));
    }

    public function routes(Request $request, CarrierSalaryService $service)
    {
        [$from, $to] = $service->period($request);
        $companyId = (int) $request->company_id;
        if (!$companyId) {
            return response()->json([]);
        }
        $ids = $service->routeIds($from, $to, $companyId, (string) ($request->car ?? 'all'));
        $table = collect(\App\Models\Table::get('routes'))->map(fn ($col) => (array) $col)->values()->all();
        if (empty($ids)) {
            return response()->json(['list' => ['data' => []], 'table' => $table]);
        }
        $listed = \App\Models\EntityObject::list('routes', new Request(['ids' => $ids, 'per_page' => count($ids)]));

        return response()->json([
            'list' => [
                'data' => array_values($listed['data'] ?? []),
                'sort_field' => $listed['sort_field'] ?? null,
                'sort_order' => $listed['sort_order'] ?? null,
            ],
            'table' => $table,
        ]);
    }
}
