<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        self::apply(DB::connection(), Schema::getFacadeRoot());

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
    }

    public static function apply($db, $schema): void
    {
        if (!$schema->hasTable('data_rows') || !$schema->hasTable('data_types')) {
            return;
        }
        $typeId = $db->table('data_types')->where('slug', 'products')->value('id');
        if (!$typeId) {
            return;
        }
        $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('field', 'category_id')
            ->where(function ($q) {
                $q->whereNull('relation_table')->orWhere('relation_table', '');
            })
            ->update(['relation_table' => 'categories']);
    }

    public function down(): void
    {
    }
};
