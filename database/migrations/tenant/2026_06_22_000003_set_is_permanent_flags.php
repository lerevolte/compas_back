<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * (8472/8473/8475) Массовая простановка флага is_permanent у полей (data_rows).
 * is_permanent=1 — поле «системное», его нельзя удалить/переименовать в UI.
 *
 * data_type_id здесь одинаковы во всех порталах (клонируются из шаблона seeds):
 *   3   = logistic_tasks
 *   7   = cars
 *   8   = employees
 *   10  = (служебная сущность)
 *   275 = (служебная сущность)
 *
 * Парная миграция для шаблона новых порталов: ..._in_seeds.php.
 */
return new class extends Migration
{
    // 8472 — logistic_tasks (dt=3)
    private array $lt_permanent = [
        'delivery_date', 'service_time', 'weight', 'volume', 'delivery_price',
        'reserve_for_delivery', 'phone', 'priority', 'plan_time', 'time',
    ];

    // 8473 — cars (dt=7): эти поля НЕ системные, остальные системные
    private array $cars_not_permanent = ['sts_number', 'toplivnaia_karta_3298', 'number', 'faily_3316'];

    // 8473 — employees (dt=8): эти поля НЕ системные, остальные системные
    private array $emp_not_permanent = ['driver_license', 'dokumenty_3317'];

    public function up(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        // 8472 — logistic_tasks
        DB::table('data_rows')->where('data_type_id', 3)
            ->where('field', 'car_type')->update(['is_permanent' => 0]);
        DB::table('data_rows')->where('data_type_id', 3)
            ->whereIn('field', $this->lt_permanent)->update(['is_permanent' => 1]);

        // 8473 — dt=10: все поля системные
        DB::table('data_rows')->where('data_type_id', 10)->update(['is_permanent' => 1]);

        // 8473 — cars (dt=7)
        DB::table('data_rows')->where('data_type_id', 7)->update(['is_permanent' => 1]);
        DB::table('data_rows')->where('data_type_id', 7)
            ->whereIn('field', $this->cars_not_permanent)->update(['is_permanent' => 0]);

        // 8473 — employees (dt=8)
        DB::table('data_rows')->where('data_type_id', 8)->update(['is_permanent' => 1]);
        DB::table('data_rows')->where('data_type_id', 8)
            ->whereIn('field', $this->emp_not_permanent)->update(['is_permanent' => 0]);

        // 8475 — dt=275: все поля системные
        DB::table('data_rows')->where('data_type_id', 275)->update(['is_permanent' => 1]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('data_rows')) {
            return;
        }

        // Точный откат невозможен (прежние значения не сохранялись), возвращаем
        // лишь детерминированные исключения к ожидаемым прежним значениям.
        DB::table('data_rows')->where('data_type_id', 3)
            ->where('field', 'car_type')->update(['is_permanent' => 1]);
    }
};
