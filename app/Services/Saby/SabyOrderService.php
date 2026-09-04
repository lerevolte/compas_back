<?php

namespace App\Services\Saby;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Route;
use App\Models\SabyOrder;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SabyOrderService extends SabyWaybillService
{
    public const ORDER_KND = '1110361';
    public const ORDER_DOC_TYPE = 'TransportOrder';
    public const ORDER_REGULATION = 'Заказ на перевозку';
    public const WAYBILL_LOOKBACK_DAYS = 45;

    public const ORDER_STATES = [
        '0' => 'Черновик — отправьте заказ перевозчику в Saby',
        '4' => 'Отправлен, ожидается утверждение перевозчиком',
        '7' => 'Утверждён перевозчиком',
        '9' => 'Отклонён перевозчиком',
    ];

    public static function make(): ?self
    {
        $client = SabyClient::make();

        return $client ? new self($client) : null;
    }

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('saby_orders');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function createOrder(Task $task, ?Task $loadingTask = null, ?string $massMethod = null): SabyOrder
    {
        $substitutions = $this->buildOrder($task, $loadingTask, $massMethod);
        $config = $this->client->config();

        $generated = $this->client->call('СБИС.СгенерироватьВложение', [
            'Документ' => [
                'Вложение' => [[
                    'Тип' => 'ЗаказЗаявка',
                    'Подтип' => self::ORDER_KND,
                    'ВерсияФормата' => (string) $config->param('order_format_version', self::FORMAT_VERSION),
                    'ПодверсияФормата' => '',
                    'Подстановка' => $substitutions,
                ]],
            ],
        ]);

        $file = $generated['Вложение'][0]['Файл'] ?? null;
        if (!isset($file['ДвоичныеДанные'])) {
            throw new SabyException('Saby не вернул сформированный файл заказа на перевозку');
        }
        $file['ДвоичныеДанные'] = $this->patchCargoDistribution($file['ДвоичныеДанные']);

        $written = $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => [
            'Тип' => self::ORDER_DOC_TYPE,
            'Регламент' => ['Название' => self::ORDER_REGULATION],
            'НашаОрганизация' => $this->ourOrganization(),
            'Вложение' => [
                ['Файл' => [
                    'ДвоичныеДанные' => $file['ДвоичныеДанные'],
                    'Имя' => $file['Имя'] ?? null,
                ]],
            ],
        ]]);

        $attachment = $written['Вложение'][0] ?? [];
        $state = $written['Состояние'] ?? [];

        $order = SabyOrder::create([
            'task_id' => $task->id,
            'route_id' => $task->route_id,
            'loading_task_id' => $loadingTask?->id,
            'mass_method' => $massMethod,
            'doc_id' => $written['Идентификатор'] ?? null,
            'attachment_id' => $attachment['Идентификатор'] ?? null,
            'complete_id' => $this->findKey($written, 'ИдКомплекта'),
            'number' => $written['Номер'] ?? ($substitutions['Документ']['Номер'] ?? null),
            'date' => $written['Дата'] ?? ($substitutions['Документ']['Дата'] ?? now()->format('d.m.Y')),
            'state_code' => isset($state['Код']) ? (string) $state['Код'] : '0',
            'state_name' => $state['Название'] ?? null,
            'state_note' => $state['Описание'] ?? null,
            'pdf_url' => $written['СсылкаНаPDF'] ?? ($attachment['СсылкаНаPDF'] ?? null),
            'cabinet_url' => $written['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? null),
            'archive_url' => $written['СсылкаНаАрхив'] ?? null,
            'payload' => $substitutions,
            'error' => null,
            'user_id' => auth()->id(),
            'synced_at' => now(),
        ]);

        $this->log('info', 'order created', [
            'task_id' => $task->id,
            'doc_id' => $order->doc_id,
            'number' => $order->number,
            'flc_errors' => $attachment['КоличествоОшибок'] ?? null,
        ]);

        return $order;
    }

    protected function patchCargoDistribution(string $base64): string
    {
        $xml = base64_decode($base64, true);
        if ($xml === false || $xml === '') {
            return $base64;
        }
        foreach (['windows-1251', 'utf-8'] as $encoding) {
            $attr = @iconv('utf-8', $encoding, 'РаспрГр');
            $needle = @iconv('utf-8', $encoding, '<ОпГруз ');
            $replace = @iconv('utf-8', $encoding, '<ОпГруз РаспрГр="1" ');
            if ($attr === false || $needle === false || $replace === false) {
                continue;
            }
            if (strpos($xml, $attr) !== false) {
                return $base64;
            }
            if (strpos($xml, $needle) !== false) {
                return base64_encode(str_replace($needle, $replace, $xml));
            }
        }

        return $base64;
    }

    public function deleteOrder(SabyOrder $order): void
    {
        if ($order->doc_id) {
            try {
                $this->client->call('СБИС.УдалитьДокумент', ['Документ' => ['Идентификатор' => $order->doc_id]]);
            } catch (\Throwable $e) {
                $this->log('warning', 'order delete in saby failed', ['doc_id' => $order->doc_id, 'error' => $e->getMessage()]);
            }
        }
        $this->log('info', 'order deleted', ['task_id' => $order->task_id, 'doc_id' => $order->doc_id]);
        $order->delete();
    }

    public function refreshOrder(SabyOrder $order): SabyOrder
    {
        if ($order->doc_id) {
            $document = $this->client->call('СБИС.ПрочитатьДокумент', [
                'Документ' => ['Идентификатор' => $order->doc_id, 'ДопПоля' => 'Подстановки'],
            ]);
            $this->applyOrderDocument($order, $document);
        }

        if ($order->waybill_doc_id) {
            $document = $this->client->call('СБИС.ПрочитатьДокумент', [
                'Документ' => ['Идентификатор' => $order->waybill_doc_id, 'ДопПоля' => 'ЭПД'],
            ]);
            $this->applyWaybillDocument($order, $document);
        } else {
            $this->findWaybillFor($order);
        }

        $order->synced_at = now();
        $order->save();

        return $order;
    }

    public function validateOrder(Task $task): array
    {
        try {
            $this->buildOrder($task);
        } catch (SabyValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    public function buildOrder(Task $task, ?Task $loadingTask = null, ?string $massMethod = null): array
    {
        $errors = [];
        $route = $task->route_id ? Route::find($task->route_id) : null;

        $shipper = $this->companyOf($task, 'shipment_company_id');
        if (!$shipper) {
            $errors[] = 'В задаче не заполнено поле «Компания отгрузки» (грузоотправитель)';
        } elseif ($this->inn($shipper) === '') {
            $errors[] = 'У компании «' . $shipper->name . '» не заполнен ИНН';
        }

        $carrier = $route ? $this->companyOf($route, 'company_id') : null;
        if (!$route) {
            $errors[] = 'Задача не привязана к маршруту';
        } elseif (!$carrier) {
            $errors[] = 'У маршрута задачи не заполнено поле «Компания» (перевозчик)';
        } elseif ($this->inn($carrier) === '') {
            $errors[] = 'У перевозчика «' . $carrier->name . '» не заполнен ИНН';
        }

        $receiver = $this->companyOf($task, 'company_id');
        $receiverContact = $this->contactOf($task);

        $positions = $this->orderCargo($task, $massMethod);
        if (!count($positions)) {
            $errors[] = 'В задаче не заполнено поле «Состав»';
        }

        $deliveryAddress = $this->taskAddress($task, $receiver);
        if ($deliveryAddress === '') {
            $errors[] = 'В задаче не заполнен адрес доставки';
        }

        $loadingAddress = '';
        if ($loadingTask) {
            $loadingAddress = $this->addressText($loadingTask->address);
            if ($this->isCoordinates($loadingAddress)) {
                $loadingAddress = $this->resolveCoordinates($loadingAddress) ?: $loadingAddress;
            }
        }
        if ($loadingAddress === '' && $shipper) {
            $loadingAddress = $this->companyAddress($shipper, $this->requisite($shipper));
        }
        if ($loadingAddress === '') {
            $errors[] = 'Не удалось определить адрес погрузки: выберите точку погрузки или заполните адрес компании отгрузки';
        }

        if (count($errors)) {
            throw new SabyValidationException($errors);
        }

        $date = $this->formatDate($task->delivery_date) ?: ($route ? $this->formatDate($route->date) : '') ?: now()->format('d.m.Y');
        $loadingAt = $loadingTask ? $this->loadingDateTime($loadingTask, $route) : $this->loadingDateTime($task, $route);
        $deliveryAt = $this->loadingDateTime($task, $route);

        $loadingPoint = [
            'КодСтраны' => '643',
            'АдресТекст' => $loadingAddress,
            'Операция' => ['Тип' => 'Погрузка', 'ДатаВремя' => $loadingAt],
            'Организация' => array_filter(['Название' => (string) $shipper->name, 'ИНН' => $this->inn($shipper)]),
        ];
        $unloadingPoint = [
            'КодСтраны' => '643',
            'АдресТекст' => $deliveryAddress,
            'Операция' => ['Тип' => 'Выгрузка', 'ДатаВремя' => $deliveryAt],
        ];
        if ($receiver) {
            $unloadingPoint['Организация'] = array_filter(['Название' => (string) $receiver->name, 'ИНН' => $this->inn($receiver)]);
        } elseif ($receiverContact) {
            $unloadingPoint['Организация'] = array_filter(['Название' => $this->contactName($receiverContact), 'ИНН' => $this->contactInn($receiverContact)]);
        }

        $substitutions = [
            'Документ' => [
                'Номер' => $this->nextNumber($task),
                'Дата' => $date,
            ],
            'Грузоотправитель' => $this->orderParty($shipper),
            'Грузоперевозчик' => $this->orderParty($carrier),
            'Маршрут' => [
                'Отправление' => [
                    'КодСтраны' => '643',
                    'АдресТекст' => $loadingAddress,
                    'ПодачаТС' => ['ДатаВремя' => $loadingAt],
                ],
                'Пункт' => [$loadingPoint, $unloadingPoint],
                'КонечныйПункт' => ['Название' => $deliveryAddress],
            ],
            'Груз' => ['Позиция' => $positions],
            'Файл' => ['Составитель' => ['Наименование' => (string) $shipper->name]],
        ];

        $vehicle = $route ? $this->orderVehicle($route) : [];
        if (count($vehicle)) {
            $substitutions['ПараметрыТС'] = $vehicle;
        }

        return $substitutions;
    }

    protected function orderParty(Company $company): array
    {
        $inn = $this->inn($company);
        $requisite = $this->requisite($company);
        if (strlen($inn) > 10) {
            $party = ['Реквизиты' => ['ИП' => array_filter(['ИНН' => $inn] + $this->splitName($company->name))]];
        } else {
            $party = ['Реквизиты' => ['ЮЛ' => array_filter([
                'Наименование' => (string) $company->name,
                'ИНН' => $inn,
                'КПП' => $this->kpp($company),
            ])]];
        }

        $contacts = [];
        $phone = $this->phone($company);
        if ($phone !== '') {
            $contacts['Телефон'] = [['Значение' => $phone]];
        }
        $email = trim((string) $this->attr($company, 'email'));
        if ($email !== '') {
            $contacts['ЭлектроннаяПочта'] = [['Значение' => $email]];
        }
        if (count($contacts)) {
            $party['Контакты'] = $contacts;
        }

        $address = $this->companyAddress($company, $requisite);
        if ($address !== '') {
            $party['Адрес'] = ['АдресТекст' => $address, 'КодСтраны' => '643'];
        }

        return $party;
    }

    protected function orderVehicle(Route $route): array
    {
        if (!$route->car_id) {
            return [];
        }
        $car = \App\Models\Car::find($route->car_id);
        if (!$car) {
            return [];
        }
        $params = [];
        $type = $this->fieldOptionLabel('cars', 'vehicle_type', $this->attr($car, 'vehicle_type'));
        if ($type !== '') {
            $params['Тип'] = $type;
        }
        $capacity = $this->number($car->weight_max);
        if ($capacity > 0) {
            $params['Грузоподъемность'] = $this->format($capacity / 1000, 3);
        }
        $volume = $this->number($car->volume_max);
        if ($volume > 0) {
            $params['Вместимость'] = $this->format($volume / 1000, 3);
        }

        return $params;
    }

    protected function orderCargo(Task $task, ?string $massMethod): array
    {
        $config = $this->client->config();
        $defaultCondition = (string) $config->param('cargo_condition', 'Хорошее');
        $defaultTare = (string) $config->param('tare_type', '');

        $items = [];
        foreach ($this->products($task) as $product) {
            $name = trim((string) ($product['name'] ?? ''));
            if ($name === '' || $this->isService($product['id'] ?? null)) {
                continue;
            }
            $key = ($product['id'] ?? null) ? 'id:' . $product['id'] : 'name:' . mb_strtolower($name);
            if (!isset($items[$key])) {
                $items[$key] = ['id' => $product['id'] ?? null, 'name' => $name, 'count' => 0.0, 'weight' => 0.0, 'volume' => 0.0];
            }
            $count = $this->number($product['count'] ?? 0);
            $items[$key]['count'] += $count;
            $items[$key]['weight'] += $this->number($product['weight'] ?? 0) * ($count ?: 1);
            $items[$key]['volume'] += $this->number($product['volume'] ?? 0) * ($count ?: 1);
        }

        $positions = [];
        foreach ($items as $item) {
            $places = $this->format($item['count'] ?: 1);
            $position = [
                'Наименование' => $item['name'],
                'Состояние' => $defaultCondition,
                'ПогрузкаВодителем' => '0',
                'Параметры' => ['КоличествоМест' => $places],
                'ПунктыПогрузкиВыгрузки' => [[
                    'КоличествоГрузовыхМест' => $places,
                    'ПорядковыйНомерПунктаПогрузки' => 1,
                    'ПорядковыйНомерПунктаВыгрузки' => 2,
                ]],
                'Делимость' => '0',
                'ГруженностьКонтейнера' => '0',
            ];
            $tare = $this->productAttr($item['id'], 'tare_type', $defaultTare);
            if ($tare !== '') {
                $position['ТараКод'] = $tare;
            }
            if ($item['weight'] > 0) {
                $position['Параметры']['Масса'] = ['Брутто' => $this->format($item['weight'])];
            }
            if ($item['volume'] > 0) {
                $position['Параметры']['Объем'] = $this->format($item['volume'] / 1000, 3);
            }
            if ($massMethod !== null && isset(self::MASS_METHODS[$massMethod])) {
                $position['МетодОпределенияМассы'] = str_pad($massMethod, 2, '0', STR_PAD_LEFT);
            }
            $positions[] = $position;
        }

        return $positions;
    }

    public function syncAll(): array
    {
        $stat = ['orders' => 0, 'waybills' => 0, 'linked' => 0];
        if (!self::tableReady() || !SabyOrder::where('doc_id', '!=', '')->exists()) {
            return $stat;
        }

        foreach ($this->changes(self::ORDER_DOC_TYPE, 'saby_orders_cursor', 'Подстановки') as $document) {
            $order = SabyOrder::where('doc_id', $document['Идентификатор'] ?? '')->first();
            if (!$order) {
                continue;
            }
            $this->applyOrderDocument($order, $document);
            $order->synced_at = now();
            $order->save();
            $stat['orders']++;
        }

        if (SabyOrder::whereNull('waybill_doc_id')->exists() || SabyOrder::whereNotNull('waybill_doc_id')->exists()) {
            foreach ($this->changes(self::DOC_TYPE, 'saby_order_waybills_cursor', 'Подстановки,ЭПД') as $document) {
                $order = $this->matchOrder($document);
                if (!$order) {
                    continue;
                }
                $wasLinked = (bool) $order->waybill_doc_id;
                $this->applyWaybillDocument($order, $document);
                $order->synced_at = now();
                $order->save();
                $stat['waybills']++;
                if (!$wasLinked) {
                    $stat['linked']++;
                }
            }
        }

        return $stat;
    }

    protected function changes(string $type, string $cursorKey, string $extra): array
    {
        $read = fn () => DB::table('settings')->where('type', $cursorKey)->value('value');
        $cursor = json_decode((string) $read(), true) ?: [];
        $since = (string) ($cursor['since'] ?? now()->subDays(self::WAYBILL_LOOKBACK_DAYS)->format('d.m.Y H.i.s'));
        $started = now()->subMinute()->format('d.m.Y H.i.s');

        $filter = [
            'Тип' => $type,
            'ДатаВремяС' => $since,
            'ПолныйСертификатЭП' => 'Нет',
            'ДопПоля' => $extra,
            'Навигация' => ['РазмерСтраницы' => '50'],
        ];
        if (!empty($cursor['event_id'])) {
            $filter['ИдентификаторСобытия'] = $cursor['event_id'];
            if (!empty($cursor['doc_id'])) {
                $filter['ИдентификаторДокумента'] = $cursor['doc_id'];
            }
        }

        $documents = [];
        $lastEvent = null;
        $lastDoc = null;
        $guard = 0;
        do {
            $result = $this->client->call('СБИС.СписокИзменений', ['Фильтр' => $filter]);
            $page = $result['Документ'] ?? [];
            foreach ($page as $document) {
                $documents[] = $document;
                $events = $document['Событие'] ?? [];
                $last = is_array($events) && count($events) ? end($events) : null;
                if (is_array($last) && !empty($last['Идентификатор'])) {
                    $lastEvent = $last['Идентификатор'];
                    $lastDoc = $document['Идентификатор'] ?? null;
                }
            }
            $more = (($result['Навигация']['ЕстьЕще'] ?? 'Нет') === 'Да') && $lastEvent;
            if ($more) {
                $filter['ИдентификаторСобытия'] = $lastEvent;
                $filter['ИдентификаторДокумента'] = $lastDoc;
            }
            $guard++;
        } while ($more && $guard < 40);

        DB::table('settings')->updateOrInsert(
            ['type' => $cursorKey, 'entity' => null, 'user_id' => null],
            ['key' => $cursorKey, 'value' => json_encode([
                'since' => $lastEvent ? $since : $started,
                'event_id' => $lastEvent,
                'doc_id' => $lastDoc,
            ])]
        );

        return $documents;
    }

    protected function applyOrderDocument(SabyOrder $order, array $document): void
    {
        $state = $document['Состояние'] ?? [];
        $attachment = $document['Вложение'][0] ?? [];
        if (isset($state['Код'])) {
            $order->state_code = (string) $state['Код'];
        }
        $order->state_name = $state['Название'] ?? $order->state_name;
        $order->state_note = $state['Описание'] ?? $order->state_note;
        $order->pdf_url = $document['СсылкаНаPDF'] ?? ($attachment['СсылкаНаPDF'] ?? $order->pdf_url);
        $order->cabinet_url = $document['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? $order->cabinet_url);
        $order->archive_url = $document['СсылкаНаАрхив'] ?? $order->archive_url;
        if (!empty($document['Номер'])) {
            $order->number = $document['Номер'];
        }
        $completeId = $this->findKey($document, 'ИдКомплекта');
        if ($completeId) {
            $order->complete_id = $completeId;
        }
        $events = $document['Событие'] ?? [];
        if (is_array($events) && count($events)) {
            $last = end($events);
            $order->last_event = trim((string) (($last['Название'] ?? '') . ' ' . ($last['Комментарий'] ?? '')));
        }
    }

    protected function applyWaybillDocument(SabyOrder $order, array $document): void
    {
        $attachment = $document['Вложение'][0] ?? [];
        $state = $document['Состояние'] ?? [];
        $order->waybill_doc_id = $document['Идентификатор'] ?? $order->waybill_doc_id;
        $order->waybill_number = $document['Номер'] ?? ($this->findKey($document, 'НомерТрН') ?: $order->waybill_number);
        $order->waybill_date = $document['Дата'] ?? $order->waybill_date;
        $order->waybill_state = $state['Название'] ?? $order->waybill_state;
        $stages = [];
        foreach ((array) ($document['ТекущиеЭтапы'] ?? []) as $stage) {
            $name = is_array($stage) ? ($stage['Название'] ?? '') : (string) $stage;
            if ($name !== '') {
                $stages[] = $name;
            }
        }
        if (count($stages)) {
            $order->waybill_stage = implode(', ', $stages);
        }
        $order->waybill_pdf_url = $document['СсылкаНаPDF'] ?? ($attachment['СсылкаНаPDF'] ?? $order->waybill_pdf_url);
        $order->waybill_cabinet_url = $document['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? $order->waybill_cabinet_url);
        $order->waybill_archive_url = $document['СсылкаНаАрхив'] ?? $order->waybill_archive_url;
        $qr = trim((string) ($document['QRLink'] ?? ''));
        if ($qr !== '') {
            $order->waybill_qr_url = $qr;
        }
        $order->waybill_checked_at = now();
    }

    protected function matchOrder(array $document): ?SabyOrder
    {
        $docId = (string) ($document['Идентификатор'] ?? '');
        if ($docId !== '') {
            $known = SabyOrder::where('waybill_doc_id', $docId)->first();
            if ($known) {
                return $known;
            }
        }

        $completeId = $this->findKey($document, 'ИдКомплекта');
        if ($completeId) {
            $byComplete = SabyOrder::where('complete_id', $completeId)->whereNull('waybill_doc_id')->first();
            if ($byComplete) {
                return $byComplete;
            }
        }

        $number = trim((string) $this->findKey($document, 'НомЗак'));
        if ($number === '') {
            return null;
        }
        $query = SabyOrder::where('number', $number)->whereNull('waybill_doc_id');
        $date = trim((string) $this->findKey($document, 'ДатаЗак'));
        if ($date !== '') {
            $query->where('date', $date);
        }

        return $query->orderByDesc('id')->first();
    }

    protected function findWaybillFor(SabyOrder $order): void
    {
        $result = $this->client->call('СБИС.СписокИзменений', ['Фильтр' => [
            'Тип' => self::DOC_TYPE,
            'ДатаВремяС' => now()->subDays(self::WAYBILL_LOOKBACK_DAYS)->format('d.m.Y H.i.s'),
            'ПолныйСертификатЭП' => 'Нет',
            'ДопПоля' => 'Подстановки,ЭПД',
            'Навигация' => ['РазмерСтраницы' => '50'],
        ]]);
        foreach (($result['Документ'] ?? []) as $document) {
            $completeId = $this->findKey($document, 'ИдКомплекта');
            $number = trim((string) $this->findKey($document, 'НомЗак'));
            if (($order->complete_id && $completeId === $order->complete_id) || ($number !== '' && $number === (string) $order->number)) {
                $this->applyWaybillDocument($order, $document);
                return;
            }
        }
    }

    protected function findKey($data, string $key)
    {
        if (!is_array($data)) {
            return null;
        }
        if (array_key_exists($key, $data) && !is_array($data[$key])) {
            $value = trim((string) $data[$key]);
            if ($value !== '') {
                return $value;
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findKey($value, $key);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
