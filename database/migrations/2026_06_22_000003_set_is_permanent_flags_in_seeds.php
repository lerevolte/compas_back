<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Парная к tenant/..._set_is_permanent_flags.php — то же в базе-шаблоне
 * (connection: seeds) для новых порталов (8472/8473/8475).
 */
return new class extends Migration
{
    private array $lt_permanent = [
        'delivery_date', 'service_time', 'weight', 'volume', 'delivery_price',
        'reserve_for_delivery', 'phone', 'priority', 'plan_time', 'time',
    ];
    private array $cars_not_permanent = ['sts_number', 'toplivnaia_karta_3298', 'number', 'faily_3316'];
    private array $emp_not_permanent = ['driver_license', 'dokumenty_3317'];

    public function up(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }
        $db = DB::connection('seeds');

        $db->table('data_rows')->where('data_type_id', 3)
            ->where('field', 'car_type')->update(['is_permanent' => 0]);
        $db->table('data_rows')->where('data_type_id', 3)
            ->whereIn('field', $this->lt_permanent)->update(['is_permanent' => 1]);

        $db->table('data_rows')->where('data_type_id', 10)->update(['is_permanent' => 1]);

        $db->table('data_rows')->where('data_type_id', 7)->update(['is_permanent' => 1]);
        $db->table('data_rows')->where('data_type_id', 7)
            ->whereIn('field', $this->cars_not_permanent)->update(['is_permanent' => 0]);

        $db->table('data_rows')->where('data_type_id', 8)->update(['is_permanent' => 1]);
        $db->table('data_rows')->where('data_type_id', 8)
            ->whereIn('field', $this->emp_not_permanent)->update(['is_permanent' => 0]);

        $db->table('data_rows')->where('data_type_id', 275)->update(['is_permanent' => 1]);
    }

    public function down(): void
    {
        if (!Schema::connection('seeds')->hasTable('data_rows')) {
            return;
        }
        DB::connection('seeds')->table('data_rows')->where('data_type_id', 3)
            ->where('field', 'car_type')->update(['is_permanent' => 1]);
    }
};
