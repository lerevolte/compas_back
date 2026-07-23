<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

class BackfillAccountEmails extends Command
{
    protected $signature = 'accounts:backfill-email
        {--force : Перезаписать и уже заполненные email}';

    protected $description = 'Заполнить accounts.email в central-БД почтой первого администратора портала';

    public function handle(): int
    {
        if (!Schema::hasColumn('accounts', 'email')) {
            $this->error('В accounts нет колонки email — сначала php artisan migrate');
            return self::FAILURE;
        }

        $accounts = DB::table('accounts')->whereNotNull('tenant_id')->get();
        $updated = 0;

        foreach ($accounts as $account) {
            if ($account->email && !$this->option('force')) {
                continue;
            }

            $tenant = Tenant::find($account->tenant_id);
            if (!$tenant) {
                $this->warn("  ✗ {$account->tenant_id}: тенант не найден");
                continue;
            }

            try {
                $email = $tenant->run(function () {
                    return DB::table('users')
                        ->where('is_admin', 1)
                        ->orderBy('id')
                        ->value('email');
                });
            } catch (\Throwable $e) {
                $this->error("  ✗ {$account->tenant_id}: ".$e->getMessage());
                continue;
            }

            if (!$email) {
                $this->warn("  – {$account->tenant_id}: у администратора нет email");
                continue;
            }

            DB::table('accounts')->where('id', $account->id)->update(['email' => $email]);
            $this->info("  ✓ {$account->tenant_id}: {$email}");
            $updated++;
        }

        $this->info("Готово, обновлено: {$updated}");
        return self::SUCCESS;
    }
}
