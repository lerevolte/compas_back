<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\RelationFieldsService;
use Illuminate\Console\Command;

class ReceiptSingleReceiver extends Command
{
    public const SLUG = 'receipt_invoices';
    public const FIELD = 'company_id';

    protected $signature = 'receipts:single-receiver
        {target=all : seeds | all-tenants | all (all-tenants + seeds) | <tenant_id>}
        {--dry-run : только показать план}';

    protected $description = '«Компания получатель» (company_id) у приходных накладных — одиночная связь; сохранённые списки сводятся к первой компании';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds' || $target === 'all') {
            $this->apply(\DB::connection('seeds'), 'admin_seeds', false);
            if ($target === 'seeds') {
                return self::SUCCESS;
            }
        }

        if ($target === 'all-tenants' || $target === 'all') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->apply(\DB::connection(), (string) $tenant->id, true));
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->apply(\DB::connection(), $target, true));

        return self::SUCCESS;
    }

    private function apply($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        $typeId = $db->table('data_types')->where('slug', self::SLUG)->value('id');
        if (!$typeId || !$sb->hasTable(self::SLUG) || !$sb->hasColumn(self::SLUG, self::FIELD)) {
            $this->line("  - {$label}: приходных накладных нет");
            return;
        }

        $dry = (bool) $this->option('dry-run');
        $rowIds = $db->table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->pluck('id')->all();
        if (!$dry && count($rowIds)) {
            $db->table('data_rows')->whereIn('id', $rowIds)->update(['is_plural' => 0]);
        }

        $converted = 0;
        $multiple = [];
        $db->table(self::SLUG)->whereNotNull(self::FIELD)->where(self::FIELD, 'like', '[%')->orderBy('id')
            ->chunkById(200, function ($rows) use ($db, $dry, &$converted, &$multiple) {
                foreach ($rows as $row) {
                    $ids = RelationFieldsService::ids($row->{self::FIELD});
                    if (count($ids) > 1) {
                        $multiple[] = $row->id . ' (' . implode(', ', $ids) . ')';
                    }
                    if (!$dry) {
                        $db->table(self::SLUG)->where('id', $row->id)->update([self::FIELD => $ids[0] ?? null]);
                    }
                    $converted++;
                }
            });

        $this->line("  ✓ {$label}: " . ($dry ? '[dry-run] ' : '') . 'полей ' . count($rowIds) . ", значений сведено {$converted}");
        if (count($multiple)) {
            $this->warn('    несколько компаний, оставлена первая: ' . implode('; ', $multiple));
        }

        if ($dry) {
            return;
        }
        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . self::SLUG)->update(['updated_at' => now()]);
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
