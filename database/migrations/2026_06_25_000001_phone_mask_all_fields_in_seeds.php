<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows') || !Schema::connection('seeds')->hasColumn('data_rows', 'mask')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->whereIn('field', ['phone', 'work_phone'])
            ->update(['mask' => '+#(###)###-##-##']);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows') || !Schema::connection('seeds')->hasColumn('data_rows', 'mask')) {
            return;
        }

        DB::connection('seeds')->table('data_rows')
            ->whereIn('field', ['phone', 'work_phone'])
            ->update(['mask' => null]);
    }
};
