<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (8478) На странице /roles список сущностей берётся из data_types по условию
 * enable=1 AND hidden=0 (RoleController::show). Сущности «Маршруты» (routes) и
 * «Пользователи» (users) туда не попадали — выводим их, выставляя оба флага.
 *
 * Парная миграция для шаблона новых порталов: ..._in_seeds.php.
 */
return new class extends Migration
{
    private array $slugs = ['routes', 'users'];

    public function up(): void
    {
        if (!Schema::hasTable('data_types')) {
            return;
        }

        DB::table('data_types')
            ->whereIn('slug', $this->slugs)
            ->update(['enable' => 1, 'hidden' => 0]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_types')) {
            return;
        }

        DB::table('data_types')
            ->whereIn('slug', $this->slugs)
            ->update(['hidden' => 1]);
    }
};
