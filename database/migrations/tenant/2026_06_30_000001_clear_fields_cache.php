<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Разовый сброс кэша после фикса Settings::clear_cache (теперь он чистит и
 * кэш полей моделей "{tenant}:{table}-fields"). До фикса добавление поля
 * «Оплата, руб» не сбрасывало этот кэш, и getFields отдавал устаревший список
 * без него — значения поля не попадали в /routes/{id}/tasks (8579).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (class_exists(\App\Models\Settings::class)) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
                // memcached может быть недоступен в окружении миграции — не падаем.
            }
        }
    }

    public function down(): void
    {
        // Откат не требуется — это разовый сброс кэша.
    }
};
