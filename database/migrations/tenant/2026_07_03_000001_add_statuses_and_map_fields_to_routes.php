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
        if (!Schema::hasTable('routes')) {
            return;
        }

        foreach (array_keys($this->fields) as $column) {
            if (!Schema::hasColumn('routes', $column)) {
                Schema::table('routes', function (Blueprint $table) use ($column) {
                    $table->string($column)->nullable();
                });
            }
        }

        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }
        $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        $maxSort = (int) DB::table('data_rows')->where('data_type_id', $routesTypeId)->max('sort');
        $sectionId = DB::table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->where('field', 'name')
            ->value('section_id');

        foreach ($this->fields as $field => $meta) {
            $exists = DB::table('data_rows')
                ->where('data_type_id', $routesTypeId)
                ->where('field', $field)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('data_rows')->insert([
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

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_rows') && Schema::hasTable('data_types')) {
            $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
            if ($routesTypeId) {
                DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->whereIn('field', array_keys($this->fields))
                    ->delete();
            }
        }

        if (Schema::hasTable('routes')) {
            foreach (array_keys($this->fields) as $column) {
                if (Schema::hasColumn('routes', $column)) {
                    Schema::table('routes', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
