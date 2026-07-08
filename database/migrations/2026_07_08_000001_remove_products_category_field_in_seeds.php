<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['category_id'];

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows') || !Schema::connection('seeds')->hasTable('data_types')) {
            return;
        }

        $typeIds = DB::connection('seeds')->table('data_types')->where('slug', 'products')->pluck('id');
        if ($typeIds->isEmpty()) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->whereIn('data_type_id', $typeIds)
            ->whereIn('field', $this->fields)
            ->delete();
    }

    public function down(): void
    {
    }
};
