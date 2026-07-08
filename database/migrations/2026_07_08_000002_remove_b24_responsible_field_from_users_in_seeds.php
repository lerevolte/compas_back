<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = ['b24_responsible'];

    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $db = DB::connection('seeds');
        $usersTypeId = $db->table('data_types')->where('slug', 'users')->value('id');
        if (!$usersTypeId) {
            return;
        }

        $db->table('data_rows')
            ->where('data_type_id', $usersTypeId)
            ->whereIn('field', $this->fields)
            ->delete();
    }

    public function down(): void
    {
    }
};
