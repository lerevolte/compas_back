<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ShipmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairActionTypeField extends Command
{
    protected $signature = 'logistic:repair-action-type
        {target : <tenant_id>}
        {--unloading= : id значения «выгрузки» у задач}
        {--loading= : id значения «загрузки» у задач, 0 — загрузки нет}
        {--supply= : id значения «приход от поставщика» у задач}
        {--warehouse= : id значения «склад погрузки» у задач}
        {--dry-run : только показать, что будет исправлено}';

    protected $description = 'Восстановить программные значения поля «Тип действия» задач, таблицу соответствий у библиотеки/быстрых задач (по названиям) и перевести задачи с чужими значениями на значения задач';

    private const KEYS = ['unloading' => 'unloading_value_id', 'loading' => 'loading_value_id', 'supply' => 'supply_value_id', 'warehouse' => 'warehouse_value_id'];

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');
        $tenant->run(function () use ($dry) {
            $typeId = DB::table('data_types')->where('slug', 'logistic_tasks')->value('id');
            $taskField = $typeId ? DB::table('data_rows')->where('data_type_id', $typeId)->where('field', ShipmentService::ACTION_FIELD)->first() : null;
            if (!$taskField) {
                $this->error('У задач нет поля action_type');
                return;
            }
            $taskValues = DB::table('field_values')->where('field_id', $taskField->id)->get(['id', 'value']);
            $taskIds = $taskValues->pluck('id')->map(fn ($v) => (int) $v)->all();

            $details = json_decode((string) $taskField->details, true);
            $details = is_array($details) ? $details : [];
            foreach (self::KEYS as $option => $key) {
                $value = $this->option($option);
                if ($value === null || $value === '') {
                    continue;
                }
                if ((int) $value === 0 && $option !== 'unloading') {
                    $this->line("  {$key}: " . ($details[$key] ?? '—') . ' → нет (значение не назначено)');
                    $details[$key] = 0;
                    continue;
                }
                if (!in_array((int) $value, $taskIds, true)) {
                    $this->error("  {$key}: значение {$value} не принадлежит полю задач, пропуск");
                    continue;
                }
                $this->line("  {$key}: " . ($details[$key] ?? '—') . " → {$value} («" . $taskValues->firstWhere('id', (int) $value)->value . '»)');
                $details[$key] = (int) $value;
            }
            if (!$dry) {
                DB::table('data_rows')->where('id', $taskField->id)->update(['details' => json_encode($details, JSON_UNESCAPED_UNICODE)]);
            }

            $byLabel = [];
            foreach ($taskValues as $value) {
                $label = mb_strtolower(trim((string) $value->value));
                if ($label !== '' && !isset($byLabel[$label])) {
                    $byLabel[$label] = (int) $value->id;
                }
            }
            foreach (['addresses', 'warehouses'] as $slug) {
                $mirrorTypeId = DB::table('data_types')->where('slug', $slug)->value('id');
                $mirror = $mirrorTypeId ? DB::table('data_rows')->where('data_type_id', $mirrorTypeId)->where('field', ShipmentService::ACTION_FIELD)->first() : null;
                if (!$mirror) {
                    continue;
                }
                $mirrorDetails = json_decode((string) $mirror->details, true);
                $mirrorDetails = is_array($mirrorDetails) ? $mirrorDetails : [];
                $map = [];
                foreach (DB::table('field_values')->where('field_id', $mirror->id)->get(['id', 'value']) as $value) {
                    $existing = $mirrorDetails['task_value_map'][(string) $value->id] ?? null;
                    if ($existing && in_array((int) $existing, $taskIds, true)) {
                        $map[(string) $value->id] = (int) $existing;
                        continue;
                    }
                    $target = $byLabel[mb_strtolower(trim((string) $value->value))] ?? null;
                    if ($target) {
                        $map[(string) $value->id] = $target;
                    } else {
                        $this->warn("  {$slug}: у значения «{$value->value}» (id {$value->id}) нет пары у задач");
                    }
                }
                $this->line("  {$slug}: соответствий " . count($map));
                if (!$dry) {
                    $mirrorDetails['task_value_map'] = $map;
                    DB::table('data_rows')->where('id', $mirror->id)->update(['details' => json_encode($mirrorDetails, JSON_UNESCAPED_UNICODE)]);
                }
            }

            $bad = DB::table('logistic_tasks')
                ->whereNotNull(ShipmentService::ACTION_FIELD)
                ->where(ShipmentService::ACTION_FIELD, '!=', '')
                ->whereNotIn(ShipmentService::ACTION_FIELD, array_map('strval', $taskIds))
                ->get(['id', ShipmentService::ACTION_FIELD]);
            $fixed = 0;
            foreach ($bad as $task) {
                $mapped = ShipmentService::taskActionValue($task->{ShipmentService::ACTION_FIELD});
                if ((string) $mapped === (string) $task->{ShipmentService::ACTION_FIELD} || !in_array((int) $mapped, $taskIds, true)) {
                    $this->warn("  задача {$task->id}: значение {$task->{ShipmentService::ACTION_FIELD}} не удалось сопоставить");
                    continue;
                }
                if (!$dry) {
                    DB::table('logistic_tasks')->where('id', $task->id)->update([ShipmentService::ACTION_FIELD => (string) $mapped]);
                }
                $fixed++;
            }
            $this->info('  задач с чужим значением: ' . $bad->count() . ', исправлено: ' . $fixed . ($dry ? ' (dry-run)' : ''));

            if (!$dry) {
                try {
                    if (Schema::hasTable('local_cache')) {
                        DB::table('local_cache')->whereIn('url', ['fields/logistic_tasks', 'fields/addresses', 'fields/warehouses'])->update(['updated_at' => now()]);
                    }
                    \App\Models\Settings::clear_cache();
                } catch (\Throwable $e) {
                }
            }
        });

        return self::SUCCESS;
    }
}
