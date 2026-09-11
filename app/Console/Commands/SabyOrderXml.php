<?php

namespace App\Console\Commands;

use App\Models\SabyOrder;
use App\Models\Tenant;
use App\Services\Saby\SabyClient;
use App\Services\Saby\SabyOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SabyOrderXml extends Command
{
    protected $signature = 'saby:order-xml {tenant} {order : id заказа, номер (cmps-2456) или id задачи} {--generate : дополнительно перегенерировать титул из сохранённого payload}';

    protected $description = 'Показать состояние и XML титула заказа на перевозку в Saby (диагностика)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }

        return (int) $tenant->run(function () {
            $key = (string) $this->argument('order');
            $order = SabyOrder::where('number', $key)->orderByDesc('id')->first()
                ?: (is_numeric($key) ? (SabyOrder::find((int) $key) ?: SabyOrder::where('task_id', (int) $key)->orderByDesc('id')->first()) : null)
                ?: SabyOrder::where('number', 'like', '%' . $key)->orderByDesc('id')->first();
            if (!$order) {
                $this->error('Заказ не найден');
                return self::FAILURE;
            }

            $client = SabyClient::make();
            if (!$client) {
                $this->error('Saby не настроен');
                return self::FAILURE;
            }

            $this->line("order id={$order->id} task={$order->task_id} number={$order->number} doc={$order->doc_id} state={$order->state_code}/{$order->state_name}");
            $this->line('payload Маршрут.Пункт: ' . json_encode($order->payload['Маршрут']['Пункт'] ?? null, JSON_UNESCAPED_UNICODE));

            $document = $client->call('СБИС.ПрочитатьДокумент', [
                'Документ' => ['Идентификатор' => $order->doc_id, 'ДопПоля' => 'Подстановки'],
            ]);
            $state = $document['Состояние'] ?? [];
            $this->line('Saby state: ' . json_encode($state, JSON_UNESCAPED_UNICODE));
            $this->line('Saby Контрагент: ' . json_encode($document['Контрагент'] ?? null, JSON_UNESCAPED_UNICODE));

            $sid = $client->sessionId();
            foreach ($document['Вложение'] ?? [] as $i => $attachment) {
                $link = $attachment['Файл']['Ссылка'] ?? null;
                $this->line("--- Вложение[$i] id=" . ($attachment['Идентификатор'] ?? '') . ' name=' . ($attachment['Файл']['Имя'] ?? '') . ' errors=' . ($attachment['КоличествоОшибок'] ?? '') . " link=$link");
                if (!$link) {
                    continue;
                }
                $response = Http::withHeaders(['X-SBISSessionID' => $sid])->timeout(30)->get($link);
                $this->printXml($response->body());
            }

            if ($this->option('generate')) {
                $config = $client->config();
                $generated = $client->call('СБИС.СгенерироватьВложение', [
                    'Документ' => [
                        'Вложение' => [[
                            'Тип' => 'ЗаказЗаявка',
                            'Подтип' => SabyOrderService::ORDER_KND,
                            'ВерсияФормата' => (string) $config->param('order_format_version', SabyOrderService::FORMAT_VERSION),
                            'ПодверсияФормата' => '',
                            'Подстановка' => $order->payload,
                        ]],
                    ],
                ]);
                $file = $generated['Вложение'][0]['Файл'] ?? [];
                $this->line('--- Перегенерированный титул из payload (документ не создаётся), errors=' . ($generated['Вложение'][0]['КоличествоОшибок'] ?? ''));
                $this->printXml(base64_decode((string) ($file['ДвоичныеДанные'] ?? ''), true) ?: '');
            }

            return self::SUCCESS;
        });
    }

    private function printXml(string $raw): void
    {
        if ($raw === '') {
            $this->line('(пусто)');
            return;
        }
        $xml = $raw;
        if (stripos(substr($raw, 0, 100), 'windows-1251') !== false) {
            $xml = @iconv('windows-1251', 'utf-8//IGNORE', $raw) ?: $raw;
        }
        $this->line($xml);
    }
}
