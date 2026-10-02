<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OneC\OneCExportService;
use Illuminate\Http\Request;

class OneCController extends Controller
{
    public function documents(Request $request)
    {
        $config = OneCExportService::config();
        if (!$config) {
            return response()->json(['ok' => false, 'error' => 'Обмен с 1С не настроен'], 404);
        }
        if (!OneCExportService::authorized($config, $this->token($request))) {
            return response()->json(['ok' => false, 'error' => 'Неверный токен'], 403);
        }
        $documents = OneCExportService::pending($config);

        return response()->json(['ok' => true, 'count' => count($documents), 'documents' => $documents], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function confirm(Request $request)
    {
        $config = OneCExportService::config();
        if (!$config) {
            return response()->json(['ok' => false, 'error' => 'Обмен с 1С не настроен'], 404);
        }
        if (!OneCExportService::authorized($config, $this->token($request))) {
            return response()->json(['ok' => false, 'error' => 'Неверный токен'], 403);
        }
        $items = [];
        foreach ((array) $request->input('documents', []) as $document) {
            if (is_array($document)) {
                $items[] = ['number' => $document['number'] ?? null, 'ref' => $document['ref'] ?? null];
            }
        }
        $numbers = $request->input('numbers', $request->input('number'));
        if (is_string($numbers)) {
            $numbers = explode(',', $numbers);
        }
        foreach ((array) $numbers as $number) {
            if (is_scalar($number)) {
                $items[] = ['number' => (string) $number, 'ref' => null];
            }
        }
        if (!count($items)) {
            return response()->json(['ok' => false, 'error' => 'Не переданы номера документов'], 422);
        }
        $result = OneCExportService::confirm($items);

        return response()->json(['ok' => true] + $result, 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function token(Request $request): ?string
    {
        $token = $request->query('token') ?? $request->header('X-Compas-Token') ?? $request->bearerToken();

        return is_string($token) ? trim($token) : null;
    }
}
