<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $mask = '+#(###)###-##-##';

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')
            || !Schema::connection('seeds')->hasTable('data_types')) {
            return;
        }

        $typeId = DB::connection('seeds')->table('data_types')
            ->where('slug', 'cars')
            ->orWhere('name', 'cars')
            ->value('id');
        if (!$typeId) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where(function ($q) {
                $q->whereIn('field', ['phone', 'work_phone'])
                  ->orWhere('title', 'Телефон')
                  ->orWhere('title', 'like', '%елефон%');
            })
            ->update(['mask' => $this->mask]);
    }

    public function down(): void
    {
    }
};
