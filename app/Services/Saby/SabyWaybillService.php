<?php

namespace App\Services\Saby;

use App\Models\Car;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Requisite;
use App\Models\Route;
use App\Models\Task;
use App\Models\SabyWaybill;
use App\Services\Dadata;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SabyWaybillService
{
    public const FORMAT_VERSION = '5.01';
    public const SHIPPER_TITLE_KND = '1110339';
    public const DOC_TYPE = 'ConsignmentNote';
    public const REGULATION = 'Транспортная накладная';
    public const GEOCODE_RADIUS = 1000;
    public const MASS_METHODS = [
        '1' => 'Взвешивание по общей массе',
        '2' => 'Взвешивание поосно',
        '3' => 'Расчетная масса',
    ];

    private SabyClient $client;
    private array $geocodeCache = [];

    public function __construct(SabyClient $client)
    {
        $this->client = $client;
    }

    public static function make(): ?self
    {
        $client = SabyClient::make();

        return $client ? new self($client) : null;
    }

    public static function ready(): bool
    {
        return SabyClient::ready();
    }

    public function create(Task $task, ?Task $loadingTask = null, ?string $massMethod = null): SabyWaybill
    {
        $document = $this->buildDocument($task, $loadingTask, $massMethod);
        $config = $this->client->config();
        $route = $task->route_id ? Route::find($task->route_id) : null;
        $shipper = $this->companyOf($task, 'shipment_company_id');
        $carrier = $route ? $this->companyOf($route, 'company_id') : null;
        $receiver = $this->companyOf($task, 'company_id');
        $receiverContact = $this->contactOf($task);

        $number = $this->nextNumber($task);
        $document['СодИнфГО']['НомерТрН'] = $number;

        $generated = $this->client->call('СБИС.СгенерироватьВложение', [
            'Документ' => [
                'Вложение' => [
                    'Тип' => 'ЭТрН',
                    'Подтип' => self::SHIPPER_TITLE_KND,
                    'ВерсияФормата' => (string) $config->param('format_version', self::FORMAT_VERSION),
                    'Подстановка' => [
                        self::SHIPPER_TITLE_KND => ['Файл' => ['Документ' => $document]],
                    ],
                ],
            ],
        ]);

        $file = $generated['Вложение'][0]['Файл'] ?? null;
        if (!isset($file['ДвоичныеДанные'])) {
            throw new SabyException('Saby не вернул сформированный файл накладной');
        }

        $payload = [
            'Тип' => self::DOC_TYPE,
            'Регламент' => ['Название' => self::REGULATION],
            'НашаОрганизация' => $this->ourOrganization(),
            'Грузоотправитель' => $this->counterparty($shipper),
            'ТранспортнаяКомпания' => $this->counterparty($carrier ?: $shipper),
            'Вложение' => [
                ['Файл' => [
                    'ДвоичныеДанные' => $file['ДвоичныеДанные'],
                    'Имя' => $file['Имя'] ?? null,
                ]],
            ],
        ];
        $receiverParty = $receiver ? $this->counterparty($receiver) : $this->contactCounterparty($receiverContact);
        if (count($receiverParty)) {
            $payload['Грузополучатель'] = $receiverParty;
        }

        $written = $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => $payload]);

        $attachment = $written['Вложение'][0] ?? [];

        $waybill = SabyWaybill::create([
            'task_id' => $task->id,
            'loading_task_id' => $loadingTask?->id,
            'mass_method' => Schema::hasColumn('saby_waybills', 'mass_method') ? $massMethod : null,
            'route_id' => $task->route_id,
            'doc_id' => $written['Идентификатор'] ?? null,
            'attachment_id' => $attachment['Идентификатор'] ?? null,
            'number' => $written['Номер'] ?? $number,
            'date' => $written['Дата'] ?? now()->format('d.m.Y'),
            'status' => $written['Состояние']['Название'] ?? null,
            'pdf_url' => $written['СсылкаНаPDF'] ?? ($attachment['СсылкаНаPDF'] ?? null),
            'cabinet_url' => $written['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? null),
            'archive_url' => $written['СсылкаНаАрхив'] ?? null,
            'payload' => $document,
            'error' => null,
            'user_id' => auth()->id(),
        ]);

        $this->log('info', 'waybill created', [
            'task_id' => $task->id,
            'doc_id' => $waybill->doc_id,
            'number' => $waybill->number,
            'flc_errors' => $attachment['КоличествоОшибок'] ?? null,
        ]);

        return $waybill;
    }

    public function delete(SabyWaybill $waybill): void
    {
        if ($waybill->doc_id) {
            try {
                $this->client->call('СБИС.УдалитьДокумент', [
                    'Документ' => ['Идентификатор' => $waybill->doc_id],
                ]);
            } catch (\Throwable $e) {
                $this->log('warning', 'waybill delete in saby failed', [
                    'doc_id' => $waybill->doc_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->log('info', 'waybill deleted', ['task_id' => $waybill->task_id, 'doc_id' => $waybill->doc_id]);
        $waybill->delete();
    }

    public function refresh(SabyWaybill $waybill): SabyWaybill
    {
        if (!$waybill->doc_id) {
            return $waybill;
        }

        $document = $this->client->call('СБИС.ПрочитатьДокумент', [
            'Документ' => ['Идентификатор' => $waybill->doc_id, 'ДопПоля' => 'ЭПД'],
        ]);

        $attachment = $document['Вложение'][0] ?? [];
        $qr = trim((string) ($document['QRLink'] ?? ''));

        $waybill->update([
            'status' => $document['Состояние']['Название'] ?? $waybill->status,
            'pdf_url' => $document['СсылкаНаPDF'] ?? $waybill->pdf_url,
            'cabinet_url' => $document['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? $waybill->cabinet_url),
            'archive_url' => $document['СсылкаНаАрхив'] ?? $waybill->archive_url,
            'qr_url' => $qr !== '' ? $qr : $waybill->qr_url,
        ]);

        return $waybill;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        try {
            Log::channel('saby')->{$level}($message, $context);
        } catch (\Throwable $e) {
        }
    }

    public function buildDocument(Task $task, ?Task $loadingTask = null, ?string $massMethod = null): array
    {
        $errors = [];

        $route = $task->route_id ? Route::find($task->route_id) : null;

        $shipper = $this->companyOf($task, 'shipment_company_id');
        if (!$shipper) {
            $errors[] = 'В задаче не заполнено поле «Компания отгрузки» (грузоотправитель)';
        }

        $carrier = $route ? $this->companyOf($route, 'company_id') : null;
        if (!$carrier) {
            $errors[] = 'У маршрута задачи не заполнено поле «Компания» (перевозчик)';
        }
        if ($carrier && $this->inn($carrier) === '') {
            $errors[] = 'У перевозчика «' . $carrier->name . '» не заполнен ИНН';
        }

        $receiver = $this->companyOf($task, 'company_id');
        $receiverContact = $this->contactOf($task);
        if (!$receiver && !$receiverContact) {
            $errors[] = 'В задаче не заполнено ни поле «Компания», ни поле «Контакт» (грузополучатель)';
        }

        $cargo = $this->cargo([$task]);
        if (!count($cargo)) {
            $errors[] = 'В задаче не заполнено поле «Состав»';
        }

        $deliveryAddress = $this->taskAddress($task, $receiver);
        if ($deliveryAddress === '') {
            $errors[] = 'В задаче не заполнен адрес доставки';
        }

        if ($shipper && $this->inn($shipper) === '') {
            $errors[] = 'У компании «' . $shipper->name . '» не заполнен ИНН';
        }
        if ($receiver && $this->inn($receiver) === '') {
            $errors[] = 'У получателя «' . $receiver->name . '» не заполнен ИНН';
        }

        if (count($errors)) {
            throw new SabyValidationException($errors);
        }

        $document = [
            'СодИнфГО' => [
                'ДатаТрН' => $this->formatDate($task->delivery_date) ?: ($route ? $this->formatDate($route->date) : '') ?: now()->format('d.m.Y'),
                'НомерТрН' => (string) $task->id,
                'СвГО' => ['РекИдентГО' => $this->party($shipper)],
                'СвГП' => [
                    'РекИдентГП' => $receiver ? $this->party($receiver, $receiverContact) : $this->contactParty($receiverContact),
                    'АдресДостГр' => ['АдресИнф' => ['АдрТекст' => $deliveryAddress, 'КодСтр' => '643']],
                ],
                'СвГруз' => ['ОпГруз' => $cargo],
            ],
        ];

        $document['СодИнфГО']['НомЗак'] = (string) $task->id;
        $document['СодИнфГО']['ДатаЗак'] = $document['СодИнфГО']['ДатаТрН'];

        $document['СодИнфГО']['СвПер'] = $this->party($carrier);

        $loading = [];
        $gross = $this->number($this->attr($task, 'weight'));
        if ($gross > 0) {
            $loading['МасБрутОтгр'] = $this->format($gross);
        }
        $places = 0.0;
        foreach ($cargo as $entry) {
            $places += (float) ($entry['КолМестГр'] ?? 0);
        }
        if ($places > 0) {
            $loading['КолМестПрием'] = $this->format($places);
        }
        if ($loadingTask) {
            $loadingAt = $this->loadingDateTime($loadingTask, $route);
            if ($loadingAt !== '') {
                $loading['ЗаявПогр'] = $loadingAt;
            }
        }
        if ($massMethod !== null && isset(self::MASS_METHODS[$massMethod])) {
            $loading['МетОпрМасс'] = $massMethod;
        }

        if (count($loading)) {
            $loadingAddress = '';
            if ($loadingTask) {
                $loadingAddress = $this->addressText($loadingTask->address);
                if ($this->isCoordinates($loadingAddress)) {
                    $loadingAddress = $this->resolveCoordinates($loadingAddress) ?: $loadingAddress;
                }
            }
            if ($loadingAddress === '') {
                $loadingAddress = $this->companyAddress($shipper, $this->requisite($shipper));
            }
            if ($loadingAddress !== '') {
                $loading['ФАдресПогр'] = ['АдресИнф' => ['АдрТекст' => $loadingAddress, 'КодСтр' => '643']];
            }
            $document['СодИнфГО']['СвПогруз'] = $loading;
        }

        $driver = $this->driver($task);
        if ($driver) {
            $document['СодИнфГО']['СвВодит'] = $driver;
        }

        $vehicle = $route ? $this->vehicle($route) : null;
        if ($vehicle) {
            $document['СодИнфГО']['СвТС'] = ['ТС' => $vehicle];
        }

        return $document;
    }

    public function validate(Task $task): array
    {
        try {
            $this->buildDocument($task);
        } catch (SabyValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    private function ourOrganization(): array
    {
        $config = $this->client->config();
        $inn = trim((string) $config->param('our_inn', ''));
        $kpp = trim((string) $config->param('our_kpp', ''));

        if ($inn === '') {
            throw new SabyException('В настройках модуля Saby не указан ИНН нашей организации');
        }

        if (strlen($inn) > 10 || $kpp === '') {
            return ['СвФЛ' => ['ИНН' => $inn]];
        }

        return ['СвЮЛ' => ['ИНН' => $inn, 'КПП' => $kpp]];
    }

    private function party(Company $company, ?Contact $contact = null): array
    {
        $inn = $this->inn($company);
        $kpp = $this->kpp($company);
        $requisite = $this->requisite($company);

        $identity = strlen($inn) > 10
            ? ['СвИП' => array_filter([
                'ИННФЛ' => $inn,
                'ФИО' => $this->splitName($company->name),
            ])]
            : ['СвЮЛУч' => array_filter([
                'ИННЮЛ' => $inn,
                'КПП' => $kpp,
                'НаимОрг' => (string) $company->name,
            ])];

        $party = ['ИдСв' => $identity];

        $address = $this->companyAddress($company, $requisite);
        if ($address !== '') {
            $party['Адрес'] = ['АдрИнф' => ['АдрТекст' => $address, 'КодСтр' => '643']];
        }

        $contactInfo = $this->contactInfo($contact ? $this->contactPhone($contact) : '', $contact ? $this->contactEmail($contact) : '', $this->phone($company));
        if (count($contactInfo)) {
            $party['Контакт'] = $contactInfo;
        }

        return $party;
    }

    private function contactParty(Contact $contact): array
    {
        $inn = $this->contactInn($contact);
        $party = ['ИдСв' => ['СвИП' => array_filter([
            'ИННФЛ' => $inn !== '' ? $inn : null,
            'ФИО' => $this->splitName($this->contactName($contact)),
        ])]];

        $contactInfo = $this->contactInfo($this->contactPhone($contact), $this->contactEmail($contact), '');
        if (count($contactInfo)) {
            $party['Контакт'] = $contactInfo;
        }

        return $party;
    }

    private function contactInfo(string $contactPhone, string $email, string $companyPhone): array
    {
        $info = [];
        $phones = array_values(array_unique(array_filter([$contactPhone, $companyPhone], fn ($v) => $v !== '')));
        if (count($phones)) {
            $info['Тлф'] = array_map(fn ($phone) => ['value' => $phone], $phones);
        }
        if ($email !== '') {
            $info['ЭлПочта'] = [['value' => $email]];
        }

        return $info;
    }

    private function contactCounterparty(?Contact $contact): array
    {
        if (!$contact) {
            return [];
        }

        $inn = $this->contactInn($contact);
        if (strlen($inn) !== 12) {
            return [];
        }
        $name = $this->splitName($this->contactName($contact));

        return ['СвФЛ' => array_filter([
            'ИНН' => $inn,
            'Фамилия' => $name['Фамилия'] ?? null,
            'Имя' => $name['Имя'] ?? null,
            'Отчество' => $name['Отчество'] ?? null,
        ])];
    }

    private function contactOf(Task $task): ?Contact
    {
        if (!Schema::hasTable('contacts')) {
            return null;
        }

        $ids = Route::parseIdList($this->attr($task, 'contact_id'));
        $id = count($ids) ? $ids[0] : null;

        if (!$id && Schema::hasTable('logistic_task_contact')) {
            $id = \DB::table('logistic_task_contact')->where('logistic_task_id', $task->id)->value('contact_id');
        }

        return $id ? Contact::find($id) : null;
    }

    private function contactName(Contact $contact): string
    {
        $name = $this->attr($contact, 'name');
        if (is_string($name) && $name !== '' && ($name[0] === '{' || $name[0] === '[')) {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                $name = (string) ($decoded['value'] ?? reset($decoded));
            }
        }

        return trim((string) $name);
    }

    private function contactInn(Contact $contact): string
    {
        return preg_replace('/\D/', '', (string) $this->attr($contact, 'inn'));
    }

    private function contactPhone(Contact $contact): string
    {
        foreach (['phones', 'phone', 'work_phone'] as $field) {
            $value = $this->phoneValue($this->attr($contact, $field));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function contactEmail(Contact $contact): string
    {
        foreach (['emails', 'email'] as $field) {
            $value = $this->phoneValue($this->attr($contact, $field));
            if ($value !== '' && str_contains($value, '@')) {
                return $value;
            }
        }

        return '';
    }

    private function counterparty(Company $company): array
    {
        $inn = $this->inn($company);
        if (strlen($inn) > 10) {
            $name = $this->splitName($company->name);
            return ['СвФЛ' => array_filter([
                'ИНН' => $inn,
                'Фамилия' => $name['Фамилия'] ?? null,
                'Имя' => $name['Имя'] ?? null,
                'Отчество' => $name['Отчество'] ?? null,
            ])];
        }

        return ['СвЮЛ' => array_filter([
            'ИНН' => $inn,
            'КПП' => $this->kpp($company),
            'Название' => (string) $company->name,
        ])];
    }

    private function driver(Task $task): ?array
    {
        $ids = Route::parseIdList($this->attr($task, 'employee_id'));
        $employeeId = count($ids) ? $ids[0] : null;

        if (!$employeeId && \Schema::hasTable('logistic_task_employee')) {
            $employeeId = \DB::table('logistic_task_employee')->where('logistic_task_id', $task->id)->value('employee_id');
        }

        if (!$employeeId) {
            return null;
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return null;
        }

        $driver = ['ФИО' => $this->splitName($employee->name)];

        $inn = trim((string) $this->attr($employee, 'inn'));
        if ($inn !== '') {
            $driver['ИННФЛ'] = $inn;
        }

        $snils = trim((string) $this->attr($employee, 'snils'));
        if ($snils !== '') {
            $driver['СНИЛС'] = $snils;
        }

        $license = trim((string) $this->attr($employee, 'driver_license'));
        if ($license !== '') {
            $driver['ВодУдост'] = $license;
        }

        $phone = $this->phoneValue($employee->phone);
        if ($phone !== '') {
            $driver['Тлф'] = [['value' => $phone]];
        }

        return $driver;
    }

    private function vehicle(Route $route): ?array
    {
        if (!$route->car_id) {
            return null;
        }

        $car = Car::find($route->car_id);
        if (!$car) {
            return null;
        }

        $vehicle = [];

        $number = trim((string) $car->number);
        if ($number !== '') {
            $vehicle['РегНомер'] = $number;
        }

        $ownership = trim((string) $this->attr($car, 'ownership_type'));
        $vehicle['ТипВлад'] = $ownership !== '' ? $ownership : '1';

        $params = [];
        $markName = trim(trim((string) $this->attr($car, 'brand')) . ' ' . trim((string) $this->attr($car, 'car_model')));
        if ($markName === '') {
            $markName = trim((string) $car->name);
        }
        if ($markName !== '') {
            $params['Марка'] = $markName;
        }
        $type = $this->fieldOptionLabel('cars', 'vehicle_type', $this->attr($car, 'vehicle_type'));
        if ($type !== '') {
            $params['Тип'] = $type;
        }
        $capacity = $this->number($car->weight_max);
        if ($capacity > 0) {
            $params['Грузопод'] = $this->format($capacity / 1000, 3);
        }
        $volume = $this->number($car->volume_max);
        if ($volume > 0) {
            $params['Вместим'] = $this->format($volume / 1000, 3);
        }
        if (count($params)) {
            $vehicle['ПарТС'] = $params;
        }

        if (!count($vehicle)) {
            return null;
        }

        $trailerNumber = trim((string) $this->attr($car, 'trailer_number'));
        if ($trailerNumber !== '') {
            return [$vehicle, ['РегНомер' => $trailerNumber, 'ТипВлад' => '1']];
        }

        return $vehicle;
    }

    private function fieldOptionLabel(string $entity, string $field, $value): string
    {
        $raw = is_array($value) ? ($value[0] ?? null) : $value;
        if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
            $raw = $decoded[0] ?? null;
        }
        if ($raw === null || trim((string) $raw) === '') {
            return '';
        }

        $row = \DB::table('data_rows')
            ->join('data_types', 'data_rows.data_type_id', '=', 'data_types.id')
            ->where('data_types.slug', $entity)
            ->where('data_rows.field', $field)
            ->value('data_rows.details');
        $details = $row ? json_decode($row, true) : null;
        foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $option) {
            if (is_array($option) && (string) ($option['value'] ?? '') === (string) $raw) {
                $label = $option['label'] ?? '';
                return trim((string) (is_array($label) ? ($label['text'] ?? '') : $label));
            }
        }

        return '';
    }

    private function cargo($tasks): array
    {
        $config = $this->client->config();
        $defaultCondition = (string) $config->param('cargo_condition', 'Хорошее');
        $defaultPacking = (string) $config->param('packing_method', 'Отсутствует');
        $defaultTare = (string) $config->param('tare_type', '');

        $items = [];
        foreach ($tasks as $task) {
            foreach ($this->products($task) as $product) {
                $name = trim((string) ($product['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                if ($this->isService($product['id'] ?? null)) {
                    continue;
                }
                $key = ($product['id'] ?? null) ? 'id:' . $product['id'] : 'name:' . mb_strtolower($name);
                if (!isset($items[$key])) {
                    $items[$key] = [
                        'id' => $product['id'] ?? null,
                        'name' => $name,
                        'count' => 0.0,
                        'weight' => 0.0,
                        'volume' => 0.0,
                    ];
                }
                $count = $this->number($product['count'] ?? 0);
                $items[$key]['count'] += $count;
                $items[$key]['weight'] += $this->number($product['weight'] ?? 0) * ($count ?: 1);
                $items[$key]['volume'] += $this->number($product['volume'] ?? 0) * ($count ?: 1);
            }
        }

        $cargo = [];
        foreach ($items as $item) {
            $entry = [
                'НаимГруз' => $item['name'],
                'КолМестГр' => $this->format($item['count'] ?: 1),
                'СостГруз' => $defaultCondition,
            ];

            $packing = $this->productAttr($item['id'], 'packing_method', $defaultPacking);
            if ($packing !== '') {
                $entry['СпУпак'] = $packing;
            }

            $tare = $this->productAttr($item['id'], 'tare_type', $defaultTare);
            if ($tare !== '') {
                $entry['ВидТар'] = $tare;
            }

            if ($item['weight'] > 0) {
                $entry['ПлМасГруз'] = ['МасБрутЗнач' => $this->format($item['weight'])];
            }

            if ($item['volume'] > 0) {
                $entry['Объем'] = $this->format($item['volume'] / 1000, 3);
            }

            $cargo[] = $entry;
        }

        return $cargo;
    }

    private function products($task): array
    {
        $raw = $task->products ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function isService($productId): bool
    {
        if (!$productId || !Schema::hasColumn('products', 'product_type')) {
            return false;
        }

        $raw = \DB::table('products')->where('id', $productId)->value('product_type');
        if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
            $raw = $decoded[0] ?? null;
        }

        return trim((string) $raw) === '1';
    }

    private function productAttr($productId, string $field, string $default): string
    {
        if (!$productId || !Schema::hasColumn('products', $field)) {
            return $default;
        }

        $value = \DB::table('products')->where('id', $productId)->value($field);
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }

    private function loadingDateTime(Task $loadingTask, ?Route $route): string
    {
        $date = trim((string) ($loadingTask->delivery_date ?: ($route?->date ?: '')));
        try {
            $base = $date !== '' ? \Carbon\Carbon::parse($date) : now();
        } catch (\Throwable $e) {
            $base = now();
        }
        $time = trim((string) $this->attr($loadingTask, 'plan_time'));
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            $base->setTime((int) $m[1], (int) $m[2], 0);
        } else {
            $base->setTime(0, 0, 0);
        }

        return $base->format('d.m.Y\TH:i:sP');
    }

    private function taskAddress(Task $task, ?Company $receiver = null): string
    {
        $text = $this->addressText($task->address);

        if ($text !== '') {
            if (!$this->isCoordinates($text)) {
                return $text;
            }

            return $this->resolveCoordinates($text) ?: $text;
        }

        if ($receiver) {
            $fallback = $this->companyAddress($receiver, $this->requisite($receiver));
            if ($fallback !== '') {
                return $fallback;
            }
        }

        return '';
    }

    private function resolveCoordinates(string $text): string
    {
        if (!preg_match('/^\s*(-?\d+\.\d+)\s*[,\s]\s*(-?\d+\.\d+)\s*$/', $text, $m)) {
            return '';
        }

        $key = $m[1] . ',' . $m[2];
        if (array_key_exists($key, $this->geocodeCache)) {
            return $this->geocodeCache[$key];
        }

        $address = $this->yandexReverse((float) $m[1], (float) $m[2]);
        if ($address !== '') {
            return $this->geocodeCache[$key] = $address;
        }

        $token = config('services.dadata.token');
        $secret = config('services.dadata.secret');

        if ($token && $secret) {
            $dadata = new Dadata($token, $secret);
            try {
                $dadata->init();
                $result = $dadata->geolocate((float) $m[1], (float) $m[2], 5, self::GEOCODE_RADIUS);
                foreach ($result['suggestions'] ?? [] as $item) {
                    if (empty($item['data']['house'])) {
                        continue;
                    }
                    $address = trim((string) ($item['unrestricted_value'] ?? $item['value'] ?? ''));
                    if ($address !== '') {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('saby')->warning('Не удалось определить адрес по координатам ' . $key . ': ' . $e->getMessage());
                $address = '';
            } finally {
                try {
                    $dadata->close();
                } catch (\Throwable $e) {
                }
            }
        }

        return $this->geocodeCache[$key] = $address;
    }

    private function yandexReverse(float $lat, float $lng): string
    {
        $key = (string) config('services.yandex.geocoder_key');
        if ($key === '') {
            return '';
        }
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => $key,
                'geocode' => $lng . ',' . $lat,
                'format' => 'json',
                'kind' => 'house',
                'results' => 1,
                'lang' => 'ru_RU',
            ]);
            if (!$response->ok()) {
                return '';
            }
            foreach ($response->json('response.GeoObjectCollection.featureMember', []) as $member) {
                $meta = $member['GeoObject']['metaDataProperty']['GeocoderMetaData'] ?? [];
                if (($meta['kind'] ?? '') !== 'house' || !in_array($meta['precision'] ?? '', ['exact', 'number', 'near'], true)) {
                    continue;
                }
                $text = trim((string) ($meta['text'] ?? ''));
                if ($text !== '') {
                    return preg_replace('/^Россия,\s*/u', '', $text);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('saby')->warning('Яндекс-геокодер недоступен для ' . $lat . ',' . $lng . ': ' . $e->getMessage());
        }

        return '';
    }

    private function isCoordinates(string $text): bool
    {
        return (bool) preg_match('/^\s*-?\d+\.\d+\s*[,\s]\s*-?\d+\.\d+\s*$/', $text);
    }

    private function addressText($raw): string
    {
        if (is_array($raw)) {
            return trim((string) ($raw['text'] ?? ''));
        }
        if (!is_string($raw) || trim($raw) === '') {
            return '';
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return trim((string) ($decoded['text'] ?? ''));
        }

        return trim($raw);
    }

    private function companyOf($model, string $field): ?Company
    {
        $value = $this->attr($model, $field);
        $ids = Route::parseIdList($value);
        $id = count($ids) ? $ids[0] : null;

        return $id ? Company::find($id) : null;
    }

    private function requisite(Company $company): ?Requisite
    {
        if (!Schema::hasTable('requisites')) {
            return null;
        }

        return Requisite::where('company_id', $company->id)->first();
    }

    private function companyAddress(Company $company, ?Requisite $requisite): string
    {
        foreach ([$requisite->fact_address ?? null, $requisite->address ?? null, $company->address ?? null] as $value) {
            $text = $this->addressText($value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function inn(Company $company): string
    {
        return preg_replace('/\D/', '', (string) $this->attr($company, 'inn'));
    }

    private function kpp(Company $company): string
    {
        return preg_replace('/\D/', '', (string) $this->attr($company, 'kpp'));
    }

    private function phone(Company $company): string
    {
        foreach (['phone', 'work_phone'] as $field) {
            $value = $this->phoneValue($this->attr($company, $field));
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($this->companyPhoneFields() as $field) {
            $value = $this->phoneValue($this->attr($company, $field));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function companyPhoneFields(): array
    {
        static $fields = null;

        if ($fields !== null) {
            return $fields;
        }

        $fields = [];
        try {
            $typeId = \DB::table('data_types')->where('slug', 'companies')->value('id');
            if ($typeId) {
                $fields = \DB::table('data_rows')
                    ->where('data_type_id', $typeId)
                    ->where('title', 'LIKE', '%елефон%')
                    ->pluck('field')
                    ->all();
            }
        } catch (\Throwable $e) {
            $fields = [];
        }

        return $fields;
    }

    private function phoneValue($raw): string
    {
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $value = $this->phoneValue($item);
                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->phoneValue($decoded['value'] ?? $decoded);
            }
        }

        return $value;
    }

    private function splitName(?string $name): array
    {
        $clean = preg_replace('/^\s*(ИП|Индивидуальный предприниматель)\s+/iu', '', trim((string) $name));
        $parts = preg_split('/\s+/u', trim((string) $clean)) ?: [];

        return array_filter([
            'Фамилия' => $parts[0] ?? '',
            'Имя' => $parts[1] ?? '',
            'Отчество' => $parts[2] ?? '',
        ], fn ($v) => $v !== '');
    }

    private function nextNumber(Task $task): string
    {
        $prefix = trim((string) $this->client->config()->param('number_prefix', ''));

        return $prefix !== '' ? $prefix . $task->id : (string) $task->id;
    }

    private function attr($model, string $field)
    {
        if (!$model) {
            return null;
        }

        return array_key_exists($field, $model->getAttributes()) ? $model->getAttribute($field) : null;
    }

    private function formatDate($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function number($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    private function format(float $value, int $precision = 2): string
    {
        $rounded = round($value, $precision);

        return rtrim(rtrim(number_format($rounded, $precision, '.', ''), '0'), '.') ?: '0';
    }
}
