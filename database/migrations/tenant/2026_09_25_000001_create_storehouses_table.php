<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        \App\Console\Commands\InstallStorehouses::ensureTable(DB::connection());
    }

    public function down(): void
    {
    }
};
