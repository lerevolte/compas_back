<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HealthService;

class HealthController extends Controller
{
    private HealthService $healthService;

    public function __construct(HealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function index()
    {
        $data = $this->healthService->check();

        $status = ($data['database']['status'] ?? 'down') === 'up' ? 200 : 503;

        return response()->json($data, $status);
    }
}
