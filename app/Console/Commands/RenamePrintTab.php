<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class RenamePrintTab extends Command
{
    protected $signature = 'entity:rename-print-tab
        {target=all-tenants : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Переименовать вкладку «Печать документов» в «Документы для заказа»';

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->renameIn(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        $tenants = $target === 'all-tenants' ? Tenant::get() : collect([Tenant::find($target)])->filter();
        if (!$tenants->count()) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        foreach ($tenants as $tenant) {
            try {
                $tenant->run(fn () => $this->renameIn(\DB::connection(), (string) $tenant->id, true));
            } catch (\Throwable $e) {
                $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
            }
        }
        if ($target === 'all-tenants') {
            $this->renameIn(\DB::connection('seeds'), 'admin_seeds', false);
        }

        return self::SUCCESS;
    }

    private function renameIn($db, string $label, bool $inTenant): void
    {
        $changed = [];
        foreach ($db->table('settings')->where('type', 'menu')->get() as $menu) {
            $tabs = json_decode((string) $menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }
            $dirty = false;
            foreach ($tabs as &$tab) {
                if (is_array($tab) && ($tab['tab'] ?? null) === InstallSaleDocsEntities::PRINT_TAB && ($tab['title'] ?? null) === InstallSaleDocsEntities::PRINT_TAB_OLD_TITLE) {
                    $tab['title'] = InstallSaleDocsEntities::PRINT_TAB_TITLE;
                    $dirty = true;
                }
            }
            unset($tab);
            if ($dirty) {
                $db->table('settings')->where('id', $menu->id)->update(['value' => json_encode($tabs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
                $changed[] = (string) $menu->entity;
            }
        }
        $changed = array_values(array_unique($changed));
        if (count($changed)) {
            try {
                $db->table('local_cache')->whereIn('url', array_map(fn ($s) => 'fields/' . $s, $changed))->update(['updated_at' => now()]);
            } catch (\Throwable $e) {
            }
            if ($inTenant) {
                try {
                    \App\Models\Settings::clear_cache();
                } catch (\Throwable $e) {
                }
            }
        }
        $this->line("  [{$label}] " . (count($changed) ? 'переименовано: ' . implode(', ', $changed) : 'нечего переименовывать'));
    }
}
