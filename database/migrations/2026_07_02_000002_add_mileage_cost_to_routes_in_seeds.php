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
        if (!$schema->hasTable('routes')) {
            return;
        }

        if (!$schema->hasColumn('routes', 'mileage_cost')) {
            $schema->table('routes', function (Blueprint $table) {
                $table->string('mileage_cost')->nullable();
            });
        }

        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $db = DB::connection('seeds');
            $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                $exists = $db->table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'mileage_cost')
                    ->exists();
                if (!$exists) {
                    $src = $db->table('data_rows')
                        ->where('data_type_id', $routesTypeId)
                        ->where('field', 'mileage')
                        ->first();

                    $row = $src ? (array) $src : ['data_type_id' => $routesTypeId, 'required' => 0, 'sort' => 100];
                    unset($row['id']);
                    $row['data_type_id'] = $routesTypeId;
                    $row['field'] = 'mileage_cost';
                    $row['title'] = 'Общий расход за километраж';
                    $row['type'] = 'number';
                    $row['unit'] = 'руб';
                    $row['only_read'] = 1;
                    if ($src && $src->sort !== null) {
                        $row['sort'] = $src->sort + 1;
                    }
                    $row['created_at'] = now();
                    $row['updated_at'] = now();

                    $db->table('data_rows')->insert($row);
                }
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('seeds');
        if ($schema->hasTable('data_rows') && $schema->hasTable('data_types')) {
            $db = DB::connection('seeds');
            $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                $db->table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'mileage_cost')
                    ->delete();
            }
        }

        if ($schema->hasTable('routes') && $schema->hasColumn('routes', 'mileage_cost')) {
            $schema->table('routes', function (Blueprint $table) {
                $table->dropColumn('mileage_cost');
            });
        }
    }
};
