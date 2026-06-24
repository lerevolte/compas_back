<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $carsTypeId = DB::table('data_types')->where('slug', 'cars')->value('id');
        $empTypeId  = DB::table('data_types')->where('slug', 'employees')->value('id');
        if (!$carsTypeId || !$empTypeId) {
            return;
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->exists();
        if ($exists) {
            return;
        }

        $src = DB::table('data_rows')
            ->where('data_type_id', $carsTypeId)
            ->where('field', 'color_status')
            ->first();
        if (!$src) {
            return;
        }

        $sectionId = DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'name')
            ->value('section_id');

        $row = (array) $src;
        unset($row['id']);
        $row['data_type_id'] = $empTypeId;
        if ($sectionId) {
            $row['section_id'] = $sectionId;
        }

        DB::table('data_rows')->insert($row);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $empTypeId = DB::table('data_types')->where('slug', 'employees')->value('id');
        if (!$empTypeId) {
            return;
        }

        DB::table('data_rows')
            ->where('data_type_id', $empTypeId)
            ->where('field', 'color_status')
            ->delete();
    }
};
