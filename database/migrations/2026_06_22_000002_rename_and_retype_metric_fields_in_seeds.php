<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._rename_and_retype_metric_fields.php — то же в базе-шаблоне
 * (connection: seeds) для новых порталов (8471).
 */
return new class extends Migration
{
    private array $map = [
        'mileage'              => ['Пробег', 'км'],
        'weight'               => ['Вес', 'кг'],
        'volume'               => ['Объем', 'л'],
        'delivery_price'       => ['Цена доставки', 'руб'],
        'reserve_for_delivery' => ['Заложено на доставку', 'руб'],
    ];

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        foreach ($this->map as $field => [$title, $unit]) {
            DB::connection('seeds')->table('data_rows')
                ->where('field', $field)
                ->update(['title' => $title, 'type' => 'number', 'unit' => $unit]);
        }
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }

        $revert = [
            'mileage'              => 'Пробег, км',
            'weight'               => 'Вес, кг',
            'volume'               => 'Объем, л',
            'delivery_price'       => 'Цена доставки, руб',
            'reserve_for_delivery' => 'Заложено на доставку',
        ];

        foreach ($revert as $field => $title) {
            DB::connection('seeds')->table('data_rows')
                ->where('field', $field)
                ->update(['title' => $title, 'type' => 'text', 'unit' => '']);
        }
    }
};
