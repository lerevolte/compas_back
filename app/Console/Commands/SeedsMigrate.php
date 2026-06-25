<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Прогоняет ТЕНАНТСКИЕ миграции (database/migrations/tenant) на БД-шаблоне
 * admin_seeds (соединение 'seeds'). Нужно, чтобы схема seeds совпадала со
 * схемой реальных порталов: новые порталы берут схему из tenant-миграций, но
 * сама seeds-БД (в неё админ заходит и настраивает дефолты) не мигрировалась
 * вместе с тенантами и отставала по столбцам (choosed_at, default_value и т.п.).
 *
 * Запускать после деплоя вместе с `php artisan tenants:migrate`.
 *
 * Перед миграцией делается BASELINE: миграции вида create_*_table помечаются
 * как выполненные для уже существующих в seeds таблиц — чтобы migrate не пытался
 * пересоздать их (Schema::create упал бы). Затем прогоняются недостающие
 * миграции; добавляющие столбцы защищены Schema::hasColumn и идемпотентны.
 */
class SeedsMigrate extends Command
{
    protected $signature = 'seeds:migrate {--pretend : показать SQL без выполнения}';

    protected $description = 'Прогнать тенантские миграции на БД-шаблоне seeds (синхронизация схемы)';

    public function handle(): int
    {
        $original = DB::getDefaultConnection();

        // Schema-фасад в tenant-миграциях использует default-соединение —
        // временно делаем им 'seeds', чтобы миграции применялись к seeds-БД.
        DB::setDefaultConnection('seeds');

        try {
            if (!$this->option('pretend')) {
                $this->ensureMigrationsTable();
                $this->baselineExistingTables();
            }

            $this->info('Прогон тенантских миграций на соединении seeds…');
            $code = $this->call('migrate', array_filter([
                '--database' => 'seeds',
                '--path'     => 'database/migrations/tenant',
                '--force'    => true,
                '--pretend'  => $this->option('pretend') ?: null,
            ]));
        } finally {
            DB::setDefaultConnection($original);
        }

        $this->info('Готово: seeds');
        return $code ?? self::SUCCESS;
    }

    private function ensureMigrationsTable(): void
    {
        if (!Schema::connection('seeds')->hasTable('migrations')) {
            $this->call('migrate:install', ['--database' => 'seeds']);
        }
    }

    /**
     * Помечаем create_*_table миграции как выполненные для уже существующих
     * таблиц, чтобы migrate не пытался их пересоздать.
     */
    private function baselineExistingTables(): void
    {
        $batch = (int) DB::connection('seeds')->table('migrations')->max('batch');
        $batch = $batch ?: 1;

        $files = glob(database_path('migrations/tenant/*.php')) ?: [];
        $marked = 0;
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!preg_match('/create_([a-z0-9_]+)_table$/', $name, $m)) {
                continue;
            }
            $table = $m[1];
            $already = DB::connection('seeds')->table('migrations')->where('migration', $name)->exists();
            if (!$already && Schema::connection('seeds')->hasTable($table)) {
                DB::connection('seeds')->table('migrations')->insert([
                    'migration' => $name,
                    'batch'     => $batch,
                ]);
                $marked++;
            }
        }
        if ($marked) {
            $this->line("  baseline: помечено выполненными create-миграций: {$marked}");
        }
    }
}
