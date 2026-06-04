<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('logistic_tasks')) {
            // Поля, приходящие из вебхука Bitrix24 (см. Bitrix24Controller@dealHook),
            // которым не нашлось готовой колонки в logistic_tasks. Номер сделки в
            // отдельной колонке не храним — он пишется в name (по согласованию).
            $cols = [
                'comment'       => 'text',     // UF_CRM_5EAFC3D4C5F76 — комментарий
                'unloading'     => 'text',     // UF_CRM_1762411084 — тип разгрузки (json-массив)
                'type'          => 'integer',  // UF_CRM_1625083610453 — тип машины/доставки (код)
                'pallets_count' => 'text',     // UF_CRM_1696596978695 — кол-во паллет
                'crm_link'      => 'text',     // ссылка на сделку в CRM
                'payment'       => 'text',     // строка оплаты (Нал/Счёт)
                'payment_type'  => 'integer',  // 1 = счёт, 2 = наличные
            ];
            foreach ($cols as $col => $type) {
                if (!Schema::hasColumn('logistic_tasks', $col)) {
                    Schema::table('logistic_tasks', function (Blueprint $table) use ($col, $type) {
                        if ($type === 'integer') {
                            $table->integer($col)->nullable();
                        } else {
                            $table->text($col)->nullable();
                        }
                    });
                }
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('logistic_tasks')) {
            foreach (['comment', 'unloading', 'type', 'pallets_count', 'crm_link', 'payment', 'payment_type'] as $col) {
                if (Schema::hasColumn('logistic_tasks', $col)) {
                    Schema::table('logistic_tasks', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }
    }
};
