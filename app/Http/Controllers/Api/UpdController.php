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

        $withDocs = filter_var($request->input('docs'), FILTER_VALIDATE_BOOLEAN);
        $output = UpdService::output((string) $model, (array) $ids, $withDocs);
        if ($output === null) {
            return response()->json(['message' => 'Не удалось сформировать УПД'], 422);
        }

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="upd.pdf"',
        ]);
    }
}
