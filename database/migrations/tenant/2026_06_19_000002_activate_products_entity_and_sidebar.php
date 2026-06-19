<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Активирует сущность «Товары» (products) во всех порталах и выводит её пункт
 * в сайдбаре:
 *   - data_types.enable = 1 (была 0 по умолчанию → раздел был выключен);
 *   - sidebar_items: пункт «Товары» (slug products) включён (enabled = 1),
 *     при отсутствии — создаётся.
 *
 * Шаблон новых порталов (admin_seeds) правит парная миграция
 * database/migrations/..._activate_products_entity_and_sidebar_in_seeds.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->activate(DB::connection());
    }

    public function down(): void
    {
        $this->deactivate(DB::connection());
    }

    private function activate(ConnectionInterface $db): void
    {
        if ($db->getSchemaBuilder()->hasTable('data_types')) {
            $db->table('data_types')
                ->where('slug', 'products')
                ->orWhere('name', 'products')
                ->update(['enable' => 1, 'hidden' => 0]);
        }

        if (!$db->getSchemaBuilder()->hasTable('sidebar_items')) {
            return;
        }

        $now = now();
        $existing = $db->table('sidebar_items')->where('slug', 'products')->first();

        if ($existing) {
            $db->table('sidebar_items')
                ->where('slug', 'products')
                ->update(['enabled' => 1, 'is_hidden' => 0, 'updated_at' => $now]);
            return;
        }

        // Название берём из метаданных сущности, фолбэк — «Товары».
        $type = $db->table('data_types')
            ->where('slug', 'products')->orWhere('name', 'products')
            ->first();
        $name = $type->title_plural ?? 'Товары';

        $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
        $db->table('sidebar_items')->insert([
            'created_at' => $now, 'updated_at' => $now,
            'name' => $name, 'slug' => 'products',
            'sort' => 0, 'link' => '/objects/products',
            '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
            'is_hidden' => 0, 'enabled' => 1,
        ]);
    }

    private function deactivate(ConnectionInterface $db): void
    {
        if ($db->getSchemaBuilder()->hasTable('data_types')) {
            $db->table('data_types')
                ->where('slug', 'products')
                ->orWhere('name', 'products')
                ->update(['enable' => 0]);
        }

        if ($db->getSchemaBuilder()->hasTable('sidebar_items')) {
            $db->table('sidebar_items')
                ->where('slug', 'products')
                ->update(['enabled' => 0]);
        }
    }
};
