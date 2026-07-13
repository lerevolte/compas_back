<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('seeds');
        $db = DB::connection('seeds');

        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $typeIds = $db->table('data_types')->where('slug', 'routes')->pluck('id');
            if ($typeIds->isNotEmpty()) {
                $rowIds = $db->table('data_rows')
                    ->whereIn('data_type_id', $typeIds)
                    ->where('field', 'color')
                    ->pluck('id');
                if ($rowIds->isNotEmpty()) {
                    if ($schema->hasTable('field_values')) {
                        $db->table('field_values')->whereIn('field_id', $rowIds)->delete();
                    }
                    $db->table('data_rows')->whereIn('id', $rowIds)->delete();
                }
            }
        }

        if ($schema->hasTable('routes') && $schema->hasColumn('routes', 'color')) {
            $schema->table('routes', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');

        if ($schema->hasTable('routes') && !$schema->hasColumn('routes', 'color')) {
            $schema->table('routes', function (Blueprint $table) {
                $table->text('color')->nullable();
            });
        }
    }
};
