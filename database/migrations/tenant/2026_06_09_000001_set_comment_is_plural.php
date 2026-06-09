<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('data_rows')
            ->where('field', 'comment')
            ->update(['is_plural' => 1]);
    }

    public function down(): void
    {
        DB::table('data_rows')
            ->where('field', 'comment')
            ->update(['is_plural' => 0]);
    }
};
