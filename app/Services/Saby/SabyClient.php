<?php

namespace App\Services\Saby;

use App\Models\SabyConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SabyClient
{
    public const AUTH_URL = 'https://online.sbis.ru/auth/service/';
    public const AUTH_URL_TEST = 'https://fix-online.sbis.ru/auth/service/';
    public const TMS_URL = 'https://tms.saby.ru/service/';

    private SabyConfig $config;

    public function __construct(SabyConfig $config)
    {
        $this->config = $config;
    }

    public static function make(): ?self
    {
        if (!self::ready()) {
            return null;
        }

        return new self(SabyConfig::first());
    }

    public static function ready(): bool
    {
        try {
            if (!Schema::hasTable('saby_config') || !Schema::hasTable('saby_waybills')) {
                return false;
            }
            $config = SabyConfig::first();

            return $config && $config->login && $config->password;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function config(): SabyConfig
    {
        return $this->config;
    }

    public function call(string $method, array $params, bool $retryOnAuth = true): array
    {
        $sid = $this->sessionId();

        $response = $this->rpc(self::TMS_URL, $method, $params, $sid);

        if ($retryOnAuth && $this->isAuthError($response)) {
            $sid = $this->authenticate(true);
            $response = $this->rpc(self::TMS_URL, $method, $params, $sid);
        }

        if (isset($response['error'])) {
            throw new SabyException($this->errorMessage($response['error']), $response['error']);
        }

        return $response['result'] ?? [];
    }

    public function sessionId(): string
    {
        $sid = (string) $this->config->param('session_id', '');
        $createdAt = (string) $this->config->param('session_created_at', '');

        if ($sid !== '' && $createdAt !== '' && strtotime($createdAt) > strtotime('-12 hours')) {
            return $sid;
        }

        return $this->authenticate();
    }

    public function authenticate(bool $force = false): string
    {
        $params = [
            'Логин' => (string) $this->config->login,
            'Пароль' => (string) $this->config->password,
        ];

        $account = trim((string) $this->config->param('account_number', ''));
        if ($account !== '') {
            $params['НомерАккаунта'] = $account;
        }

        $url = $this->config->param('test_mode') ? self::AUTH_URL_TEST : self::AUTH_URL;
        $response = $this->rpc($url, 'СБИС.Аутентифицировать', ['Параметр' => $params]);

        if (isset($response['error'])) {
            throw new SabyException($this->errorMessage($response['error']), $response['error']);
        }

        $sid = $response['result'] ?? null;
        if (!is_string($sid) || $sid === '') {
            throw new SabyException('Saby не вернул идентификатор сессии');
        }

        $this->config->mergeParams([
            'session_id' => $sid,
            'session_created_at' => now()->toDateTimeString(),
        ]);

        return $sid;
    }

    private function rpc(string $url, string $method, array $params, ?string $sid = null): array
    {
        $headers = [];
        if ($sid) {
            $headers['X-SBISSessionID'] = $sid;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(60)
                ->withBody(json_encode([
                    'jsonrpc' => '2.0',
                    'method' => $method,
                    'params' => $params,
                    'id' => 0,
                ], JSON_UNESCAPED_UNICODE), 'application/json; charset=utf-8')
                ->post($url);
        } catch (\Throwable $e) {
            $this->log('error', 'rpc transport error', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw new SabyException('Не удалось связаться с Saby: ' . $e->getMessage());
        }

        $body = json_decode($response->body(), true);

        if (!is_array($body)) {
            $this->log('error', 'rpc bad response', [
                'method' => $method,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new SabyException('Saby вернул некорректный ответ (HTTP ' . $response->status() . ')');
        }

        if (isset($body['error'])) {
            $this->log('warning', 'rpc error', [
                'method' => $method,
                'status' => $response->status(),
                'error' => $body['error'],
            ]);
        }

        return $body;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        try {
            Log::channel('saby')->{$level}($message, $context);
        } catch (\Throwable $e) {
        }
    }

    private function isAuthError(array $response): bool
    {
        if (!isset($response['error'])) {
            return false;
        }

        $code = (int) ($response['error']['code'] ?? 0);
        $message = mb_strtolower((string) ($response['error']['message'] ?? ''));

        return $code === 401 || str_contains($message, 'сесси') || str_contains($message, 'аутентиф');
    }

    private function errorMessage($error): string
    {
        if (is_array($error)) {
            $details = trim((string) ($error['details'] ?? ''));
            $message = trim((string) ($error['message'] ?? ''));

            return $details !== '' ? $details : ($message !== '' ? $message : 'Ошибка Saby');
        }

        return 'Ошибка Saby';
    }
}
