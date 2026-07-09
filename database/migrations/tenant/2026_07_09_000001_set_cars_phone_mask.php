<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $mask = '+#(###)###-##-##';

    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $typeId = DB::table('data_types')
            ->where('slug', 'cars')
            ->orWhere('name', 'cars')
            ->value('id');
        if (!$typeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $typeId)
            ->where(function ($q) {
                $q->whereIn('field', ['phone', 'work_phone'])
                  ->orWhere('title', 'Телефон')
                  ->orWhere('title', 'like', '%елефон%');
            })
            ->update(['mask' => $this->mask]);

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
    }
};
