<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallPickupEmployeeField extends Command
{
    protected $signature = 'pickups:install-employee-field
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Добавить в «Записи на самовывоз» множественное поле «Сотрудник» (как у задач логистики) — для QR-привязки сотрудников';

    public function handle(): int
    {
        return $this->call('entity:install-employee-field', ['entity' => 'pickups', 'target' => (string) $this->argument('target')]);
    }
}
