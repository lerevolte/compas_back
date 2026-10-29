<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OneC\OneCCashExportService;
use App\Services\OneC\OneCExportService;
use App\Services\OneC\OneCSupplierOrderService;
use App\Services\OneC\OneCProductionService;
use Illuminate\Http\Request;

class OneCController extends Controller
{
    public function callAction($method, $parameters)
    {
        return OneCExportService::asModuleUser(fn () => parent::callAction($method, $parameters));
    }

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
        if (!count($items) && $this->isEmptyList($request)) {
            return response()->json(['ok' => true, 'confirmed' => [], 'unknown' => []], 200, [], JSON_UNESCAPED_UNICODE);
        }
        if (!count($items)) {
            \Log::warning('1C confirm: номера не распознаны', [
                'content_type' => $request->header('Content-Type'),
                'query' => $request->except('token'),
                'body' => mb_substr((string) $request->getContent(), 0, 2000),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Не переданы номера документов',
                'expected' => ['documents' => [['number' => 'cmps-1', 'type' => 'expense_invoice', 'ref' => '<GUID 1С>', 'status' => 'created|posted|deleted']]],
                'received' => [
                    'content_type' => $request->header('Content-Type'),
                    'body' => mb_substr((string) $request->getContent(), 0, 500),
                ],
            ], 422, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $result = OneCExportService::confirm($items);

        return response()->json(['ok' => true] + $result, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function articles(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $articles = OneCCashExportService::pendingArticles();

        return response()->json(['ok' => true, 'count' => count($articles), 'articles' => $articles], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function articlesConfirm(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $items = $this->cashItems($request, ['articles', 'статьи'], ['code', 'код', 'id_1c', 'ref', 'ссылка']);
        if (!count($items)) {
            return $this->cashConfirmError($request, ['articles' => [['id' => 12, 'code' => '<код статьи в 1С>']]]);
        }

        return response()->json(['ok' => true] + OneCCashExportService::confirmArticles($items), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function cashDocuments(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $documents = OneCCashExportService::pendingDocuments(OneCExportService::config());

        return response()->json(['ok' => true, 'count' => count($documents), 'documents' => $documents], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function cashDocumentsConfirm(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $items = $this->cashItems($request, ['documents', 'документы'], ['ref', 'ссылка', 'guid']);
        if (!count($items)) {
            return $this->cashConfirmError($request, ['documents' => [['number' => 'cmps-k1', 'ref' => '<GUID 1С>']]]);
        }

        return response()->json(['ok' => true] + OneCCashExportService::confirmDocuments($items), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function supplierOrders(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $documents = OneCSupplierOrderService::pending(OneCExportService::config());

        return response()->json(['ok' => true, 'count' => count($documents), 'documents' => $documents], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function productionDocuments(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $documents = OneCProductionService::pending(OneCExportService::config());

        return response()->json(['ok' => true, 'count' => count($documents), 'documents' => $documents], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function productionDocumentsConfirm(Request $request)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $items = $this->confirmItems($request);
        if (!count($items) && $this->isEmptyList($request)) {
            return response()->json(['ok' => true, 'confirmed' => [], 'unknown' => []], 200, [], JSON_UNESCAPED_UNICODE);
        }
        if (!count($items)) {
            return $this->cashConfirmError($request, ['documents' => [['number' => 'cmps-po1', 'type' => 'production_order', 'status' => 'created|posted|deleted']]]);
        }

        return response()->json(['ok' => true] + OneCProductionService::confirm($items), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function supplierOrdersConfirm(Request $request)
    {
        return $this->supplierOrdersUpdate($request, false);
    }

    public function supplierOrdersState(Request $request)
    {
        return $this->supplierOrdersUpdate($request, true);
    }

    private function supplierOrdersUpdate(Request $request, bool $stateOnly)
    {
        if ($error = $this->guard($request)) {
            return $error;
        }
        $items = $this->supplierOrderItems($request, $stateOnly);
        if (!count($items) && $this->isEmptyList($request)) {
            return response()->json(['ok' => true, 'confirmed' => [], 'unknown' => []], 200, [], JSON_UNESCAPED_UNICODE);
        }
        if (!count($items)) {
            return $this->cashConfirmError($request, ['documents' => [['number' => 'cmps-z1', 'ref' => '<GUID 1С>', 'state' => 'Оплатили', 'status' => 'created|posted|deleted']]]);
        }

        return response()->json(['ok' => true] + OneCSupplierOrderService::confirm($items), 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function supplierOrderItems(Request $request, bool $stateOnly): array
    {
        $payload = $request->except('token');
        $raw = trim((string) $request->getContent());
        if ($raw !== '') {
            $decoded = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);
            if (is_array($decoded)) {
                $payload = array_is_list($decoded) ? ['documents' => $decoded] : $decoded + $payload;
            } elseif (is_scalar($decoded) && trim((string) $decoded) !== '') {
                $payload = ['numbers' => (string) $decoded] + $payload;
            } elseif (!count($payload) && !preg_match('/^[\[{]/', $raw)) {
                $payload = ['numbers' => $raw];
            }
        }
        $list = null;
        foreach ($payload as $key => $value) {
            $key = mb_strtolower((string) $key);
            if (in_array($key, ['documents', 'документы', 'docs', 'orders', 'заказы', 'items', 'data'], true)) {
                $list = is_array($value) ? $value : [$value];
                break;
            }
            if (in_array($key, ['numbers', 'номера'], true)) {
                $list = is_string($value) ? preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) : (array) $value;
                break;
            }
        }
        $items = [];
        foreach ($list ?? [$payload] as $entry) {
            if (is_scalar($entry) && trim((string) $entry) !== '') {
                $entry = ['number' => trim((string) $entry)];
            }
            if (!is_array($entry)) {
                continue;
            }
            $fields = [];
            foreach ($entry as $key => $value) {
                $fields[mb_strtolower((string) $key)] = $value;
            }
            $number = $fields['number'] ?? $fields['номер'] ?? null;
            $id = $fields['id'] ?? null;
            if (!is_scalar($number) && !is_scalar($id)) {
                continue;
            }
            $ref = $fields['ref'] ?? $fields['ссылка'] ?? $fields['guid'] ?? null;
            $state = null;
            foreach (['state', 'состояние'] as $key) {
                if (array_key_exists($key, $fields) && is_scalar($fields[$key])) {
                    $state = $fields[$key];
                    break;
                }
            }
            $status = null;
            foreach (['onec_status', 'status_1c', 'статус 1с', 'статус_1с', 'status', 'статус'] as $key) {
                if (!array_key_exists($key, $fields) || !is_scalar($fields[$key])) {
                    continue;
                }
                $raw = $fields[$key];
                $status = is_numeric($raw) && in_array($key, ['status', 'статус'], true) ? null : OneCExportService::normalizeStatus($raw);
                if ($status === null && $state === null && in_array($key, ['status', 'статус'], true)) {
                    $state = $raw;
                }
                break;
            }
            $items[] = [
                'number' => is_scalar($number) ? (string) $number : null,
                'id' => is_scalar($id) ? $id : null,
                'ref' => is_scalar($ref) ? (string) $ref : null,
                'state' => $state,
                'status' => $status ?? $this->flagStatus($fields),
                'state_only' => $stateOnly,
            ];
        }

        return $items;
    }

    private function isEmptyList(Request $request): bool
    {
        $raw = trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $request->getContent()));
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        $payload = is_array($decoded) ? $decoded : $request->except('token');
        if ($payload === []) {
            return $raw === '[]' || $raw === '{}';
        }
        foreach ($payload as $key => $value) {
            if (in_array(mb_strtolower((string) $key), ['documents', 'документы', 'docs', 'orders', 'заказы', 'items', 'data', 'numbers', 'номера'], true)) {
                if ($value === [] || $value === '' || $value === null) {
                    continue;
                }

                return false;
            }
        }
        foreach ($payload as $key => $value) {
            if (in_array(mb_strtolower((string) $key), ['documents', 'документы', 'docs', 'orders', 'заказы', 'items', 'data', 'numbers', 'номера'], true)) {
                return true;
            }
        }

        return false;
    }

    private function guard(Request $request)
    {
        $config = OneCExportService::config();
        if (!$config) {
            return response()->json(['ok' => false, 'error' => 'Обмен с 1С не настроен'], 404);
        }
        if (!OneCExportService::authorized($config, $this->token($request))) {
            return response()->json(['ok' => false, 'error' => 'Неверный токен'], 403);
        }

        return null;
    }

    private function cashItems(Request $request, array $listKeys, array $refKeys): array
    {
        $payload = $request->except('token');
        $raw = trim((string) $request->getContent());
        if ($raw !== '') {
            $decoded = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);
            if (is_array($decoded)) {
                $payload = array_is_list($decoded) ? [$listKeys[0] => $decoded] : $decoded + $payload;
            }
        }
        $list = null;
        foreach ($payload as $key => $value) {
            if (in_array(mb_strtolower((string) $key), array_merge($listKeys, ['items', 'data']), true) && is_array($value)) {
                $list = $value;
                break;
            }
        }
        if ($list === null) {
            $list = [$payload];
        }
        $items = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $fields = [];
            foreach ($entry as $key => $value) {
                $fields[mb_strtolower((string) $key)] = $value;
            }
            $ref = null;
            foreach ($refKeys as $key) {
                if (isset($fields[$key]) && is_scalar($fields[$key])) {
                    $ref = (string) $fields[$key];
                    break;
                }
            }
            $item = [
                'id' => $fields['id'] ?? null,
                'number' => $fields['number'] ?? $fields['номер'] ?? null,
                'code' => $ref,
                'ref' => $ref,
            ];
            if (($item['id'] ?? null) || ($item['number'] ?? null) || $ref !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function cashConfirmError(Request $request, array $expected)
    {
        \Log::warning('1C cash confirm: тело не распознано', [
            'path' => $request->path(),
            'body' => mb_substr((string) $request->getContent(), 0, 2000),
        ]);

        return response()->json([
            'ok' => false,
            'error' => 'Не переданы элементы для подтверждения',
            'expected' => $expected,
            'received' => mb_substr((string) $request->getContent(), 0, 500),
        ], 422, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

            return $number === '' ? [] : [['number' => $number, 'ref' => null, 'status' => null, 'type' => null]];
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
        $type = $fields['type'] ?? $fields['тип'] ?? null;
        $status = null;
        foreach (['status', 'статус', 'state', 'состояние'] as $key) {
            if (array_key_exists($key, $fields) && is_scalar($fields[$key])) {
                $status = OneCExportService::normalizeStatus($fields[$key]);
                break;
            }
        }
        if ($status === null) {
            $status = $this->flagStatus($fields);
        }

        return [[
            'number' => trim((string) $number),
            'ref' => is_scalar($ref) ? (string) $ref : null,
            'status' => $status,
            'type' => is_scalar($type) ? (string) $type : null,
        ]];
    }

    private function flagStatus(array $fields): ?int
    {
        foreach (['deleted' => OneCExportService::STATUS_DELETED, 'удалена' => OneCExportService::STATUS_DELETED, 'удален' => OneCExportService::STATUS_DELETED, 'posted' => OneCExportService::STATUS_POSTED, 'проведена' => OneCExportService::STATUS_POSTED, 'проведен' => OneCExportService::STATUS_POSTED] as $key => $index) {
            if (!array_key_exists($key, $fields)) {
                continue;
            }
            $flag = filter_var($fields[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($flag === true) {
                return $index;
            }
            if ($flag === false && $index === OneCExportService::STATUS_POSTED) {
                return OneCExportService::STATUS_CREATED;
            }
        }

        return null;
    }

    private function token(Request $request): ?string
    {
        $token = $request->query('token') ?? $request->header('X-Compas-Token') ?? $request->bearerToken();

        return is_string($token) ? trim($token) : null;
    }
}
