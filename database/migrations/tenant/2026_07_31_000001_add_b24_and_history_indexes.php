<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['deals', 'contacts', 'companies'] as $table) {
            $this->addIndex($table, ['b24_id'], $table.'_b24_id_index');
        }
        $this->addIndex('histories', ['entity', 'entity_id'], 'histories_entity_entity_id_index');
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($name);
        if ($exists) {
            return;
        }
        Schema::table($table, function (Blueprint $t) use ($columns, $name) {
            $t->index($columns, $name);
        });
    }

    public function down(): void
    {
        foreach (['deals', 'contacts', 'companies'] as $table) {
            $this->dropIndex($table, $table.'_b24_id_index');
        }
        $this->dropIndex('histories', 'histories_entity_entity_id_index');
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $exists = collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($name);
        if (!$exists) {
            return;
        }
        Schema::table($table, function (Blueprint $t) use ($name) {
            $t->dropIndex($name);
        });
    }
};
