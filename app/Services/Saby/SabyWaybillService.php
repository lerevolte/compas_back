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
use App\Models\SabyOrder;
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
    public const EDO_OPERATOR_PREFIX = '2BE';

    public const MASS_METHODS = [
        '1' => 'Взвешивание по общей массе',
        '2' => 'Взвешивание поосно',
        '3' => 'Расчетная масса',
    ];

    protected SabyClient $client;
    protected array $geocodeCache = [];

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

    public function create(Task $task, ?Task $loadingTask = null, ?string $massMethod = null, ?Task $unloadingTask = null): SabyWaybill
    {
        if ($unloadingTask && !$loadingTask) {
            $loadingTask = $task;
        }
        $document = $this->buildDocument($task, $loadingTask, $massMethod, $unloadingTask);
        $config = $this->client->config();
        $route = $task->route_id ? Route::find($task->route_id) : null;
        $shipper = $this->companyOf($task, 'shipment_company_id');
        $carrier = $route ? $this->companyOf($route, 'company_id') : null;
        [$receiver, $receiverContact] = $this->waybillReceiver($task, $unloadingTask);

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
            'ТранспортнаяКомпания' => $this->addressedCounterparty($carrier ?: $shipper),
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

        $written = $this->writeDocument($payload, ['ТранспортнаяКомпания']);

        $attachment = $written['Вложение'][0] ?? [];

        $waybill = SabyWaybill::create([
            'task_id' => $task->id,
            'loading_task_id' => $loadingTask?->id,
            'unloading_task_id' => Schema::hasColumn('saby_waybills', 'unloading_task_id') ? $unloadingTask?->id : null,
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

        $this->linkOrder($task, $waybill, $written);

        return $waybill;
    }

    public function orderFor(Task $task): ?SabyOrder
    {
        if (!Schema::hasTable('saby_orders')) {
            return null;
        }
        return SabyOrder::where(function ($q) use ($task) {
                $q->where('task_id', $task->id)->orWhere('unloading_task_id', $task->id);
            })
            ->whereNotNull('doc_id')
            ->where('doc_id', '!=', '')
            ->orderByDesc('id')
            ->first();
    }

    public function createOrAdopt(SabyOrder $order, Task $task, ?Task $loadingTask = null, ?string $massMethod = null, ?Task $unloadingTask = null): array
    {
        $docId = $order->waybill_doc_id ?: $this->linkedWaybillDocId($order);
        if ($docId) {
            return ['adopted' => true, 'waybill' => $this->adopt($order, $docId, $task, $loadingTask, $massMethod, $unloadingTask)];
        }

        return ['adopted' => false, 'waybill' => $this->create($task, $loadingTask, $massMethod, $unloadingTask)];
    }

    public function ensureReceiver(SabyOrder $order, bool $force = false): ?SabyWaybill
    {
        if (!$order->waybill_doc_id || !self::isDraftState($order->waybill_state)) {
            return null;
        }
        $existing = SabyWaybill::where('doc_id', $order->waybill_doc_id)->first();
        if ($existing && !$force) {
            $payload = is_array($existing->payload) ? $existing->payload : [];
            if (!empty($payload['adopted'])) {
                return $existing;
            }
            $error = is_array($existing->error) ? $existing->error : null;
            if ($error && !empty($error['at'])) {
                try {
                    if (\Carbon\Carbon::parse($error['at'])->gt(now()->subMinutes(30))) {
                        return $existing;
                    }
                } catch (\Throwable $e) {
                }
            }
        }
        $task = Task::find($order->task_id);
        if (!$task) {
            return $existing;
        }
        $loadingTask = null;
        $unloadingTask = null;
        if ($order->current_is_loading) {
            $unloadingTask = $order->unloading_task_id ? Task::find($order->unloading_task_id) : null;
        } elseif ($order->loading_task_id) {
            $loadingTask = Task::find($order->loading_task_id);
        }
        $massMethod = $order->mass_method !== null && $order->mass_method !== '' ? (string) $order->mass_method : null;

        try {
            return $this->adopt($order, $order->waybill_doc_id, $task, $loadingTask, $massMethod, $unloadingTask);
        } catch (SabyValidationException $e) {
            $message = implode('; ', $e->errors());
        } catch (\Throwable $e) {
            $message = $e->getMessage();
        }
        $this->log('warning', 'auto receiver failed', ['order_id' => $order->id, 'doc_id' => $order->waybill_doc_id, 'error' => $message]);
        $values = [
            'task_id' => $task->id,
            'route_id' => $task->route_id,
            'doc_id' => $order->waybill_doc_id,
            'number' => $order->waybill_number,
            'date' => $order->waybill_date,
            'status' => $order->waybill_state,
            'pdf_url' => $order->waybill_pdf_url,
            'cabinet_url' => $order->waybill_cabinet_url,
            'archive_url' => $order->waybill_archive_url,
            'error' => ['message' => $message, 'at' => now()->toDateTimeString()],
        ];
        if ($existing) {
            $existing->update($values);
            return $existing;
        }

        return SabyWaybill::create($values + ['payload' => null, 'user_id' => null]);
    }

    public function linkedWaybillDocId(SabyOrder $order): ?string
    {
        if (!$order->doc_id) {
            return null;
        }
        try {
            $document = $this->client->call('СБИС.ПрочитатьДокумент', ['Документ' => ['Идентификатор' => $order->doc_id]]);
        } catch (SabyException $e) {
            return null;
        }

        return self::linkedDocumentId($document, self::DOC_TYPE);
    }

    public static function linkedDocumentId(array $document, string $type): ?string
    {
        foreach (['ДокументСледствие', 'ДокументОснование'] as $key) {
            foreach ((array) ($document[$key] ?? []) as $link) {
                $linked = is_array($link) ? ($link['Документ'] ?? []) : [];
                if (($linked['Тип'] ?? '') === $type && !empty($linked['Идентификатор']) && (($linked['Удален'] ?? 'Нет') !== 'Да')) {
                    return (string) $linked['Идентификатор'];
                }
            }
        }

        return null;
    }

    public function adopt(SabyOrder $order, string $docId, Task $task, ?Task $loadingTask = null, ?string $massMethod = null, ?Task $unloadingTask = null): SabyWaybill
    {
        if ($unloadingTask && !$loadingTask) {
            $loadingTask = $task;
        }
        $document = $this->client->call('СБИС.ПрочитатьДокумент', ['Документ' => ['Идентификатор' => $docId, 'ДопПоля' => 'ЭПД']]);
        $state = $document['Состояние']['Название'] ?? null;
        if (!self::isDraftState($state)) {
            throw new SabyException('Накладная № ' . ($document['Номер'] ?? '') . ' в Saby уже подписана или отправлена (' . $state . ') — грузополучателя можно изменить только в Saby');
        }
        $attachment = null;
        foreach ((array) ($document['Вложение'] ?? []) as $item) {
            if (($item['Тип'] ?? '') === 'ЭТрН' && (string) ($item['Подтип'] ?? '') === (string) self::SHIPPER_TITLE_KND && (($item['Удален'] ?? 'Нет') !== 'Да')) {
                $attachment = $item;
                break;
            }
        }
        if (!$attachment || empty($attachment['Файл']['Ссылка'])) {
            throw new SabyException('В накладной Saby нет титула грузоотправителя, который можно дополнить');
        }

        $theirXml = $this->downloadAttachment($attachment);
        $ourDocument = $this->buildDocument($task, $loadingTask, $massMethod, $unloadingTask);
        $ourDocument['СодИнфГО']['НомерТрН'] = (string) ($document['Номер'] ?? $ourDocument['СодИнфГО']['НомерТрН']);
        $generated = $this->client->call('СБИС.СгенерироватьВложение', [
            'Документ' => [
                'Вложение' => [
                    'Тип' => 'ЭТрН',
                    'Подтип' => self::SHIPPER_TITLE_KND,
                    'ВерсияФормата' => (string) $this->client->config()->param('format_version', self::FORMAT_VERSION),
                    'Подстановка' => [
                        self::SHIPPER_TITLE_KND => ['Файл' => ['Документ' => $ourDocument]],
                    ],
                ],
            ],
        ]);
        $ourFile = $generated['Вложение'][0]['Файл'] ?? null;
        if (!isset($ourFile['ДвоичныеДанные'])) {
            throw new SabyException('Saby не вернул сформированный титул с грузополучателем');
        }
        $merged = $this->mergeReceiver($theirXml, base64_decode($ourFile['ДвоичныеДанные']));

        $payload = [
            'Идентификатор' => $docId,
            'Тип' => self::DOC_TYPE,
            'Вложение' => [
                ['Идентификатор' => $attachment['Идентификатор'] ?? null, 'Файл' => [
                    'Имя' => $attachment['Файл']['Имя'] ?? ($ourFile['Имя'] ?? 'ON_TRNACLGROT.xml'),
                    'ДвоичныеДанные' => base64_encode($merged),
                ]],
            ],
        ];
        if (empty($payload['Вложение'][0]['Идентификатор'])) {
            unset($payload['Вложение'][0]['Идентификатор']);
        }
        $written = $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => $payload]);
        $writtenAttachment = $written['Вложение'][0] ?? [];

        $values = [
            'task_id' => $task->id,
            'loading_task_id' => $loadingTask?->id,
            'unloading_task_id' => Schema::hasColumn('saby_waybills', 'unloading_task_id') ? $unloadingTask?->id : null,
            'mass_method' => Schema::hasColumn('saby_waybills', 'mass_method') ? $massMethod : null,
            'route_id' => $task->route_id,
            'doc_id' => $docId,
            'attachment_id' => $writtenAttachment['Идентификатор'] ?? ($attachment['Идентификатор'] ?? null),
            'number' => $written['Номер'] ?? ($document['Номер'] ?? null),
            'date' => $written['Дата'] ?? ($document['Дата'] ?? now()->format('d.m.Y')),
            'status' => $written['Состояние']['Название'] ?? $state,
            'pdf_url' => $written['СсылкаНаPDF'] ?? ($document['СсылкаНаPDF'] ?? null),
            'cabinet_url' => $written['СсылкаДляНашаОрганизация'] ?? ($document['СсылкаДляНашаОрганизация'] ?? null),
            'archive_url' => $written['СсылкаНаАрхив'] ?? ($document['СсылкаНаАрхив'] ?? null),
            'payload' => ['adopted' => true, 'receiver' => $ourDocument['СодИнфГО']['СвГП'] ?? null],
            'error' => null,
            'user_id' => auth()->id(),
        ];
        $waybill = SabyWaybill::where('doc_id', $docId)->first();
        if ($waybill) {
            $waybill->update($values);
        } else {
            $waybill = SabyWaybill::create($values);
        }

        $this->log('info', 'waybill adopted from saby', [
            'task_id' => $task->id,
            'order_id' => $order->id,
            'doc_id' => $docId,
            'number' => $waybill->number,
            'flc_errors' => $writtenAttachment['КоличествоОшибок'] ?? null,
        ]);

        $this->linkWaybillToOrder($order, $waybill, $written);

        return $waybill;
    }

    protected function downloadAttachment(array $attachment): string
    {
        $link = (string) ($attachment['Файл']['Ссылка'] ?? '');
        $response = \Illuminate\Support\Facades\Http::withHeaders(['X-SBISSessionID' => $this->client->sessionId()])->timeout(30)->get($link);
        if (!$response->successful() || trim($response->body()) === '') {
            throw new SabyException('Не удалось скачать титул накладной из Saby (HTTP ' . $response->status() . ')');
        }

        return $response->body();
    }

    protected function mergeReceiver(string $theirXml, string $ourXml): string
    {
        $theirs = new \DOMDocument();
        $ours = new \DOMDocument();
        if (!@$theirs->loadXML($theirXml) || !@$ours->loadXML($ourXml)) {
            throw new SabyException('Не удалось разобрать XML титула накладной');
        }
        $xpTheirs = new \DOMXPath($theirs);
        $xpOurs = new \DOMXPath($ours);
        $theirBody = $xpTheirs->query('/Файл/Документ/СодИнфГО')->item(0);
        $ourReceiver = $xpOurs->query('/Файл/Документ/СодИнфГО/СвГП')->item(0);
        if (!$theirBody || !$ourReceiver) {
            throw new SabyException('В титуле накладной не найден блок грузополучателя');
        }
        $imported = $theirs->importNode($ourReceiver, true);
        $theirReceiver = $xpTheirs->query('СвГП', $theirBody)->item(0);
        if ($theirReceiver) {
            $theirBody->replaceChild($imported, $theirReceiver);
        } else {
            $shipper = $xpTheirs->query('СвГО', $theirBody)->item(0);
            if ($shipper && $shipper->nextSibling) {
                $theirBody->insertBefore($imported, $shipper->nextSibling);
            } else {
                $theirBody->appendChild($imported);
            }
        }

        $ourInstructions = $xpOurs->query('/Файл/Документ/СодИнфГО/УказГО')->item(0);
        if ($ourInstructions) {
            $importedInstructions = $theirs->importNode($ourInstructions, true);
            $theirInstructions = $xpTheirs->query('УказГО', $theirBody)->item(0);
            if ($theirInstructions) {
                $theirBody->replaceChild($importedInstructions, $theirInstructions);
            } else {
                $anchor = null;
                foreach (['СвПер', 'СвВодит', 'СвТС', 'СвПогруз', 'ПодпИнфГО'] as $tag) {
                    $anchor = $xpTheirs->query($tag, $theirBody)->item(0);
                    if ($anchor) {
                        break;
                    }
                }
                if ($anchor) {
                    $theirBody->insertBefore($importedInstructions, $anchor);
                } else {
                    $theirBody->appendChild($importedInstructions);
                }
            }
        }

        $theirLoading = $xpTheirs->query('СвПогруз', $theirBody)->item(0);
        $ourLoading = $xpOurs->query('/Файл/Документ/СодИнфГО/СвПогруз')->item(0);
        if ($theirLoading && $ourLoading) {
            foreach (['МасБрутОтгр', 'КолМестПрием'] as $attr) {
                if (!$theirLoading->hasAttribute($attr) && $ourLoading->hasAttribute($attr)) {
                    $theirLoading->setAttribute($attr, $ourLoading->getAttribute($attr));
                }
            }
        }

        $xml = $theirs->saveXML();
        if ($xml === false) {
            throw new SabyException('Не удалось собрать XML титула накладной');
        }

        return $xml;
    }

    protected function linkOrder(Task $task, SabyWaybill $waybill, array $written): void
    {
        $order = $this->orderFor($task);
        if (!$order || !$waybill->doc_id) {
            return;
        }
        $this->linkWaybillToOrder($order, $waybill, $written);
    }

    protected function linkWaybillToOrder(SabyOrder $order, SabyWaybill $waybill, array $written): void
    {
        if (!$waybill->doc_id) {
            return;
        }
        $attachment = $written['Вложение'][0] ?? [];
        $order->waybill_doc_id = $waybill->doc_id;
        $order->waybill_number = $waybill->number;
        $order->waybill_date = $waybill->date;
        $order->waybill_state = $written['Состояние']['Название'] ?? $waybill->status;
        $order->waybill_pdf_url = $waybill->pdf_url;
        $order->waybill_cabinet_url = $waybill->cabinet_url;
        $order->waybill_archive_url = $waybill->archive_url;
        $qr = trim((string) ($written['QRLink'] ?? ''));
        if ($qr !== '') {
            $order->waybill_qr_url = $qr;
        }
        $order->waybill_checked_at = now();
        $order->save();
        $this->log('info', 'waybill linked to order', ['order_id' => $order->id, 'waybill_doc_id' => $waybill->doc_id]);
    }

    public static function isDraftState($state): bool
    {
        $state = mb_strtolower(trim((string) $state));
        if ($state === '') {
            return true;
        }
        foreach (['редактир', 'черновик', 'создан'] as $marker) {
            if (str_contains($state, $marker)) {
                return true;
            }
        }

        return false;
    }

    public function canDelete(SabyWaybill $waybill): bool
    {
        if (!$waybill->doc_id) {
            return true;
        }
        $state = $waybill->status;
        if (Schema::hasTable('saby_orders')) {
            $order = SabyOrder::where('waybill_doc_id', $waybill->doc_id)->first();
            if ($order && $order->waybill_state) {
                $state = $order->waybill_state;
            }
        }

        return self::isDraftState($state);
    }

    public function delete(SabyWaybill $waybill): void
    {
        if ($waybill->doc_id) {
            $missing = false;
            try {
                $document = $this->client->call('СБИС.ПрочитатьДокумент', [
                    'Документ' => ['Идентификатор' => $waybill->doc_id],
                ]);
                $state = $document['Состояние']['Название'] ?? null;
                if (!self::isDraftState($state)) {
                    $waybill->update(['status' => $state]);
                    if (Schema::hasTable('saby_orders')) {
                        SabyOrder::where('waybill_doc_id', $waybill->doc_id)->update(['waybill_state' => $state, 'waybill_checked_at' => now()]);
                    }
                    throw new SabyException('Накладная уже подписана или отправлена (' . $state . ') — удалить её можно только в Saby');
                }
            } catch (SabyException $e) {
                if (!$this->isMissingDocumentError($e)) {
                    throw $e;
                }
                $missing = true;
            }
            if (!$missing) {
                try {
                    $this->client->call('СБИС.УдалитьДокумент', [
                        'Документ' => ['Идентификатор' => $waybill->doc_id],
                    ]);
                } catch (\Throwable $e) {
                    $this->log('warning', 'waybill delete in saby failed', [
                        'doc_id' => $waybill->doc_id,
                        'error' => $e->getMessage(),
                    ]);
                    if (!$this->isMissingDocumentError($e)) {
                        throw new SabyException('Saby не позволил удалить накладную: ' . $e->getMessage());
                    }
                }
            }
        }

        $this->log('info', 'waybill deleted', ['task_id' => $waybill->task_id, 'doc_id' => $waybill->doc_id]);
        if ($waybill->doc_id && Schema::hasTable('saby_orders')) {
            SabyOrder::where('waybill_doc_id', $waybill->doc_id)->update([
                'waybill_doc_id' => null, 'waybill_number' => null, 'waybill_date' => null, 'waybill_state' => null,
                'waybill_stage' => null, 'waybill_pdf_url' => null, 'waybill_cabinet_url' => null,
                'waybill_archive_url' => null, 'waybill_qr_url' => null, 'waybill_checked_at' => null,
            ]);
        }
        $waybill->delete();
    }

    public function generatePreview(Task $task): array
    {
        $document = $this->buildDocument($task);
        $generated = $this->client->call('СБИС.СгенерироватьВложение', [
            'Документ' => [
                'Вложение' => [
                    'Тип' => 'ЭТрН',
                    'Подтип' => self::SHIPPER_TITLE_KND,
                    'ВерсияФормата' => (string) $this->client->config()->param('format_version', self::FORMAT_VERSION),
                    'Подстановка' => [
                        self::SHIPPER_TITLE_KND => ['Файл' => ['Документ' => $document]],
                    ],
                ],
            ],
        ]);
        $attachment = $generated['Вложение'][0] ?? [];

        return [
            'document' => $document,
            'errors' => $attachment['КоличествоОшибок'] ?? null,
            'xml' => base64_decode((string) ($attachment['Файл']['ДвоичныеДанные'] ?? ''), true) ?: '',
        ];
    }

    public function updateData(SabyWaybill $waybill): SabyWaybill
    {
        if (!$waybill->doc_id) {
            throw new SabyException('У накладной нет документа в Saby — обновлять нечего');
        }
        $task = Task::find($waybill->task_id);
        if (!$task) {
            throw new SabyException('Задача накладной не найдена');
        }

        $loadingTask = $waybill->loading_task_id ? Task::find($waybill->loading_task_id) : null;
        $unloadingTask = null;
        if (Schema::hasColumn('saby_waybills', 'unloading_task_id') && $waybill->unloading_task_id) {
            $unloadingTask = Task::find($waybill->unloading_task_id);
        }
        $order = $this->orderFor($task);
        if (!$unloadingTask && $order && $order->current_is_loading && $order->unloading_task_id) {
            $unloadingTask = Task::find($order->unloading_task_id);
        }
        $massMethod = $waybill->mass_method !== null && $waybill->mass_method !== '' && isset(self::MASS_METHODS[$waybill->mass_method])
            ? (string) $waybill->mass_method
            : null;

        $payload = is_array($waybill->payload) ? $waybill->payload : [];
        if (!empty($payload['adopted']) && $order) {
            return $this->adopt($order, $waybill->doc_id, $task, $loadingTask, $massMethod, $unloadingTask);
        }

        $document = $this->client->call('СБИС.ПрочитатьДокумент', [
            'Документ' => ['Идентификатор' => $waybill->doc_id, 'ДопПоля' => 'ЭПД'],
        ]);
        $state = $document['Состояние']['Название'] ?? null;
        if (!self::isDraftState($state)) {
            throw new SabyException('Накладная № ' . ($document['Номер'] ?? $waybill->number) . ' в Saby уже подписана или отправлена (' . $state . ') — данные можно изменить только в Saby');
        }

        $attachment = null;
        foreach ((array) ($document['Вложение'] ?? []) as $item) {
            if (($item['Тип'] ?? '') === 'ЭТрН' && (string) ($item['Подтип'] ?? '') === (string) self::SHIPPER_TITLE_KND && (($item['Удален'] ?? 'Нет') !== 'Да')) {
                $attachment = $item;
                break;
            }
        }

        $ourDocument = $this->buildDocument($task, $loadingTask, $massMethod, $unloadingTask);
        $ourDocument['СодИнфГО']['НомерТрН'] = (string) ($document['Номер'] ?? ($waybill->number ?: $ourDocument['СодИнфГО']['НомерТрН']));

        $generated = $this->client->call('СБИС.СгенерироватьВложение', [
            'Документ' => [
                'Вложение' => [
                    'Тип' => 'ЭТрН',
                    'Подтип' => self::SHIPPER_TITLE_KND,
                    'ВерсияФормата' => (string) $this->client->config()->param('format_version', self::FORMAT_VERSION),
                    'Подстановка' => [
                        self::SHIPPER_TITLE_KND => ['Файл' => ['Документ' => $ourDocument]],
                    ],
                ],
            ],
        ]);
        $file = $generated['Вложение'][0]['Файл'] ?? null;
        if (!isset($file['ДвоичныеДанные'])) {
            throw new SabyException('Saby не вернул сформированный файл накладной');
        }

        $writePayload = [
            'Идентификатор' => $waybill->doc_id,
            'Тип' => self::DOC_TYPE,
            'Вложение' => [
                array_filter([
                    'Идентификатор' => $attachment['Идентификатор'] ?? ($waybill->attachment_id ?: null),
                    'Файл' => [
                        'Имя' => $attachment['Файл']['Имя'] ?? ($file['Имя'] ?? 'ON_TRNACLGROT.xml'),
                        'ДвоичныеДанные' => $file['ДвоичныеДанные'],
                    ],
                ]),
            ],
        ];
        $written = $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => $writePayload]);
        $writtenAttachment = $written['Вложение'][0] ?? [];

        $waybill->update([
            'attachment_id' => $writtenAttachment['Идентификатор'] ?? $waybill->attachment_id,
            'number' => $written['Номер'] ?? $waybill->number,
            'status' => $written['Состояние']['Название'] ?? $state,
            'pdf_url' => $written['СсылкаНаPDF'] ?? $waybill->pdf_url,
            'cabinet_url' => $written['СсылкаДляНашаОрганизация'] ?? $waybill->cabinet_url,
            'archive_url' => $written['СсылкаНаАрхив'] ?? $waybill->archive_url,
            'payload' => $ourDocument,
            'error' => null,
        ]);

        $this->log('info', 'waybill data updated', [
            'task_id' => $task->id,
            'doc_id' => $waybill->doc_id,
            'number' => $waybill->number,
            'flc_errors' => $writtenAttachment['КоличествоОшибок'] ?? null,
        ]);

        return $waybill;
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

    protected function isMissingDocumentError(\Throwable $e): bool
    {
        $message = mb_strtolower($e->getMessage());
        foreach (['не найден', 'not found', 'удален', 'удалён', 'не существует'] as $marker) {
            if (str_contains($message, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        try {
            Log::channel('saby')->{$level}($message, $context);
        } catch (\Throwable $e) {
        }
    }

    public function buildDocument(Task $task, ?Task $loadingTask = null, ?string $massMethod = null, ?Task $unloadingTask = null): array
    {
        $errors = [];

        if ($unloadingTask && !$loadingTask) {
            $loadingTask = $task;
        }
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

        [$receiver, $receiverContact] = $this->waybillReceiver($task, $unloadingTask);
        if (!$receiver && !$receiverContact) {
            $errors[] = 'В задаче не заполнено ни поле «Компания», ни поле «Контакт» (грузополучатель)';
        }

        $cargo = $this->cargo([$task]);
        if (!count($cargo)) {
            $errors[] = 'В задаче не заполнено поле «Состав»';
        }

        $deliveryAddress = $this->taskAddress($unloadingTask ?: $task, $receiver);
        if ($deliveryAddress === '' && $unloadingTask) {
            $deliveryAddress = $this->taskAddress($task, $receiver);
        }
        if ($deliveryAddress === '') {
            $errors[] = $unloadingTask ? 'У точки выгрузки не заполнен адрес доставки' : 'В задаче не заполнен адрес доставки';
        }

        if ($shipper && $this->inn($shipper) === '') {
            $errors[] = 'У компании «' . $shipper->name . '» не заполнен ИНН';
        }
        if ($receiver && $this->inn($receiver) === '') {
            $errors[] = 'У получателя «' . $receiver->name . '» не заполнен ИНН';
        }
        if (!$receiver && $receiverContact) {
            $errors = array_merge($errors, $this->receiverContactErrors($receiverContact));
        }

        $redirectContact = $shipper
            ? $this->contactInfo('', $this->companyEmail($shipper), $this->phone($shipper))
            : [];
        if ($shipper && !count($redirectContact)) {
            $errors[] = 'У компании отгрузки «' . $shipper->name . '» не заполнены телефон и почта — они нужны для «Контакты лица переадресовки» в ТрН';
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

        $order = $this->orderFor($task);
        $document['СодИнфГО']['НомЗак'] = $order && $order->number !== null && $order->number !== '' ? (string) $order->number : (string) $task->id;
        $document['СодИнфГО']['ДатаЗак'] = $order && $order->date ? (string) $order->date : $document['СодИнфГО']['ДатаТрН'];

        $document['СодИнфГО']['СвПер'] = $this->party($carrier);

        if (count($redirectContact)) {
            $document['СодИнфГО']['УказГО'] = [
                'СвПА' => [
                    'ЛицоПА' => 'Грузоотправитель',
                    'СпосПерУкПА' => 'Электронное уведомление перевозчика о переадресовке',
                    'КонтПА' => $redirectContact,
                ],
            ];
        }

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

    protected function waybillReceiver(Task $task, ?Task $unloadingTask = null): array
    {
        $receiver = $unloadingTask ? $this->companyOf($unloadingTask, 'company_id') : null;
        $receiverContact = $unloadingTask ? $this->contactOf($unloadingTask) : null;
        if (!$receiver && !$receiverContact) {
            $receiver = $this->companyOf($task, 'company_id');
            $receiverContact = $this->contactOf($task);
        }

        return [$receiver, $receiverContact];
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

    protected function ourOrganization(): array
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

    protected function party(Company $company, ?Contact $contact = null): array
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

    protected function contactParty(Contact $contact): array
    {
        $inn = $this->contactInn($contact);
        $phone = $this->contactPhone($contact);
        $party = ['ИдСв' => ['СвФЛУч' => array_filter([
            'ИННФЛ' => strlen($inn) === 12 ? $inn : null,
            'ИныеСвед' => strlen($inn) === 12 ? null : $this->individualOtherInfo($contact, $phone),
            'ФИО' => $this->splitName($this->contactName($contact)),
        ])]];

        $contactInfo = $this->contactInfo($phone, $this->contactEmail($contact), '');
        if (count($contactInfo)) {
            $party['Контакт'] = $contactInfo;
        }

        return $party;
    }

    protected function individualOtherInfo(Contact $contact, string $phone): string
    {
        $text = 'Физическое лицо ' . $this->contactName($contact) . ($phone !== '' ? ', тел. ' . $phone : '');

        return mb_substr($text, 0, 255);
    }

    protected function receiverContactErrors(Contact $contact): array
    {
        $errors = [];
        $name = $this->contactName($contact);
        $parts = $this->splitName($name);
        if (empty($parts['Фамилия']) || empty($parts['Имя'])) {
            $errors[] = 'У контакта-получателя «' . ($name !== '' ? $name : '#' . $contact->id) . '» укажите полные ФИО (минимум фамилия и имя) — физлицо без ИНН идентифицируется по ФИО';
        }
        $inn = $this->contactInn($contact);
        if ($inn !== '' && strlen($inn) !== 12) {
            $errors[] = 'У контакта-получателя «' . $name . '» ИНН должен состоять из 12 цифр (или оставьте поле пустым)';
        }

        return $errors;
    }

    protected function contactInfo(string $contactPhone, string $email, string $companyPhone): array
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

    protected function contactCounterparty(?Contact $contact): array
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

    protected function contactOf(Task $task): ?Contact
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

    protected function contactName(Contact $contact): string
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

    protected function contactInn(Contact $contact): string
    {
        return preg_replace('/\D/', '', (string) $this->attr($contact, 'inn'));
    }

    protected function contactPhone(Contact $contact): string
    {
        foreach (['phones', 'phone', 'work_phone'] as $field) {
            $value = $this->phoneValue($this->attr($contact, $field));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function contactEmail(Contact $contact): string
    {
        foreach (['emails', 'email'] as $field) {
            $value = $this->phoneValue($this->attr($contact, $field));
            if ($value !== '' && str_contains($value, '@')) {
                return $value;
            }
        }

        return '';
    }

    protected function writeDocument(array $payload, array $addressedKeys): array
    {
        try {
            return $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => $payload]);
        } catch (SabyException $e) {
            $retry = false;
            foreach ($addressedKeys as $key) {
                if (isset($payload[$key]['Идентификатор'])) {
                    unset($payload[$key]['Идентификатор']);
                    $retry = true;
                }
            }
            if (!$retry) {
                throw $e;
            }
            $this->log('warning', 'write document with operator prefix failed, retry via roaming', ['error' => $e->getMessage()]);

            return $this->client->call('СБИС.ЗаписатьДокумент', ['Документ' => $payload]);
        }
    }

    protected function addressedCounterparty(Company $company): array
    {
        $party = $this->counterparty($company);
        $prefix = trim((string) $this->client->config()->param('edo_operator_prefix', self::EDO_OPERATOR_PREFIX));
        if ($prefix !== '') {
            $party['Идентификатор'] = $prefix;
        }

        return $party;
    }

    protected function counterparty(Company $company): array
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

    protected function driver(Task $task): ?array
    {
        $ids = Route::parseIdList($this->attr($task, 'employee_id'));
        $employeeId = count($ids) ? $ids[0] : null;

        if (!$employeeId && \Schema::hasTable('logistic_task_employee')) {
            $employeeId = \DB::table('logistic_task_employee')->where('logistic_task_id', $task->id)->value('employee_id');
        }

        return $employeeId ? $this->driverByEmployee((int) $employeeId) : null;
    }

    protected function driverByEmployee(int $employeeId): ?array
    {
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

    protected function vehicle(Route $route): ?array
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

    protected function fieldOptionLabel(string $entity, string $field, $value): string
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

    protected function cargo($tasks): array
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

    protected function products($task): array
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

    protected function isService($productId): bool
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

    protected function productAttr($productId, string $field, string $default): string
    {
        if (!$productId || !Schema::hasColumn('products', $field)) {
            return $default;
        }

        $value = \DB::table('products')->where('id', $productId)->value($field);
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }

    protected function loadingDateTime(Task $loadingTask, ?Route $route): string
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

    protected function taskAddress(Task $task, ?Company $receiver = null): string
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

    protected function resolveCoordinates(string $text): string
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

    protected function yandexReverse(float $lat, float $lng): string
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

    protected function isCoordinates(string $text): bool
    {
        return (bool) preg_match('/^\s*-?\d+\.\d+\s*[,\s]\s*-?\d+\.\d+\s*$/', $text);
    }

    protected function addressText($raw): string
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

    protected function companyOf($model, string $field): ?Company
    {
        $value = $this->attr($model, $field);
        $ids = Route::parseIdList($value);
        $id = count($ids) ? $ids[0] : null;

        return $id ? Company::find($id) : null;
    }

    protected function requisite(Company $company): ?Requisite
    {
        if (!Schema::hasTable('requisites')) {
            return null;
        }

        return Requisite::where('company_id', $company->id)->first();
    }

    protected function companyAddress(Company $company, ?Requisite $requisite): string
    {
        foreach ([$requisite->fact_address ?? null, $requisite->address ?? null, $company->address ?? null] as $value) {
            $text = $this->addressText($value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    protected function inn(Company $company): string
    {
        return $this->companyDigits($company, 'inn');
    }

    protected function kpp(Company $company): string
    {
        return $this->companyDigits($company, 'kpp');
    }

    protected function companyDigits(Company $company, string $field): string
    {
        $value = preg_replace('/\D/', '', (string) $this->attr($company, $field));
        if ($value !== '') {
            return $value;
        }
        $requisite = $this->requisite($company);

        return $requisite ? preg_replace('/\D/', '', (string) $requisite->{$field}) : '';
    }

    protected function companyEmail(Company $company): string
    {
        foreach (['emails', 'email'] as $field) {
            $value = $this->phoneValue($this->attr($company, $field));
            if ($value !== '' && str_contains($value, '@')) {
                return $value;
            }
        }

        return '';
    }

    protected function phone(Company $company): string
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

    protected function companyPhoneFields(): array
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

    protected function phoneValue($raw): string
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

    protected function splitName(?string $name): array
    {
        $clean = preg_replace('/^\s*(ИП|Индивидуальный предприниматель)\s+/iu', '', trim((string) $name));
        $parts = preg_split('/\s+/u', trim((string) $clean)) ?: [];

        return array_filter([
            'Фамилия' => $parts[0] ?? '',
            'Имя' => $parts[1] ?? '',
            'Отчество' => $parts[2] ?? '',
        ], fn ($v) => $v !== '');
    }

    protected function nextNumber(Task $task): string
    {
        $prefix = trim((string) $this->client->config()->param('number_prefix', ''));

        return $prefix !== '' ? $prefix . $task->id : (string) $task->id;
    }

    protected function attr($model, string $field)
    {
        if (!$model) {
            return null;
        }

        return array_key_exists($field, $model->getAttributes()) ? $model->getAttribute($field) : null;
    }

    protected function formatDate($value): string
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

    protected function number($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function format(float $value, int $precision = 2): string
    {
        $rounded = round($value, $precision);

        return rtrim(rtrim(number_format($rounded, $precision, '.', ''), '0'), '.') ?: '0';
    }
}
