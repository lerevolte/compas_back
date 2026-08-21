<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallGeoposition extends Command
{
    protected $signature = 'geo:install
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--remove : убрать поле из data_rows (таблица и данные сохраняются)}';

    protected $description = 'Установить приём геопозиции: таблица user_geopositions, колонка users.geoposition и поле «Геопозиция» в карточке пользователя';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->install(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->install(\DB::connection(), (string) $tenant->id, true));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

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
        $tenant->run(fn () => $this->install(\DB::connection(), (string) $target, true));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function install($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();

        $dataType = $db->table('data_types')->where('slug', 'users')->first();
        if (!$dataType || !$sb->hasTable('users')) {
            $this->warn("    [{$label}] сущность users не найдена, пропуск");
            return;
        }

        if ($this->option('remove')) {
            $deleted = $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->where('field', 'geoposition')
                ->delete();
            if ($deleted) {
                $this->line("    [{$label}] поле geoposition убрано из data_rows");
                $this->clearCache($db, $inTenant);
            }
            return;
        }

        $db->statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `user_geopositions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `accuracy` double DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `heading` double DEFAULT NULL,
  `provider` varchar(32) DEFAULT NULL,
  `is_mock` tinyint(1) DEFAULT NULL,
  `satellites` int(11) DEFAULT NULL,
  `gps_time` bigint(20) DEFAULT NULL,
  `client_time` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_geopositions_user_id_id_index` (`user_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        if (!$sb->hasColumn('users', 'geoposition')) {
            $db->statement("ALTER TABLE `users` ADD COLUMN `geoposition` TEXT NULL");
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', 'geoposition')
            ->first();

        if ($existing) {
            $db->table('data_rows')->where('id', $existing->id)->update([
                'type' => 'geoposition',
                'title' => 'Геопозиция',
                'only_read' => 1,
                'hide' => 0,
            ]);
            $this->line("    [{$label}] поле geoposition обновлено (id {$existing->id})");
        } else {
            $sectionId = $db->table('field_sections')
                ->where('page', 'users')
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')
                ->value('id');

            $maxSort = (int) $db->table('data_rows')
                ->where('data_type_id', $dataType->id)
                ->max('sort');

            $id = $db->table('data_rows')->insertGetId([
                'data_type_id' => $dataType->id,
                'field' => 'geoposition',
                'type' => 'geoposition',
                'title' => 'Геопозиция',
                'required' => 0,
                'visible_always' => 1,
                'section_id' => $sectionId,
                'hide' => $sectionId ? 0 : 1,
                'sort' => $maxSort + 1,
                'only_read' => 1,
                'is_permanent' => 1,
            ]);
            $this->line("    [{$label}] создано поле geoposition (id {$id})");
        }

        $this->clearCache($db, $inTenant);
    }

    private function clearCache($db, bool $inTenant): void
    {
        try {
            if ($db->getSchemaBuilder()->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/users')->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
