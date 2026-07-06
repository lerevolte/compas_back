<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Tenant;

class SyncPermanentFieldsToSeeds extends Command
{
    protected $signature = 'seeds:sync-permanent-fields
        {tenant=avixo : id портала-источника}
        {--dry-run : только показать, что будет добавлено}';

    protected $description = 'Скопировать программные (is_permanent=1) поля портала в шаблон seeds (data_rows + field_values + физические колонки)';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Портал '{$tenantId}' не найден");
            return self::FAILURE;
        }

        $srcTypes = collect();
        $srcRows = collect();
        $srcSections = collect();
        $srcValues = collect();

        $tenant->run(function () use (&$srcTypes, &$srcRows, &$srcSections, &$srcValues) {
            $srcTypes = \DB::table('data_types')->get()->keyBy('id');
            $srcRows = \DB::table('data_rows')->where('is_permanent', 1)->get();
            $srcSections = \DB::table('field_sections')->get()->keyBy('id');
            $fieldIds = $srcRows->pluck('id')->all();
            $srcValues = \DB::table('field_values')->whereIn('field_id', $fieldIds)->get()->groupBy('field_id');
        });

        $db = \DB::connection('seeds');
        $schema = \Illuminate\Support\Facades\Schema::connection('seeds');
        $seedTypes = $db->table('data_types')->get()->keyBy('slug');

        $added = 0;
        $skipped = 0;
        foreach ($srcRows as $row) {
            $srcType = $srcTypes[$row->data_type_id] ?? null;
            if (!$srcType || !$srcType->slug || !$row->field) {
                continue;
            }
            $seedType = $seedTypes[$srcType->slug] ?? null;
            if (!$seedType) {
                $this->warn("  ~ {$srcType->slug}: сущности нет в seeds — пропуск поля {$row->field}");
                continue;
            }

            $exists = $db->table('data_rows')
                ->where('data_type_id', $seedType->id)
                ->where('field', $row->field)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("  + [dry-run] {$srcType->slug}.{$row->field} ({$row->title})");
                $added++;
                continue;
            }

            $arr = (array) $row;
            $srcFieldId = $arr['id'];
            unset($arr['id']);
            $arr['data_type_id'] = $seedType->id;

            $seedSectionId = null;
            $srcSection = $srcSections[$row->section_id] ?? null;
            if ($srcSection) {
                $seedSectionId = $db->table('field_sections')
                    ->where('page', $srcSection->page)
                    ->where('name', $srcSection->name)
                    ->value('id');
            }
            if (!$seedSectionId) {
                $seedSectionId = $db->table('field_sections')
                    ->where('page', $srcType->slug)
                    ->whereNull('module')
                    ->orderBy('id')
                    ->value('id');
            }
            if (!$seedSectionId) {
                $seedSectionId = $db->table('field_sections')
                    ->where('page', $srcType->slug)
                    ->orderBy('id')
                    ->value('id');
            }
            $arr['section_id'] = $seedSectionId;

            $moduleSectionRaw = trim((string) ($arr['module_section_id'] ?? ''));
            $arr['module_section_id'] = null;
            if ($moduleSectionRaw !== '' && $moduleSectionRaw !== '0' && $moduleSectionRaw !== '[]') {
                $decoded = json_decode($moduleSectionRaw, true);
                if (is_array($decoded)) {
                    $mapped = [];
                    foreach ($decoded as $msId) {
                        $ms = $srcSections[$msId] ?? null;
                        if (!$ms) {
                            continue;
                        }
                        $sid = $db->table('field_sections')
                            ->where('page', $ms->page)
                            ->where('name', $ms->name)
                            ->where('module', $ms->module)
                            ->value('id');
                        if ($sid) {
                            $mapped[] = (int) $sid;
                        }
                    }
                    if (count($mapped)) {
                        $arr['module_section_id'] = json_encode($mapped);
                    }
                }
            }

            $newFieldId = $db->table('data_rows')->insertGetId($arr);

            foreach (($srcValues->get($srcFieldId) ?? collect()) as $fv) {
                $fvArr = (array) $fv;
                unset($fvArr['id']);
                $fvArr['field_id'] = $newFieldId;
                $db->table('field_values')->insert($fvArr);
            }

            if ($schema->hasTable($srcType->slug) && !$schema->hasColumn($srcType->slug, $row->field)) {
                $schema->table($srcType->slug, function (Blueprint $table) use ($row) {
                    $table->text($row->field)->nullable();
                });
            }

            $this->info("  + {$srcType->slug}.{$row->field} ({$row->title})");
            $added++;
        }

        $this->info("Готово: добавлено {$added}, уже было {$skipped}");
        return self::SUCCESS;
    }
}
