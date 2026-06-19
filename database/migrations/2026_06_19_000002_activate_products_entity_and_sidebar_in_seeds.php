<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Парная к tenant/..._activate_products_entity_and_sidebar.php: то же самое в
 * базе-шаблоне admin_seeds (connection: seeds), чтобы у НОВЫХ регистрируемых
 * порталов сущность «Товары» сразу была активна и присутствовала в сайдбаре.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->activate(DB::connection('seeds'));
    }

    public function down(): void
    {
        $this->deactivate(DB::connection('seeds'));
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
