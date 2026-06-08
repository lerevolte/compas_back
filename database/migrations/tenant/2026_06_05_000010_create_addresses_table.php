<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица «Справочник адресов» (addresses).
 *
 * Схема скопирована из logistic_tasks (поля, нужные адресу). По требованию
 * задачи: если таблицы нет — создаём, если есть — пересоздаём с актуальной
 * схемой (старая addresses из tenant_delete имела другой набор колонок).
 *
 * Эта миграция покрывает:
 *  - новые порталы (применяется автоматически из database/migrations/tenant/);
 *  - существующие порталы — через `php artisan tenants:migrate`.
 *
 * Для базы-шаблона admin_seeds таблицу создаёт команда
 * `php artisan entity:install-addresses seeds` (она же ставит метаданные сущности).
 */
return new class extends Migration
{
    public function up()
    {
        // «если есть — перезаписать»: таблица addresses либо отсутствует, либо
        // содержит устаревшую (удалённую) схему — пересоздаём под актуальную.
        Schema::dropIfExists('addresses');

        Schema::create('addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('choosed_at')->nullable();
            $table->json('address')->nullable();
            $table->text('name')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('sort')->nullable();
            $table->string('client_id')->nullable();
            $table->text('photo')->nullable();
            $table->text('car_requirements')->nullable();
            $table->text('employee_requirements')->nullable();
            $table->integer('service_time')->nullable()->default(0);
            $table->text('phone')->nullable();
            $table->text('time')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};
