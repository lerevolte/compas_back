<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['category_id'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeIds = DB::table('data_types')->where('slug', 'products')->pluck('id');
        if ($typeIds->isEmpty()) {
            return;
        }

        DB::table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->whereIn('field', $this->fields)
            ->delete();

        \App\Models\Settings::clear_cache();
    }

    public function down(): void
    {
    }
};
