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

        $docKeys = $request->input('doc_keys');
        if (is_string($docKeys)) {
            $docKeys = explode(',', $docKeys);
        }
        $docKeys = is_array($docKeys)
            ? array_values(array_filter(array_map('trim', $docKeys), fn ($key) => $key !== ''))
            : null;

        $includeUpd = $request->has('upd')
            ? filter_var($request->input('upd'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $output = UpdService::output((string) $model, (array) $ids, $withDocs, $docKeys, $includeUpd);
        if ($output === null) {
            return response()->json(['message' => 'Не удалось сформировать документы для печати'], 422);
        }

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="upd.pdf"',
        ]);
    }
}
