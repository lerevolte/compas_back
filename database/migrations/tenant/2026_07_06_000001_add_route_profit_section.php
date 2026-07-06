<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sectionFields = ['reserve_for_delivery', 'mileage_cost', 'delivery_cost'];

    public function up(): void
    {
        if (!Schema::hasTable('routes') || !Schema::hasTable('data_rows') || !Schema::hasTable('data_types') || !Schema::hasTable('field_sections')) {
            return;
        }

        if (!Schema::hasColumn('routes', 'delivery_cost')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->string('delivery_cost')->nullable();
            });
        }

        $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        $sectionId = DB::table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');
        if (!$sectionId) {
            $maxSort = (int) DB::table('field_sections')->where('page', 'routes')->max('sort');
            $sectionId = DB::table('field_sections')->insertGetId([
                'name' => 'Прибыль по маршруту',
                'page' => 'routes',
                'sort' => $maxSort + 1,
                'column_id' => 1,
                'hide' => 0,
                'is_short' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exists = DB::table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->where('field', 'delivery_cost')
            ->exists();
        if (!$exists) {
            $src = DB::table('data_rows')
                ->where('data_type_id', $routesTypeId)
                ->where('field', 'mileage_cost')
                ->first();
            if (!$src) {
                $src = DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', 'mileage')
                    ->first();
            }

            $row = $src ? (array) $src : ['data_type_id' => $routesTypeId, 'required' => 0, 'sort' => 100];
            unset($row['id']);
            $row['data_type_id'] = $routesTypeId;
            $row['field'] = 'delivery_cost';
            $row['title'] = 'Стоимость доставки';
            $row['type'] = 'number';
            $row['unit'] = 'руб';
            $row['only_read'] = 0;
            $row['section_id'] = $sectionId;
            if ($src && $src->sort !== null) {
                $row['sort'] = $src->sort + 1;
            }
            $row['created_at'] = now();
            $row['updated_at'] = now();

            DB::table('data_rows')->insert($row);
        }

        DB::table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->whereIn('field', $this->sectionFields)
            ->update(['section_id' => $sectionId]);

        if (Schema::hasTable('section_fields_sort')) {
            $sort = 0;
            foreach ($this->sectionFields as $field) {
                $fieldId = DB::table('data_rows')
                    ->where('data_type_id', $routesTypeId)
                    ->where('field', $field)
                    ->value('id');
                if (!$fieldId) {
                    continue;
                }
                $rowExists = DB::table('section_fields_sort')
                    ->where('section_id', $sectionId)
                    ->where('field_id', $fieldId)
                    ->exists();
                if (!$rowExists) {
                    DB::table('section_fields_sort')->insert([
                        'section_id' => $sectionId,
                        'field_id' => $fieldId,
                        'sort' => $sort,
                    ]);
                }
                $sort++;
            }
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows') || !Schema::hasTable('data_types')) {
            return;
        }

        $routesTypeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        if (!$routesTypeId) {
            return;
        }

        $sectionId = DB::table('field_sections')
            ->where('page', 'routes')
            ->where('name', 'Прибыль по маршруту')
            ->value('id');

        if ($sectionId) {
            $fallbackSectionId = DB::table('field_sections')
                ->where('page', 'routes')
                ->where('id', '!=', $sectionId)
                ->orderBy('id')
                ->value('id');
            DB::table('data_rows')
                ->where('data_type_id', $routesTypeId)
                ->whereIn('field', ['reserve_for_delivery', 'mileage_cost'])
                ->update(['section_id' => $fallbackSectionId]);
            if (Schema::hasTable('section_fields_sort')) {
                DB::table('section_fields_sort')->where('section_id', $sectionId)->delete();
            }
            DB::table('field_sections')->where('id', $sectionId)->delete();
        }

        DB::table('data_rows')
            ->where('data_type_id', $routesTypeId)
            ->where('field', 'delivery_cost')
            ->delete();

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'delivery_cost')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->dropColumn('delivery_cost');
            });
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }
};
