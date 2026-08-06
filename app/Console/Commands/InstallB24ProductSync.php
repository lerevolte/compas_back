<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class InstallB24ProductSync extends Command
{
    protected $signature = 'bitrix24:install-product-sync
        {target=seeds : seeds | all-tenants | <tenant_id> (например avixo)}';

    protected $description = 'Установить двустороннюю синхронизацию товаров/категорий с Bitrix24 (колонки products.link/id_b24, categories.id_b24, поле «Ссылка на товар»)';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->info('Установка в базу-шаблон admin_seeds (connection: seeds)…');
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            $tenants = Tenant::get();
            $this->info('Установка во все порталы: ' . $tenants->count());
            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($tenant) {
                        $this->installInto(\DB::connection(), (string) $tenant->id);
                        \App\Models\Settings::clear_cache();
                    });
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            $this->info('Готово: all-tenants');
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $this->info("Установка в портал {$target}…");
        $tenant->run(function () use ($target) {
            $this->installInto(\DB::connection(), (string) $target);
            \App\Models\Settings::clear_cache();
        });
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function installInto(ConnectionInterface $db, string $label): void
    {
        $schema = $db->getSchemaBuilder();

        if (!$schema->hasTable('products') || !$schema->hasTable('categories')) {
            $this->warn("  {$label}: нет таблиц products/categories — пропуск");
            return;
        }

        if (!$schema->hasColumn('products', 'id_b24')) {
            $schema->table('products', function ($table) {
                $table->string('id_b24')->nullable()->index();
            });
        }
        if (!$schema->hasColumn('products', 'link')) {
            $schema->table('products', function ($table) {
                $table->text('link')->nullable();
            });
        }
        if (!$schema->hasColumn('categories', 'id_b24')) {
            $schema->table('categories', function ($table) {
                $table->string('id_b24')->nullable()->index();
            });
        }

        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId) {
            $this->warn("  {$label}: нет сущности products в data_types — поле name не настроено");
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'name')
            ->update(['is_external_link' => 1]);
        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'link')
            ->delete();
    }
}
