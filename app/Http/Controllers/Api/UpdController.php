<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UpdService;
use Illuminate\Http\Request;

class UpdController extends Controller
{
    public function print($model, Request $request)
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $pdf = UpdService::pdf((string) $model, (array) $ids);
        if (!$pdf) {
            return response()->json(['message' => 'Не удалось сформировать УПД'], 422);
        }

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="upd.pdf"',
        ]);
    }
}
