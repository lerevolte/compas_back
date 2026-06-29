<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Tenant;
use App\Models\Task;
use Modules\Bitrix24\Entities\Config;
use Modules\Bitrix24\Http\Controllers\Bitrix24Controller;

/**
 * Разовый бэкфилл поля «Оплата, руб» (payment / payment_type) у всех текущих
 * задач логистики из Bitrix24 (8557). Для каждой задачи по её сделке
 * запрашиваются счета (crm.invoice.list по UF_DEAL_ID): если счета есть —
 * «Счет: <номера>», иначе «Нал: <OPPORTUNITY> р.» (OPPORTUNITY берётся из
 * crm.deal.get). Логика идентична вебхуку Bitrix24Controller@dealHook
 * (используются те же статические хелперы).
 *
 *   php artisan bitrix24:sync-payment logistopt6
 *
 * Идемпотентно: значение пересчитывается при каждом запуске. Сохранение
 * тихое (saveQuietly) — без записи истории и socket-событий.
 */
class Bitrix24SyncPayment extends Command
{
    protected $signature = 'bitrix24:sync-payment
        {tenant=logistopt6 : id портала (например logistopt6) или имя БД admin_logistopt6}
        {--sleep=250 : пауза между задачами в мс (защита от лимитов Bitrix24)}
        {--dry-run : только показать, что будет сделано, без сохранения}';

    protected $description = 'Синхронизировать «Оплата, руб» из Bitrix24 для всех текущих задач логистики';

    public function handle(): int
    {
        $target = $this->argument('tenant');
        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден (id без префикса '" . config('tenancy.database.prefix') . "', напр. logistopt6)");
            return self::FAILURE;
        }

        $sleepMs = max(0, (int) $this->option('sleep'));
        $dryRun = (bool) $this->option('dry-run');

        $tenant->run(function () use ($sleepMs, $dryRun) {
            $config = Config::first();
            if (!$config || !$config->webhook) {
                $this->error('Bitrix24 webhook не настроен (bitrix24_config пуст) — синхронизация невозможна.');
                return;
            }
            $base = $config->webhook;

            $tasks = Task::query()->orderBy('id')->get(['id', 'name', 'crm_link', 'payment', 'payment_type']);
            $this->info("Задач логистики: {$tasks->count()}");

            $updated = 0;
            $skipped = 0;
            $bar = $this->output->createProgressBar($tasks->count());
            $bar->start();

            foreach ($tasks as $task) {
                $bar->advance();

                $dealId = $this->extractDealId($task);
                if (!$dealId) {
                    $skipped++;
                    continue;
                }

                $invoices = Bitrix24Controller::fetchDealInvoiceNumbers($base, $dealId);

                $opportunity = 0;
                if (count($invoices) === 0) {
                    try {
                        $resp = Http::post($base . 'crm.deal.get', ['id' => $dealId])->collect();
                        $opportunity = $resp['result']['OPPORTUNITY'] ?? 0;
                    } catch (\Throwable $e) {
                        // оставляем 0 — выйдет «Нал: 0 р.»
                    }
                }

                $pay = Bitrix24Controller::buildPaymentFields($invoices, $opportunity);

                if (!$dryRun) {
                    $task->payment = $pay['payment'];
                    $task->payment_type = $pay['payment_type'];
                    $task->saveQuietly();
                }
                $updated++;

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            $bar->finish();
            $this->newLine(2);
            $this->info(($dryRun ? '[dry-run] ' : '') . "Обновлено: {$updated}, пропущено (нет id сделки): {$skipped}");
        });

        return self::SUCCESS;
    }

    /**
     * Извлекает ID сделки Bitrix24 из задачи логистики: из crm_link, из
     * name (JSON {value, external_link}) либо из легаси-названия «Сделка №ID».
     */
    private function extractDealId(Task $task): ?int
    {
        if (!empty($task->crm_link) && preg_match('#/deal/details/(\d+)#', $task->crm_link, $m)) {
            return (int) $m[1];
        }

        $name = $task->name;
        if (is_string($name) && $name !== '') {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                if (isset($decoded['value']) && is_numeric($decoded['value'])) {
                    return (int) $decoded['value'];
                }
                if (!empty($decoded['external_link']) && preg_match('#/deal/details/(\d+)#', $decoded['external_link'], $m)) {
                    return (int) $m[1];
                }
            }
            if (preg_match('#Сделка\s*№\s*(\d+)#u', $name, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }
}
