<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tasks') || !Schema::hasColumn('tasks', 'name')) {
            return;
        }

        $rows = DB::table('tasks')->whereNotNull('name')->get(['id', 'name']);

        foreach ($rows as $row) {
            $decoded = json_decode($row->name, true);
            if (!is_array($decoded) || !isset($decoded['value']) || !is_string($decoded['value'])) {
                continue;
            }

            $value = preg_replace('/^Сделка\s*№\s*/u', '', $decoded['value']);
            if ($value === $decoded['value']) {
                continue;
            }

            $decoded['value'] = $value;
            DB::table('tasks')->where('id', $row->id)->update([
                'name' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
};
