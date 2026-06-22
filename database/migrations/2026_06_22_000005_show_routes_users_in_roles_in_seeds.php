<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._show_routes_users_in_roles.php — то же в базе-шаблоне
 * (connection: seeds) для новых порталов (8478).
 */
return new class extends Migration
{
    private array $slugs = ['routes', 'users'];

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_types')) {
            return;
        }

        DB::connection('seeds')->table('data_types')
            ->whereIn('slug', $this->slugs)
            ->update(['enable' => 1, 'hidden' => 0]);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_types')) {
            return;
        }

        DB::connection('seeds')->table('data_types')
            ->whereIn('slug', $this->slugs)
            ->update(['hidden' => 1]);
    }
};
