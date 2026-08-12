<?php

namespace App\Services\Saby;

use App\Models\Car;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Requisite;
use App\Models\Route;
use App\Models\SabyWaybill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SabyWaybillService
{
    public const FORMAT_VERSION = '5.01';
    public const SHIPPER_TITLE_KND = '1110339';
    public const DOC_TYPE = 'ConsignmentNote';
    public const REGULATION = 'Транспортная накладная';

    private SabyClient $client;

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

    public function create(Route $route): SabyWaybill
    {
        $document = $this->buildDocument($route);
        $config = $this->client->config();

        $number = $this->nextNumber($route);
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

        $written = $this->client->call('СБИС.ЗаписатьДокумент', [
            'Документ' => [
                'Тип' => self::DOC_TYPE,
                'Регламент' => ['Название' => self::REGULATION],
                'НашаОрганизация' => $this->ourOrganization(),
                'Вложение' => [
                    ['Файл' => [
                        'ДвоичныеДанные' => $file['ДвоичныеДанные'],
                        'Имя' => $file['Имя'] ?? null,
                    ]],
                ],
            ],
        ]);

        $attachment = $written['Вложение'][0] ?? [];

        $waybill = SabyWaybill::create([
            'route_id' => $route->id,
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

        $config->mergeParams(['waybill_counter' => (int) $number]);

        $this->log('info', 'waybill created', [
            'route_id' => $route->id,
            'doc_id' => $waybill->doc_id,
            'number' => $waybill->number,
            'flc_errors' => $attachment['КоличествоОшибок'] ?? null,
        ]);

        return $waybill;
    }

    public function refresh(SabyWaybill $waybill): SabyWaybill
    {
        if (!$waybill->doc_id) {
            return $waybill;
        }

        $document = $this->client->call('СБИС.ПрочитатьДокумент', [
            'Документ' => ['Идентификатор' => $waybill->doc_id],
        ]);

        $attachment = $document['Вложение'][0] ?? [];

        $waybill->update([
            'status' => $document['Состояние']['Название'] ?? $waybill->status,
            'pdf_url' => $document['СсылкаНаPDF'] ?? $waybill->pdf_url,
            'cabinet_url' => $document['СсылкаДляНашаОрганизация'] ?? ($attachment['СсылкаВКабинет'] ?? $waybill->cabinet_url),
            'archive_url' => $document['СсылкаНаАрхив'] ?? $waybill->archive_url,
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

    public function buildDocument(Route $route): array
    {
        $errors = [];

        $shipper = $this->companyOf($route, 'company_id');
        if (!$shipper) {
            $errors[] = 'В маршруте не заполнено поле «Компания» (отправитель)';
        }

        $receiver = $this->companyOf($route, 'receiver_company_id');
        if (!$receiver) {
            $errors[] = 'В маршруте не заполнено поле «Получатель»';
        }

        $tasks = $route->logistic_tasks()->get();
        if ($tasks->isEmpty()) {
            $errors[] = 'К маршруту не привязано ни одной задачи логистики';
        }

        $cargo = $this->cargo($tasks);
        if (!count($cargo)) {
            $errors[] = 'В задачах маршрута не заполнено поле «Состав»';
        }

        $deliveryAddress = $this->deliveryAddress($tasks);
        if ($deliveryAddress === '') {
            $errors[] = 'В задачах маршрута не заполнен адрес доставки';
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
                'ДатаТрН' => $this->formatDate($route->date) ?: now()->format('d.m.Y'),
                'НомерТрН' => (string) $route->id,
                'СвГО' => ['РекИдентГО' => $this->party($shipper)],
                'СвГП' => [
                    'РекИдентГП' => $this->party($receiver),
                    'АдресДостГр' => ['АдресИнф' => ['АдрТекст' => $deliveryAddress, 'КодСтр' => '643']],
                ],
                'СвГруз' => ['ОпГруз' => $cargo],
            ],
        ];

        $requestNumber = trim((string) $this->attr($route, 'request_number'));
        if ($requestNumber !== '') {
            $document['СодИнфГО']['НомЗак'] = $requestNumber;
            $requestDate = $this->formatDate($this->attr($route, 'request_date'));
            if ($requestDate !== '') {
                $document['СодИнфГО']['ДатаЗак'] = $requestDate;
            }
        }

        $carrier = $this->party($shipper);
        $document['СодИнфГО']['СвПер'] = $carrier['ИдСв'] + array_filter([
            'Адрес' => $carrier['Адрес'] ?? null,
            'Контакт' => $carrier['Контакт'] ?? null,
        ]);

        $driver = $this->driver($route);
        if ($driver) {
            $document['СодИнфГО']['СвВодит'] = $driver;
        }

        $vehicle = $this->vehicle($route);
        if ($vehicle) {
            $document['СодИнфГО']['СвТС'] = ['ТС' => $vehicle];
        }

        return $document;
    }

    public function validate(Route $route): array
    {
        try {
            $this->buildDocument($route);
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

    private function party(Company $company): array
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

        $phone = $this->phone($company);
        if ($phone !== '') {
            $party['Контакт'] = ['Тлф' => [['value' => $phone]]];
        }

        return $party;
    }

    private function driver(Route $route): ?array
    {
        $employeeId = $route->firstEmployeeId();
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

        $phone = trim((string) $employee->phone);
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
        $mark = $car->mark;
        if ($mark && trim((string) $mark->name) !== '') {
            $params['Марка'] = (string) $mark->name;
        }
        $model = $car->model;
        if ($model && trim((string) $model->name) !== '') {
            $params['Тип'] = (string) $model->name;
        }
        $capacity = $this->number($car->weight_max);
        if ($capacity > 0) {
            $params['Грузопод'] = $this->format($capacity);
        }
        $volume = $this->number($car->volume_max);
        if ($volume > 0) {
            $params['Вместим'] = $this->format($volume);
        }
        if (count($params)) {
            $vehicle['ПарТС'] = $params;
        }

        return count($vehicle) ? $vehicle : null;
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

    private function productAttr($productId, string $field, string $default): string
    {
        if (!$productId || !Schema::hasColumn('products', $field)) {
            return $default;
        }

        $value = \DB::table('products')->where('id', $productId)->value($field);
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }

    private function deliveryAddress($tasks): string
    {
        $address = '';
        foreach ($tasks as $task) {
            $text = $this->addressText($task->address);
            if ($text !== '') {
                $address = $text;
            }
        }

        return $address;
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

    private function companyOf(Route $route, string $field): ?Company
    {
        $value = $this->attr($route, $field);
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
            $value = trim((string) $this->attr($company, $field));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];

        return array_filter([
            'Фамилия' => $parts[0] ?? '',
            'Имя' => $parts[1] ?? '',
            'Отчество' => $parts[2] ?? '',
        ], fn ($v) => $v !== '');
    }

    private function nextNumber(Route $route): string
    {
        $config = $this->client->config();
        $prefix = trim((string) $config->param('number_prefix', ''));
        $counter = (int) $config->param('waybill_counter', 0) + 1;

        return $prefix !== '' ? $prefix . $counter : (string) $counter;
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
