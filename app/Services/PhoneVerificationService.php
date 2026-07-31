<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhoneVerificationService
{
    private const API_SEND = 'https://api3.sms-agent.ru/v2.0/json/send/';
    private const API_STATE = 'https://api3.sms-agent.ru/v2.0/json/state/';
    private const TOKEN_TTL_HOURS = 24;
    private const MAX_PER_PHONE_PER_HOUR = 4;
    private const MAX_PER_IP_PER_HOUR = 10;

    public function enabled(): bool
    {
        return (bool) config('services.sms_agent.login') && (bool) config('services.sms_agent.password');
    }

    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }
        return strlen($digits) === 11 ? $digits : null;
    }

    public function initiate(?string $rawPhone, ?string $ip): array
    {
        if (!$this->enabled()) {
            return ['status' => 'disabled'];
        }
        $phone = self::normalizePhone($rawPhone);
        if (!$phone) {
            return ['status' => 'error', 'message' => 'Некорректный номер телефона'];
        }

        $hourAgo = now()->subHour();
        $byPhone = DB::table('phone_verifications')->where('phone', $phone)->where('created_at', '>=', $hourAgo)->count();
        if ($byPhone >= self::MAX_PER_PHONE_PER_HOUR) {
            return ['status' => 'error', 'message' => 'Слишком много попыток. Попробуйте позже'];
        }
        if ($ip) {
            $byIp = DB::table('phone_verifications')->where('ip', $ip)->where('created_at', '>=', $hourAgo)->count();
            if ($byIp >= self::MAX_PER_IP_PER_HOUR) {
                return ['status' => 'error', 'message' => 'Слишком много попыток. Попробуйте позже'];
            }
        }

        $response = $this->call(self::API_SEND, [
            'type' => 'callme',
            'payload' => [['phone' => $phone]],
        ]);

        if (!isset($response[0]['id_sms']) || !isset($response[0]['call_to'])) {
            Log::warning('phone-verification: initiate failed', ['phone' => $phone, 'response' => $response]);
            return ['status' => 'error', 'message' => 'Не удалось инициировать звонок. Попробуйте позже'];
        }

        $id = DB::table('phone_verifications')->insertGetId([
            'phone' => $phone,
            'id_sms' => (string) $response[0]['id_sms'],
            'call_to' => (string) $response[0]['call_to'],
            'status' => 'pending',
            'ip' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['status' => 'success', 'id' => $id, 'call_to' => (string) $response[0]['call_to']];
    }

    public function status(int $id): array
    {
        $row = DB::table('phone_verifications')->where('id', $id)->first();
        if (!$row) {
            return ['status' => 'error', 'message' => 'Запрос не найден'];
        }
        if ($row->status === 'confirmed') {
            return ['status' => 'delivered', 'verification_token' => $row->token];
        }
        if ($row->status !== 'pending') {
            return ['status' => 'error', 'message' => 'Запрос устарел, начните заново'];
        }

        $response = $this->call(self::API_STATE, [
            'payload' => [['id_sms' => $row->id_sms]],
        ]);
        $state = $response[0]['state'] ?? null;
        if ($state === null) {
            return ['status' => 'pending'];
        }
        if ($state !== 'deliver') {
            return ['status' => 'pending', 'api_state' => (string) $state];
        }

        $token = Str::random(48);
        DB::table('phone_verifications')->where('id', $id)->update([
            'status' => 'confirmed',
            'token' => $token,
            'confirmed_at' => now(),
            'updated_at' => now(),
        ]);

        return ['status' => 'delivered', 'verification_token' => $token];
    }

    public function check(?string $rawPhone, ?string $token): bool
    {
        $phone = self::normalizePhone($rawPhone);
        if (!$phone || !$token) {
            return false;
        }
        return DB::table('phone_verifications')
            ->where('token', $token)
            ->where('phone', $phone)
            ->where('status', 'confirmed')
            ->where('confirmed_at', '>=', now()->subHours(self::TOKEN_TTL_HOURS))
            ->exists();
    }

    public function markUsed(?string $token): void
    {
        if (!$token) {
            return;
        }
        DB::table('phone_verifications')->where('token', $token)->update([
            'status' => 'used',
            'updated_at' => now(),
        ]);
    }

    private function call(string $url, array $data): ?array
    {
        try {
            $auth = base64_encode(config('services.sms_agent.login') . ':' . config('services.sms_agent.password'));
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Basic ' . $auth])
                ->post($url, $data);
            if (!$response->ok()) {
                Log::warning('phone-verification: api http ' . $response->status(), ['url' => $url]);
                return null;
            }
            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('phone-verification: api error ' . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }
}
