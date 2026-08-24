<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestController extends Controller
{
    public function bank(Request $request): JsonResponse
    {
        $q = trim((string) $request->q);
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $token = config('services.dadata.token');
        $secret = config('services.dadata.secret');
        if (!$token || !$secret) {
            return response()->json([]);
        }

        $dadata = new Dadata($token, $secret);
        $result = [];
        try {
            $dadata->init();
            $result = $dadata->suggest('bank', ['query' => $q, 'count' => 7]);
        } catch (\Throwable $e) {
            \Log::warning('dadata bank suggest failed: ' . $e->getMessage());
        } finally {
            try {
                $dadata->close();
            } catch (\Throwable $e) {
            }
        }

        $data = [];
        foreach ($result['suggestions'] ?? [] as $item) {
            $d = $item['data'] ?? [];
            $address = $d['address']['unrestricted_value'] ?? ($d['address']['value'] ?? '');
            $data[] = [
                'label' => (string) ($item['value'] ?? ''),
                'hint' => trim(($d['bic'] ?? '') . ' ' . ($d['address']['value'] ?? '')),
                'value' => [
                    'bank_name' => (string) ($d['name']['payment'] ?? ($item['value'] ?? '')),
                    'bic' => (string) ($d['bic'] ?? ''),
                    'corr_account' => (string) ($d['correspondent_account'] ?? ''),
                    'swift' => (string) ($d['swift'] ?? ''),
                    'bank_address' => (string) $address,
                ],
            ];
        }

        return response()->json($data);
    }
}
