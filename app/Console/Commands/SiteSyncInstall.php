<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SiteProductSync;
use Illuminate\Console\Command;

class SiteSyncInstall extends Command
{
    protected $signature = 'site-sync:install
        {target=avixo : <tenant_id>}
        {--url= : адрес обработчика на сайте, например https://opt6.ru/local/compas_sync.php}
        {--token= : секретный токен, совпадает с COMPAS_SYNC_TOKEN в обработчике}
        {--disable : выключить синхронизацию}';

    protected $description = 'Настроить отправку характеристик товаров на сайт (таблица site_sync_config портала)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error("Портал '{$this->argument('target')}' не найден");
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            $db = \DB::connection();
            SiteProductSync::ensureTable($db);
            $row = $db->table(SiteProductSync::TABLE)->orderBy('id')->first();
            $attrs = ['updated_at' => now()];
            if ($this->option('url') !== null && $this->option('url') !== '') {
                $attrs['url'] = trim((string) $this->option('url'));
            }
            if ($this->option('token') !== null && $this->option('token') !== '') {
                $attrs['token'] = trim((string) $this->option('token'));
            }
            $attrs['enabled'] = $this->option('disable') ? 0 : 1;
            if ($row) {
                $db->table(SiteProductSync::TABLE)->where('id', $row->id)->update($attrs);
            } else {
                $db->table(SiteProductSync::TABLE)->insert($attrs + ['created_at' => now()]);
            }
            SiteProductSync::resetCache();
            $state = SiteProductSync::ready() ? 'включена' : 'НЕ активна (нет url/token или отключена)';
            $this->info("  ✓ {$tenant->id}: синхронизация с сайтом {$state}");
        });

        return self::SUCCESS;
    }
}
