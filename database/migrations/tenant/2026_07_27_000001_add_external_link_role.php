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
        if (!$schema->hasTable('roles') || !$schema->hasTable('permissions')) {
            return;
        }

        $now = \Carbon\Carbon::now();
        $role = $db->table('roles')->where('name', 'external_link')->whereNull('deleted_at')->first();

        if (!$role) {
            $tables = null;
            if ($schema->hasTable('users')) {
                $tables = $db->table('users')->where('id', 1)->value('tables');
            }
            $roleId = $db->table('roles')->insertGetId([
                'name' => 'external_link',
                'display_name' => 'Внешняя ссылка',
                'is_admin' => 0,
                'is_permanent' => 1,
                'sort' => 0,
                'tables' => $tables,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $roleId = $role->id;
            if (!$role->is_permanent) {
                $db->table('roles')->where('id', $roleId)->update(['is_permanent' => 1]);
            }
        }

        if (!$schema->hasTable('data_types')) {
            return;
        }

        $typeIds = $db->table('data_types')->pluck('id');
        $existing = $db->table('permissions')
            ->where('role_id', $roleId)
            ->whereNotNull('entity_id')
            ->pluck('entity_id')
            ->map(fn ($i) => (int) $i)
            ->all();

        foreach ($typeIds as $typeId) {
            if (in_array((int) $typeId, $existing, true)) {
                continue;
            }
            $db->table('permissions')->insert([
                'entity_id' => $typeId,
                'role_id' => $roleId,
                'read_p' => 'A',
                'create_p' => 'N',
                'update_p' => 'N',
                'delete_p' => 'N',
                'export_p' => 'N',
                'import_p' => 'N',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
    }
};
