<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Создаёт таблицу bitrix24_config в каждой тенантской БД.
// Модуль Bitrix24 хранил свою миграцию в Modules/Bitrix24/Database/Migrations/,
// которая подхватывается только php artisan migrate (central DB). Здесь
// дублируем её для тенантских баз.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bitrix24_config')) {
            Schema::create('bitrix24_config', function (Blueprint $table) {
                $table->id();
                $table->string('webhook')->nullable();
                $table->text('config')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrix24_config');
    }
};
