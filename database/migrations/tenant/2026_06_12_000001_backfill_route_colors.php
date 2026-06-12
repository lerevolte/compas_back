<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * У всех маршрутов должен стоять цвет (как у остальных сущностей).
 * Существующим маршрутам без цвета назначаем дефолтный — через ту же
 * палитру field_values, что и Route::defaultColorId().
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('routes') || !Schema::hasColumn('routes', 'color')) {
            return;
        }

        $ids = DB::table('routes')
            ->where(function ($q) {
                $q->whereNull('color')->orWhere('color', '');
            })
            ->pluck('id');

        foreach ($ids as $id) {
            $colorId = \App\Models\Route::defaultColorId();
            if (!$colorId) {
                // У сущности routes не объявлено поле color — заводить
                // палитру не из чего, оставляем как есть.
                return;
            }
            DB::table('routes')->where('id', $id)->update(['color' => $colorId]);
        }
    }
};
