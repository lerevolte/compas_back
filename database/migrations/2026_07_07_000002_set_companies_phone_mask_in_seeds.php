<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная миграция для шаблона новых порталов (connection seeds): маска телефона
 * полю «Телефон» сущности «Компания» (companies). См. tenant-версию
 * 2026_07_07_000002_set_companies_phone_mask.php (8662).
 */
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
            ->where('slug', 'companies')
            ->orWhere('name', 'companies')
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
