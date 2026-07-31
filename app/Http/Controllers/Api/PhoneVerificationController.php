<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    private PhoneVerificationService $service;

    public function __construct(PhoneVerificationService $service)
    {
        $this->service = $service;
    }

    public function initiate(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:30']);
        return response()->json($this->service->initiate($request->input('phone'), $request->ip()));
    }

    public function status(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        return response()->json($this->service->status((int) $request->input('id')));
    }
}
