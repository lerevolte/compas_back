<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $fields = [
        'statuses' => ['title' => 'Статусы', 'type' => 'route_statuses'],
        'route_map' => ['title' => 'Маршрут на карте', 'type' => 'route_map'],
    ];

    public function up(): void
    {
        $schema = Schema::connection('seeds');
        if (!$schema->hasTable('routes')) {
            return;
        }

        foreach (array_keys($this->fields) as $column) {
            if (!$schema->hasColumn('routes', $column)) {
                $schema->table('routes', function (Blueprint $table) use ($column) {
                    $table->string($column)->nullable();
                });
            }
        }

        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $db = DB::connection('seeds');
        $routesTypeId = $db->table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $routesTypeId)->max('sort');
        $sectionId = $db->table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->where('field', 'name')
            ->value('section_id');

        foreach ($this->fields as $field => $meta) {
            $exists = $db->table('data_rows')
                ->where('data_type_id', $routesTypeId)
                ->where('field', $field)
                ->exists();
            if ($exists) {
                continue;
            }
            $db->table('data_rows')->insert([
                'data_type_id' => $routesTypeId,
                'field' => $field,
                'type' => $meta['type'],
                'title' => $meta['title'],
                'required' => 0,
                'only_read' => 1,
                'section_id' => $sectionId,
                'sort' => ++$maxSort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
                    ->whereIn('field', array_keys($this->fields))
                    ->delete();
            }
        }

        if ($schema->hasTable('routes')) {
            foreach (array_keys($this->fields) as $column) {
                if ($schema->hasColumn('routes', $column)) {
                    $schema->table('routes', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }
};
