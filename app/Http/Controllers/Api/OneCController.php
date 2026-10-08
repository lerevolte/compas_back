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
        $items = $this->confirmItems($request);
        if (!count($items)) {
            \Log::warning('1C confirm: номера не распознаны', [
                'content_type' => $request->header('Content-Type'),
                'query' => $request->except('token'),
                'body' => mb_substr((string) $request->getContent(), 0, 2000),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Не переданы номера документов',
                'expected' => ['numbers' => ['cmps-1', 'cmps-2']],
                'received' => [
                    'content_type' => $request->header('Content-Type'),
                    'body' => mb_substr((string) $request->getContent(), 0, 500),
                ],
            ], 422, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $result = OneCExportService::confirm($items);

        return response()->json(['ok' => true] + $result, 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function confirmItems(Request $request): array
    {
        $payload = $request->except('token');
        $raw = trim((string) $request->getContent());
        if ($raw !== '') {
            $decoded = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);
            if (is_array($decoded)) {
                $payload = array_is_list($decoded) ? ['documents' => $decoded] : $decoded + $payload;
            } elseif (!count($payload)) {
                $payload = ['numbers' => $raw];
            }
        }
        $items = [];
        foreach ($payload as $key => $value) {
            $key = mb_strtolower((string) $key);
            if (in_array($key, ['documents', 'docs', 'items', 'data', 'документы'], true)) {
                foreach (is_array($value) ? $value : [$value] as $document) {
                    $items = array_merge($items, $this->confirmItem($document));
                }
            } elseif (in_array($key, ['numbers', 'number', 'номер', 'номера'], true)) {
                $values = is_string($value) ? preg_split('/[\s,;]+/', $value) : (array) $value;
                foreach ($values as $number) {
                    $items = array_merge($items, $this->confirmItem($number));
                }
            }
        }

        return $items;
    }

    private function confirmItem($document): array
    {
        if (is_scalar($document)) {
            $number = trim((string) $document);

            return $number === '' ? [] : [['number' => $number, 'ref' => null]];
        }
        if (!is_array($document)) {
            return [];
        }
        $fields = [];
        foreach ($document as $key => $value) {
            $fields[mb_strtolower((string) $key)] = $value;
        }
        $number = $fields['number'] ?? $fields['номер'] ?? null;
        if (!is_scalar($number) || trim((string) $number) === '') {
            return [];
        }
        $ref = $fields['ref'] ?? $fields['ссылка'] ?? $fields['guid'] ?? $fields['id_1c'] ?? null;

        return [['number' => trim((string) $number), 'ref' => is_scalar($ref) ? (string) $ref : null]];
    }

    private function token(Request $request): ?string
    {
        $token = $request->query('token') ?? $request->header('X-Compas-Token') ?? $request->bearerToken();

        return is_string($token) ? trim($token) : null;
    }
}
