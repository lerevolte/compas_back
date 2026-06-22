<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (8471) Метрические поля во ВСЕХ сущностях, где они есть (routes, logistic_tasks
 * и т.п.): короткий заголовок + type=number + единица измерения в столбце unit
 * (раньше единица «зашивалась» в заголовок, например «Пробег, км»).
 *
 * Правка идёт по имени поля (field), без привязки к data_type_id — так
 * согласовано с заказчиком. Шаблон новых порталов правит парная ..._in_seeds.php.
 */
return new class extends Migration
{
    /** field => [title, unit] */
    private array $map = [
        'mileage'              => ['Пробег', 'км'],
        'weight'               => ['Вес', 'кг'],
        'volume'               => ['Объем', 'л'],
        'delivery_price'       => ['Цена доставки', 'руб'],
        'reserve_for_delivery' => ['Заложено на доставку', 'руб'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        foreach ($this->map as $field => [$title, $unit]) {
            DB::table('data_rows')
                ->where('field', $field)
                ->update(['title' => $title, 'type' => 'number', 'unit' => $unit]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        // Best-effort откат к прежним «routes»-заголовкам и типу text без единицы.
        $revert = [
            'mileage'              => 'Пробег, км',
            'weight'               => 'Вес, кг',
            'volume'               => 'Объем, л',
            'delivery_price'       => 'Цена доставки, руб',
            'reserve_for_delivery' => 'Заложено на доставку',
        ];

        foreach ($revert as $field => $title) {
            DB::table('data_rows')
                ->where('field', $field)
                ->update(['title' => $title, 'type' => 'text', 'unit' => '']);
        }
    }
};
