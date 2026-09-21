<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection();
        \App\Console\Commands\InstallContactsEntity::ensureTables($db);
        \App\Console\Commands\InstallSabyModule::ensureTables($db);
        \App\Services\DocumentNumber::ensureTable($db);
    }

    public function down(): void
    {
    }
};
