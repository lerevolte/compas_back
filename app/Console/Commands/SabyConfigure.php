<?php

namespace App\Console\Commands;

use App\Models\SabyConfig;
use App\Models\Tenant;
use App\Services\Saby\SabyClient;
use App\Services\Saby\SabyException;
use Illuminate\Console\Command;

class SabyConfigure extends Command
{
    protected $signature = 'saby:configure
        {tenant : <tenant_id>}
        {--login= : логин личного кабинета Saby}
        {--password= : пароль личного кабинета Saby}
        {--account= : номер аккаунта Saby, если кабинетов несколько}
        {--inn= : ИНН нашей организации}
        {--kpp= : КПП нашей организации}
        {--prefix= : префикс номера накладной}
        {--packing= : способ упаковки по умолчанию}
        {--tare= : код вида тары по умолчанию}
        {--condition= : состояние груза по умолчанию}
        {--test : проверить авторизацию в Saby после сохранения}
        {--show : показать текущие настройки}';

    protected $description = 'Настроить модуль Saby в портале: креды и значения по умолчанию';

    public function handle(): int
    {
        $target = $this->argument('tenant');

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $code = self::SUCCESS;
        $tenant->run(function () use (&$code) {
            $code = $this->apply();
        });

        return $code;
    }

    private function apply(): int
    {
        if (!\Schema::hasTable('saby_config')) {
            $this->error('Модуль не установлен: сначала запустите saby:install');
            return self::FAILURE;
        }

        $config = SabyConfig::first() ?: new SabyConfig();

        if ($this->option('show')) {
            $params = $config->getParams();
            unset($params['session_id']);
            $this->line('login: ' . ($config->login ?: '—'));
            $this->line('password: ' . ($config->password ? 'задан' : '—'));
            foreach ($params as $key => $value) {
                $this->line($key . ': ' . (is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)));
            }
            return self::SUCCESS;
        }

        if ($this->option('login')) {
            $config->login = $this->option('login');
        }
        if ($this->option('password')) {
            $config->password = $this->option('password');
        }
        $config->save();

        $patch = [];
        foreach ([
            'account' => 'account_number',
            'inn' => 'our_inn',
            'kpp' => 'our_kpp',
            'prefix' => 'number_prefix',
            'packing' => 'packing_method',
            'tare' => 'tare_type',
            'condition' => 'cargo_condition',
        ] as $option => $key) {
            if ($this->option($option) !== null) {
                $patch[$key] = $this->option($option);
            }
        }

        if (count($patch)) {
            $patch['session_id'] = '';
            $patch['session_created_at'] = '';
            $config->mergeParams($patch);
        }

        $this->info('Настройки сохранены');

        if ($this->option('test')) {
            try {
                $client = new SabyClient($config->fresh());
                $sid = $client->authenticate(true);
                $this->info('Авторизация в Saby успешна, сессия ' . substr($sid, 0, 8) . '…');
            } catch (SabyException $e) {
                $this->error('Ошибка авторизации: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
