<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SabyConfig;
use App\Services\Saby\SabyClient;
use App\Services\Saby\SabyException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SabyConfigController extends Controller
{
    private const PARAMS = [
        'account_number' => 'Номер аккаунта Saby',
        'our_inn' => 'ИНН нашей организации',
        'our_kpp' => 'КПП нашей организации',
        'number_prefix' => 'Префикс номера накладной',
    ];

    public function show()
    {
        if (!$this->installed()) {
            return response()->json(['code' => 404, 'error' => 'Модуль не установлен'], 404);
        }

        return response()->json($this->fields(SabyConfig::first() ?: new SabyConfig()));
    }

    public function update(Request $request)
    {
        if (!$this->installed()) {
            return response()->json(['code' => 404, 'error' => 'Модуль не установлен'], 404);
        }

        $config = SabyConfig::first() ?: new SabyConfig();
        $patch = [];

        if ($request->has('login')) {
            $config->login = trim((string) $request->input('login'));
        }
        $password = (string) $request->input('password', '');
        if ($password !== '') {
            $config->password = $password;
        }
        foreach (array_keys(self::PARAMS) as $key) {
            if ($request->has($key)) {
                $patch[$key] = trim((string) $request->input($key));
            }
        }
        $config->save();

        $patch['session_id'] = '';
        $patch['session_created_at'] = '';
        $config->mergeParams($patch);

        $check = $this->check($config->fresh());

        return response()->json(['ok' => $check['ok'], 'error' => $check['error'], 'fields' => $this->fields($config->fresh())]);
    }

    private function check(SabyConfig $config): array
    {
        $result = ['ok' => false, 'error' => ''];
        if (!$config->login || !$config->password) {
            $result['error'] = 'Не заданы логин и пароль';
        } else {
            try {
                (new SabyClient($config))->authenticate(true);
                $result['ok'] = true;
            } catch (SabyException $e) {
                $result['error'] = $e->getMessage();
            } catch (\Throwable $e) {
                $result['error'] = 'Не удалось подключиться к Saby: ' . $e->getMessage();
            }
        }
        $config->mergeParams([
            'last_check_at' => now()->format('d.m.Y H:i'),
            'last_check_ok' => $result['ok'] ? 1 : 0,
            'last_check_error' => $result['error'],
        ]);

        return $result;
    }

    private function fields(SabyConfig $config): array
    {
        $params = $config->getParams();
        $fields = [
            $this->field(0, 'login', 'Логин Saby', 'text', (string) ($config->login ?? '')),
            $this->field(1, 'password', $config->password ? 'Пароль Saby (задан, введите для замены)' : 'Пароль Saby', 'password', ''),
        ];
        $i = 2;
        foreach (self::PARAMS as $key => $title) {
            $fields[] = $this->field($i++, $key, $title, 'text', (string) ($params[$key] ?? ''));
        }
        $status = '';
        if (($params['last_check_at'] ?? '') !== '') {
            $status = !empty($params['last_check_ok'])
                ? 'Подключение проверено ' . $params['last_check_at'] . ' — успешно'
                : 'Проверка ' . $params['last_check_at'] . ' — ошибка: ' . ($params['last_check_error'] ?? '');
        }
        $fields[] = $this->field($i, 'status', 'Статус подключения', 'text', $status, false);

        return $fields;
    }

    private function field(int $id, string $key, string $title, string $type, string $value, bool $editable = true): array
    {
        return [
            'id' => $id,
            'key' => $key,
            'title' => $title,
            'type' => $type,
            'value' => $value,
            'is_plural' => 0,
            'is_external_link' => 0,
            'required' => 0,
            'visible_always' => 1,
            'can_read' => 1,
            'can_edit' => $editable ? 1 : 0,
            'read_only' => $editable ? 0 : 1,
            'only_read' => $editable ? 0 : 1,
        ];
    }

    private function installed(): bool
    {
        try {
            return Schema::hasTable('saby_config');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
