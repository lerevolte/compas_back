<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CopyFields extends Command
{
    protected $signature = 'fields:copy
        {source : id портала-источника (например avixo)}
        {slug : slug сущности}
        {target=seeds : seeds | all-tenants | all (all-tenants + seeds) | <tenant_id>}
        {--field=* : имена полей (data_rows.field); без опции — все непостоянные поля сущности}
        {--dry-run : только показать план}';

    protected $description = 'Скопировать поля сущности с одного портала в seeds / другие порталы (data_rows + field_values + колонка); существующие по имени пропускаются';

    private $srcRows;
    private $srcSections;
    private $srcValues;
    private $srcGroups;

    public function handle(): int
    {
        $sourceId = (string) $this->argument('source');
        $slug = (string) $this->argument('slug');
        $target = (string) $this->argument('target');
        $source = Tenant::find($sourceId);
        if (!$source) {
            $this->error("Портал '{$sourceId}' не найден");
            return self::FAILURE;
        }

        $fields = array_values(array_filter($this->option('field')));
        $source->run(function () use ($slug, $fields) {
            $typeId = \DB::table('data_types')->where('slug', $slug)->value('id');
            if (!$typeId) {
                throw new \RuntimeException("сущности {$slug} нет в источнике");
            }
            $query = \DB::table('data_rows')->where('data_type_id', $typeId)->where('is_remove', 0);
            count($fields) ? $query->whereIn('field', $fields) : $query->where('is_permanent', 0);
            $this->srcRows = $query->orderBy('sort')->get();
            $this->srcSections = \DB::table('field_sections')->where('page', $slug)->get()->keyBy('id');
            $this->srcValues = \DB::table('field_values')->whereIn('field_id', $this->srcRows->pluck('id')->all())->get()->groupBy('field_id');
            $this->srcGroups = \DB::table('data_rows')->where('data_type_id', $typeId)->where('type', 'text_group')->get()->keyBy('id');
        });

        if ($this->srcRows->isEmpty()) {
            $this->warn('Полей для копирования не найдено');
            return self::SUCCESS;
        }
        $this->info('К копированию: ' . $this->srcRows->map(fn ($r) => "{$r->field} («{$r->title}»)")->implode(', '));

        if ($target === 'seeds' || $target === 'all') {
            $this->copyInto(\DB::connection('seeds'), 'admin_seeds', $slug, false);
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }
        if ($target === 'all-tenants' || $target === 'all') {
            foreach (Tenant::get() as $tenant) {
                if ((string) $tenant->id === $sourceId) {
                    continue;
                }
                try {
                    $tenant->run(fn () => $this->copyInto(\DB::connection(), (string) $tenant->id, $slug, true));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->copyInto(\DB::connection(), $target, $slug, true));

        return self::SUCCESS;
    }

    private function copyInto($db, string $label, string $slug, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', $slug)->value('id');
        if (!$typeId || !$sb->hasTable($slug)) {
            $this->line("  - {$label}: сущности {$slug} нет, пропуск");
            return;
        }
        $dry = (bool) $this->option('dry-run');
        $added = [];
        $skipped = [];

        foreach ($this->srcRows as $row) {
            if ($db->table('data_rows')->where('data_type_id', $typeId)->where('field', $row->field)->exists()) {
                $skipped[] = $row->field;
                continue;
            }
            $added[] = $row->field;
            if ($dry) {
                continue;
            }

            $arr = (array) $row;
            $srcId = $arr['id'];
            unset($arr['id']);
            $arr['data_type_id'] = $typeId;
            $arr['module'] = $arr['module'] === '' ? '' : null;
            $arr['module_section_id'] = null;

            $sectionId = null;
            $srcSection = $this->srcSections[$row->section_id] ?? null;
            if ($srcSection) {
                $sectionId = $db->table('field_sections')
                    ->where('page', $slug)
                    ->where('name', $srcSection->name)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->value('id');
            }
            if (!$sectionId) {
                $sectionId = $db->table('field_sections')
                    ->where('page', $slug)
                    ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                    ->orderBy('sort')
                    ->value('id');
            }
            $arr['section_id'] = $sectionId;

            $arr['group_id'] = null;
            $srcGroup = $row->group_id ? ($this->srcGroups[$row->group_id] ?? null) : null;
            if ($srcGroup) {
                $group = $db->table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('type', 'text_group')
                    ->where('title', $srcGroup->title)
                    ->where('is_remove', 0)
                    ->first();
                if ($group) {
                    $arr['group_id'] = $group->id;
                }
            }
            if ($row->type === 'text_group') {
                $arr['subfields'] = null;
            }

            $newId = $db->table('data_rows')->insertGetId($arr);

            if ($arr['group_id']) {
                $group = $db->table('data_rows')->where('id', $arr['group_id'])->first();
                $sub = json_decode((string) ($group->subfields ?? ''), true);
                $sub = is_array($sub) ? $sub : [];
                if (!in_array($newId, $sub)) {
                    $sub[] = $newId;
                    $db->table('data_rows')->where('id', $group->id)->update(['subfields' => json_encode($sub)]);
                }
            }

            foreach (($this->srcValues->get($srcId) ?? collect()) as $fv) {
                $fvArr = (array) $fv;
                unset($fvArr['id']);
                $fvArr['field_id'] = $newId;
                $db->table('field_values')->insert($fvArr);
            }

            if (!$sb->hasColumn($slug, $row->field)) {
                $db->statement("ALTER TABLE `{$slug}` ADD COLUMN `{$row->field}` TEXT NULL");
            }
        }

        $this->line("  ✓ {$label}: " . ($dry ? '[dry-run] ' : '') . 'добавлено ' . count($added) . (count($added) ? ' (' . implode(', ', $added) . ')' : '') . ', пропущено ' . count($skipped));

        if ($dry || !count($added)) {
            return;
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . $slug)->update(['updated_at' => now()]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
