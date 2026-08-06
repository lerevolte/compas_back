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
            $this->warn("  {$label}: нет сущности products в data_types — поле «Ссылка на товар» не добавлено");
            return;
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'link')
            ->first(['id', 'is_external_link']);
        if ($existing && !$existing->is_external_link) {
            $db->table('data_rows')->where('id', $existing->id)->update(['is_external_link' => 1]);
        }
        if (!$existing) {
            $sample = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('field', 'weight')
                ->first();
            $maxSort = (int) $db->table('data_rows')->where('data_type_id', $typeId)->max('sort');
            $db->table('data_rows')->insert([
                'data_type_id'   => $typeId,
                'field'          => 'link',
                'type'           => 'text',
                'title'          => 'Ссылка на товар',
                'required'       => 0,
                'details'        => '',
                'visible_always' => 0,
                'label_color'    => '#000',
                'section_id'     => $sample->section_id ?? null,
                'sort'           => $maxSort + 1,
                'button_name'    => 'Загрузить',
                'hide'           => 0,
                'is_plural'      => 0,
                'roles_read'     => '',
                'roles_write'    => '',
                'mobile_pages'   => '',
                'only_read'      => 0,
                'is_permanent'   => 0,
                'external_link'  => '',
                'is_external_link' => 1,
                'unit'           => '',
            ]);
        }
    }
}
