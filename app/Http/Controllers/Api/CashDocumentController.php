<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CashDocumentService;
use Illuminate\Http\JsonResponse;

class CashDocumentController extends Controller
{
    public function operation($id): JsonResponse
    {
        $operation = CashDocumentService::operationFor((int) $id);
        if (!$operation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($operation);
    }
}
