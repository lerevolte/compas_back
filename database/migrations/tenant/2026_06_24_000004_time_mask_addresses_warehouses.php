<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeIds = DB::table('data_types')
            ->whereIn('slug', ['addresses', 'warehouses'])
            ->pluck('id');

        if ($typeIds->isEmpty()) {
            return;
        }

        DB::table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->where('field', 'time')
            ->update(['mask' => '##:## - ##:##']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeIds = DB::table('data_types')
            ->whereIn('slug', ['addresses', 'warehouses'])
            ->pluck('id');

        DB::table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->where('field', 'time')
            ->update(['mask' => null]);
    }
};
