<?php

namespace Modules\Bitrix24\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Bitrix24\Entities\Config;
use Modules\Bitrix24\Http\Controllers\Bitrix24Controller;

/**
 * Двусторонняя синхронизация сущностей deals/contacts/companies с Bitrix24.
 *
 * Портал-агностична (политика avixo): включается только там, где установлены
 * сущности (Schema::hasTable) и заполнен вебхук (bitrix24_config.webhook).
 *
 * Направления:
 *   - pull*: Bitrix24 -> портал (крон bitrix24:sync-entities + вебхук entity-hook);
 *   - push*: портал -> Bitrix24 (saved-хуки моделей Deal/Contact/Company);
 *   - changeStage: смена стадии из карточки сделки -> Bitrix24 + история событий.
 *
 * $muted гасит push-хуки моделей на время применения входящих изменений,
 * иначе pull зациклился бы на push.
 */
class B24EntitySync
{
    public static bool $muted = false;

    private string $base;
    private array $params;
    private ?array $b24DealFields = null;
    private ?array $b24ContactFields = null;
    private array $responsibleCache = [];

    private const CONTACT_TYPE_UF = 'UF_CRM_1785851130';

    private const DEFAULT_EXCLUDE_STAGES = ['NEW'];
    private const STAGE_PALETTE = [
        '#39a8ef', '#2fc6f6', '#55d0e0', '#47e4c2', '#ffda12',
        '#ffa900', '#ff5752', '#7bd500', '#00c38d', '#986cff',
    ];

    public static function make(): ?self
    {
        if (!self::ready()) {
            return null;
        }
        $svc = new self();
        $config = Config::first();
        $svc->base = $config->webhook;
        $svc->params = $config->getParams() ?: [];
        return $svc;
    }

    public static function ready(): bool
    {
        try {
            if (!Schema::hasTable('deals') || !Schema::hasTable('contacts')) {
                return false;
            }
            $config = Config::first();
            return $config && $config->webhook;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function b24(string $method, array $params = [])
    {
        return Http::timeout(20)->post($this->base . $method, $params)->collect();
    }

    private function b24All(string $method, array $params = [], int $max = 0): array
    {
        $result = [];
        $start = 0;
        $guard = 0;
        do {
            $resp = $this->b24($method, $params + ['start' => $start]);
            $batch = $resp['result'] ?? [];
            if (!is_array($batch)) {
                break;
            }
            $result = array_merge($result, $batch);
            $start = $resp['next'] ?? null;
            $guard++;
            if ($max > 0 && count($result) >= $max) {
                break;
            }
        } while ($start !== null && count($batch) && $guard < 200);

        return $result;
    }

    /**
     * crm.batch: до 50 команд за один HTTP-запрос. Возвращает результаты,
     * ключи — из $cmd; отсутствующий ключ = команда не выполнилась.
     */
    private function b24Batch(array $cmd): array
    {
        $out = [];
        foreach (array_chunk($cmd, 50, true) as $chunk) {
            try {
                $resp = $this->b24('batch', ['halt' => 0, 'cmd' => $chunk]);
                foreach (($resp['result']['result'] ?? []) as $key => $value) {
                    $out[$key] = $value;
                }
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('entity-sync: batch failed', ['error' => $e->getMessage()]);
            }
        }
        return $out;
    }

    private function categoryId(): int
    {
        return (int) ($this->params['category_id'] ?? 0);
    }

    private function excludedStages(): array
    {
        $ex = $this->params['exclude_stages'] ?? self::DEFAULT_EXCLUDE_STAGES;
        return is_array($ex) ? $ex : [$ex];
    }

    // ---------------------------------------------------------------- stages

    public function syncStages(): array
    {
        $resp = $this->b24('crm.dealcategory.stage.list', ['id' => $this->categoryId()]);
        $items = $resp['result'] ?? [];
        if (!is_array($items) || !count($items)) {
            return $this->stageOptions();
        }

        $meta = $this->stageMeta();

        $options = [];
        foreach (array_values($items) as $i => $st) {
            $statusId = (string) ($st['STATUS_ID'] ?? '');
            $color = (string) (($st['COLOR'] ?? '') ?: ($meta[$statusId]['color'] ?? ''));
            $option = [
                'value' => $statusId,
                'label' => (string) ($st['NAME'] ?? ''),
                'color' => $color !== '' ? $color : self::STAGE_PALETTE[$i % count(self::STAGE_PALETTE)],
            ];
            $semantics = strtoupper((string) ($meta[$statusId]['semantics'] ?? ''));
            if (in_array($semantics, ['S', 'SUCCESS'], true)) {
                $option['semantics'] = 'success';
            } elseif (in_array($semantics, ['F', 'FAILURE', 'FAIL'], true)) {
                $option['semantics'] = 'failure';
            }
            $options[] = $option;
        }

        $row = $this->stageFieldRow();
        if ($row) {
            $details = json_decode($row->details ?? '', true) ?: [];
            if (($details['options'] ?? null) != $options) {
                $details['options'] = $options;
                DB::table('data_rows')->where('id', $row->id)->update([
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ]);
                try {
                    \App\Models\Settings::clear_cache();
                } catch (\Throwable $e) {
                }
            }
        }

        return $options;
    }

    private function stageMeta(): array
    {
        $categoryId = $this->categoryId();
        $entityId = $categoryId > 0 ? 'DEAL_STAGE_' . $categoryId : 'DEAL_STAGE';
        $meta = [];
        try {
            $items = $this->b24All('crm.status.list', ['filter' => ['ENTITY_ID' => $entityId]]);
            foreach ($items as $st) {
                $statusId = (string) ($st['STATUS_ID'] ?? '');
                if ($statusId === '') {
                    continue;
                }
                $meta[$statusId] = [
                    'color' => (string) ($st['COLOR'] ?? ''),
                    'semantics' => (string) ($st['SEMANTICS'] ?? ($st['EXTRA']['SEMANTICS'] ?? '')),
                ];
            }
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('entity-sync: stage colors failed', ['error' => $e->getMessage()]);
        }
        return $meta;
    }

    public function stageOptions(): array
    {
        $row = $this->stageFieldRow();
        $details = $row ? (json_decode($row->details ?? '', true) ?: []) : [];
        $options = $details['options'] ?? [];
        if (!count($options)) {
            $options = $this->syncStages();
        }
        return $options;
    }

    private function stageFieldRow()
    {
        return DB::table('data_rows')
            ->join('data_types', 'data_rows.data_type_id', '=', 'data_types.id')
            ->where('data_types.slug', 'deals')
            ->where('data_rows.field', 'stage')
            ->select('data_rows.id', 'data_rows.details')
            ->first();
    }

    public function changeStage(Deal $deal, string $stage, $userId = null): array
    {
        $options = $this->stageOptions();
        $new = collect($options)->firstWhere('value', $stage);
        if (!$new) {
            throw new \InvalidArgumentException('Неизвестная стадия: ' . $stage);
        }
        if (!$deal->b24_id) {
            throw new \RuntimeException('У сделки нет привязки к Bitrix24 (b24_id)');
        }

        $resp = $this->b24('crm.deal.update', [
            'id' => $deal->b24_id,
            'fields' => ['STAGE_ID' => $stage],
        ]);
        if (empty($resp['result'])) {
            throw new \RuntimeException('Bitrix24 отклонил смену стадии: ' . json_encode($resp->toArray(), JSON_UNESCAPED_UNICODE));
        }

        $old = collect($options)->firstWhere('value', $deal->stage);
        $oldLabel = $old['label'] ?? ($deal->stage ?: '—');

        self::$muted = true;
        try {
            $deal->stage = $stage;
            $deal->saveQuietly();
        } finally {
            self::$muted = false;
        }

        $history = new History([
            'entity'    => 'deals',
            'entity_id' => $deal->id,
            'user_id'   => $userId,
            'field'     => 'stage',
            'old_value' => $oldLabel,
            'new_value' => $new['label'],
            'text'      => 'Стадия изменена: ' . $oldLabel . ' → ' . $new['label'],
            'event'     => 'STAGE_CHANGED',
            'color'     => $new['color'] ?? '#000',
        ]);
        $history->saveQuietly();

        return $new;
    }

    // ------------------------------------------------------------------ pull

    public function fullSync(?string $since = null): array
    {
        $stages = $this->syncStages();
        $deals = $this->pullDeals($since);
        $contacts = $this->pullContacts($since);
        return ['stages' => count($stages), 'deals' => $deals['count'], 'contacts' => $contacts['count']];
    }

    /**
     * Инкрементальный прогон для крона/очереди: не больше $chunk записей на
     * поток за раз. Курсоры — b24_deals_synced_at / b24_contacts_synced_at в
     * settings (двигаются по DATE_MODIFY обработанных записей, при недоборе
     * чанка — на время старта прогона). Первый прогон без курсоров — init:
     * только стадии, метки ставятся на текущий момент.
     */
    public function runIncremental(int $chunk = 200): array
    {
        $read = fn (string $type) => DB::table('settings')->where('type', $type)->value('value');
        $write = function (string $type, string $value) {
            DB::table('settings')->updateOrInsert(
                ['type' => $type, 'entity' => null, 'user_id' => null],
                ['key' => $type, 'value' => $value]
            );
        };

        $legacy = $read('b24_entities_synced_at');
        $sinceDeals = $read('b24_deals_synced_at') ?: $legacy;
        $sinceContacts = $read('b24_contacts_synced_at') ?: $legacy;
        $started = now()->format('Y-m-d\TH:i:sP');

        if (!$sinceDeals && !$sinceContacts) {
            $stages = $this->syncStages();
            $write('b24_entities_synced_at', $started);
            $write('b24_deals_synced_at', $started);
            $write('b24_contacts_synced_at', $started);
            return ['init' => true, 'stages' => count($stages), 'deals' => 0, 'contacts' => 0, 'more' => false];
        }

        $this->syncStages();

        $deals = $this->pullDeals($sinceDeals, $chunk);
        $write('b24_deals_synced_at', ($deals['more'] && $deals['last_modify']) ? $deals['last_modify'] : $started);

        $contacts = $this->pullContacts($sinceContacts, $chunk);
        $write('b24_contacts_synced_at', ($contacts['more'] && $contacts['last_modify']) ? $contacts['last_modify'] : $started);

        return [
            'init' => false,
            'deals' => $deals['count'],
            'contacts' => $contacts['count'],
            'more' => $deals['more'] || $contacts['more'],
        ];
    }

    public function pullDeals(?string $since = null, int $limit = 0): array
    {
        $filter = [
            'CATEGORY_ID' => $this->categoryId(),
            '!STAGE_ID'   => $this->excludedStages(),
        ];
        if ($since) {
            $filter['>DATE_MODIFY'] = $since;
        }
        $deals = $this->b24All('crm.deal.list', [
            'filter' => $filter,
            'select' => ['*', 'UF_*'],
            'order'  => $since ? ['DATE_MODIFY' => 'ASC'] : ['ID' => 'ASC'],
        ], $limit);
        $more = $limit > 0 && count($deals) >= $limit;
        if ($limit > 0) {
            $deals = array_slice($deals, 0, $limit);
        }

        $count = 0;
        $lastModify = null;
        foreach (array_chunk($deals, 16) as $chunkDeals) {
            $pre = $this->prefetchDealData($chunkDeals);
            foreach ($chunkDeals as $deal) {
                try {
                    $this->upsertDealFromB24($deal, $pre[$deal['ID']] ?? null);
                    $count++;
                    $lastModify = $deal['DATE_MODIFY'] ?? $lastModify;
                } catch (\Throwable $e) {
                    Log::channel('bitrix24')->warning('entity-sync: deal upsert failed', [
                        'deal_id' => $deal['ID'] ?? null,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }
        return ['count' => $count, 'last_modify' => $lastModify, 'more' => $more];
    }

    /**
     * Пакетная предзагрузка данных сделок (crm.batch): товары, контакты,
     * счета — 3 команды на сделку вместо 3 отдельных HTTP-запросов.
     */
    private function prefetchDealData(array $deals): array
    {
        $cmd = [];
        foreach ($deals as $deal) {
            $id = $deal['ID'];
            $cmd['rows_' . $id] = 'crm.deal.productrows.get?id=' . $id;
            $cmd['contacts_' . $id] = 'crm.deal.contact.items.get?id=' . $id;
            $cmd['invoices_' . $id] = 'crm.invoice.list?filter[UF_DEAL_ID]=' . $id;
            if ($this->bankRequisitesReady()) {
                $cmd['reqlink_' . $id] = 'crm.requisite.link.list?filter[ENTITY_TYPE_ID]=' . self::DEAL_ENTITY_TYPE . '&filter[ENTITY_ID]=' . $id;
            }
        }
        $res = $this->b24Batch($cmd);

        $pre = [];
        foreach ($deals as $deal) {
            $id = $deal['ID'];
            $item = [
                'product_rows'  => (isset($res['rows_' . $id]) && is_array($res['rows_' . $id])) ? $res['rows_' . $id] : null,
                'contact_items' => (isset($res['contacts_' . $id]) && is_array($res['contacts_' . $id])) ? $res['contacts_' . $id] : null,
            ];
            if (isset($res['invoices_' . $id]) && is_array($res['invoices_' . $id])) {
                $invoices = [];
                $rows = [];
                foreach ($res['invoices_' . $id] as $invoice) {
                    if (($invoice['UF_DEAL_ID'] ?? null) == $id) {
                        $invoices[] = $invoice['ACCOUNT_NUMBER'] ?? $invoice['ID'];
                        $rows[] = $invoice;
                    }
                }
                $item['invoices'] = $invoices;
                $item['invoice_rows'] = $rows;
            }
            if (isset($res['reqlink_' . $id]) && is_array($res['reqlink_' . $id])) {
                $item['requisite_link'] = $this->firstRow($res['reqlink_' . $id]);
            }
            $pre[$id] = $item;
        }
        return $pre;
    }

    public function pullContacts(?string $since = null, int $limit = 0): array
    {
        $filter = [];
        if ($since) {
            $filter['>DATE_MODIFY'] = $since;
        }
        $contacts = $this->b24All('crm.contact.list', [
            'filter' => $filter,
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PHONE', 'DATE_MODIFY', 'ASSIGNED_BY_ID', self::CONTACT_TYPE_UF],
            'order'  => $since ? ['DATE_MODIFY' => 'ASC'] : ['ID' => 'ASC'],
        ], $limit);
        $more = $limit > 0 && count($contacts) >= $limit;
        if ($limit > 0) {
            $contacts = array_slice($contacts, 0, $limit);
        }

        $count = 0;
        $lastModify = null;
        foreach (array_chunk($contacts, 50) as $chunkContacts) {
            $cmd = [];
            foreach ($chunkContacts as $contact) {
                $cmd['companies_' . $contact['ID']] = 'crm.contact.company.items.get?id=' . $contact['ID'];
            }
            $res = $this->b24Batch($cmd);
            foreach ($chunkContacts as $contact) {
                try {
                    $companyItems = (isset($res['companies_' . $contact['ID']]) && is_array($res['companies_' . $contact['ID']]))
                        ? $res['companies_' . $contact['ID']]
                        : null;
                    $this->upsertContactFromB24($contact, $companyItems);
                    $count++;
                    $lastModify = $contact['DATE_MODIFY'] ?? $lastModify;
                } catch (\Throwable $e) {
                    Log::channel('bitrix24')->warning('entity-sync: contact upsert failed', [
                        'contact_id' => $contact['ID'] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }
        return ['count' => $count, 'last_modify' => $lastModify, 'more' => $more];
    }

    public function pullDealById($dealId): ?Deal
    {
        $resp = $this->b24('crm.deal.get', ['id' => $dealId]);
        $deal = $resp['result'] ?? null;
        if (!$deal) {
            return null;
        }
        if ((int) ($deal['CATEGORY_ID'] ?? 0) !== $this->categoryId()
            || in_array($deal['STAGE_ID'] ?? '', $this->excludedStages(), true)) {
            return null;
        }
        return $this->upsertDealFromB24($deal);
    }

    public function pullContactById($contactId): ?Contact
    {
        $resp = $this->b24('crm.contact.get', ['id' => $contactId]);
        $contact = $resp['result'] ?? null;
        return $contact ? $this->upsertContactFromB24($contact) : null;
    }

    public function pullCompanyById($companyId): ?Company
    {
        $resp = $this->b24('crm.company.get', ['id' => $companyId]);
        $company = $resp['result'] ?? null;
        return $company ? $this->upsertCompanyFromB24($company) : null;
    }

    public function pullCompanyByRequisiteId($requisiteId): ?Company
    {
        $resp = $this->b24('crm.requisite.get', ['id' => $requisiteId]);
        $requisite = $resp['result'] ?? null;
        if (!is_array($requisite) || (int) ($requisite['ENTITY_TYPE_ID'] ?? 0) !== 4 || empty($requisite['ENTITY_ID'])) {
            return null;
        }
        return $this->pullCompanyById($requisite['ENTITY_ID']);
    }

    public function pullCompanyByBankDetailId($bankDetailId): ?Company
    {
        $resp = $this->b24('crm.requisite.bankdetail.get', ['id' => $bankDetailId]);
        $detail = $resp['result'] ?? null;
        return is_array($detail) && !empty($detail['ENTITY_ID']) ? $this->pullCompanyByRequisiteId($detail['ENTITY_ID']) : null;
    }

    // --------------------------------------------------------------- upserts

    public function upsertDealFromB24(array $deal, ?array $pre = null): Deal
    {
        $dealId = $deal['ID'];

        self::$muted = true;
        try {
            $model = Deal::withTrashed()->where('b24_id', (string) $dealId)->first() ?: new Deal();
            $isNew = !$model->exists;
            if (!$isNew && $model->trashed()) {
                $model->deleted_at = null;
            }

            $model->b24_id = (string) $dealId;
            $model->stage = (string) ($deal['STAGE_ID'] ?? '');
            $crmLink = 'https://crm6.ru/crm/deal/details/' . $dealId . '/';
            $model->crm_link = $crmLink;
            $model->name = json_encode([
                'value'         => (string) $dealId,
                'external_link' => $crmLink,
            ], JSON_UNESCAPED_UNICODE);

            $model->contact = $deal['UF_CRM_1642670804'] ?? $model->contact;

            $addrText = $deal['UF_CRM_1528885851543'] ?? '';
            $coords = [];
            if (!empty($deal['UF_CRM_1741758491'])) {
                $parts = explode(',', $deal['UF_CRM_1741758491']);
                if (count($parts) >= 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                    $coords = [trim($parts[0]), trim($parts[1])];
                }
            }
            $model->address = json_encode(['text' => $addrText, 'coords' => $coords], JSON_UNESCAPED_UNICODE);

            [$products, $deliveryPrice, $allWeight] = $this->fetchDealProducts($dealId, $pre['product_rows'] ?? null);
            if ($products) {
                $model->products = json_encode($products, JSON_UNESCAPED_UNICODE);
            }
            $model->weight = $allWeight;
            $model->time = Bitrix24Controller::normalizeTimeWindow($deal['UF_CRM_1632832553'] ?? null);

            if (!empty($deal['UF_CRM_1738582841'])) {
                try {
                    $dt = new \DateTime($deal['UF_CRM_1738582841']);
                    $dt->setTimezone(new \DateTimeZone(config('app.timezone', 'Europe/Moscow')));
                    $model->delivery_date = $dt->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }

            if (!empty($deal['UF_CRM_1623418181538'])) {
                $model->phone = Bitrix24Controller::phoneStoreValue('deals', $deal['UF_CRM_1623418181538']);
            }

            if ($deliveryPrice > 0) {
                $model->delivery_price = $deliveryPrice;
            } elseif (!empty($deal['UF_CRM_1633508830'])) {
                $model->delivery_price = $deal['UF_CRM_1633508830'];
            }

            $model->comment = $deal['UF_CRM_5EAFC3D4C5F76'] ?? $model->comment;
            $model->pallets_count = $deal['UF_CRM_1696596978695'] ?? $model->pallets_count;

            $rawUnloading = $deal['UF_CRM_1762411084'] ?? null;
            $carReqs = [];
            foreach ((is_array($rawUnloading) ? $rawUnloading : [$rawUnloading]) as $val) {
                if ($val === '' || $val === null) {
                    continue;
                }
                $label = $this->b24EnumLabel('UF_CRM_1762411084', $val);
                $local = $this->localOptionValueByLabel('deals', 'car_requirements', $label);
                if ($local !== null) {
                    $carReqs[] = (int) $local;
                }
            }
            if ($carReqs) {
                $model->car_requirements = json_encode($carReqs);
            }

            if (Schema::hasColumn('deals', 'car_type')) {
                $rawCarType = $deal['UF_CRM_1625083610453'] ?? null;
                $ctLabel = ($rawCarType === '' || $rawCarType === null) ? null : $this->b24EnumLabel('UF_CRM_1625083610453', $rawCarType);
                $localCt = $ctLabel === null ? null : $this->localOptionValueByLabel('deals', 'car_type', $ctLabel);
                if ($localCt !== null) {
                    $model->car_type = (int) $localCt;
                }
            }

            if (Schema::hasColumn('deals', 'user_id')) {
                $responsibleId = $this->resolveResponsible($deal['ASSIGNED_BY_ID'] ?? null);
                if ($responsibleId) {
                    $model->user_id = $responsibleId;
                }
            }

            $invoices = $pre !== null && array_key_exists('invoices', $pre)
                ? $pre['invoices']
                : Bitrix24Controller::fetchDealInvoiceNumbers($this->base, $dealId);
            $pay = Bitrix24Controller::buildPaymentFields($invoices, $deal['OPPORTUNITY'] ?? 0);
            $model->payment_type = $pay['payment_type'];
            $model->payment = $pay['payment'];

            if ($isNew) {
                $model->save();
                $this->writeSyncCreatedHistory('deals', $model->id);
            } elseif (count($model->getDirty())) {
                $this->writeSyncFieldHistory('deals', $model->id, $model->getDirty());
                $model->save();
            }

            $this->linkDealRelations($model, $deal, $pre['contact_items'] ?? null);

            $link = $pre !== null && array_key_exists('requisite_link', $pre)
                ? $pre['requisite_link']
                : $this->fetchRequisiteLink(self::DEAL_ENTITY_TYPE, $dealId);
            $this->applyDealRequisiteLink($model, $link);
            $this->syncDealInvoices($model, $dealId, $pre['invoice_rows'] ?? null);

            return $model;
        } finally {
            self::$muted = false;
        }
    }

    // --------------------------------------------------------------- invoices

    public const DEAL_ENTITY_TYPE = 2;
    public const INVOICE_ENTITY_TYPE = 5;

    private ?bool $invoicesReady = null;
    private array $bankDetailCache = [];

    private function paymentInvoicesReady(): bool
    {
        if ($this->invoicesReady === null) {
            $this->invoicesReady = Schema::hasTable('payment_invoices')
                && Schema::hasColumn('payment_invoices', 'b24_id')
                && DB::table('data_types')->where('slug', 'payment_invoices')->exists();
        }
        return $this->invoicesReady;
    }

    private function firstRow($rows): ?array
    {
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            if (is_array($row)) {
                return $row;
            }
        }
        return null;
    }

    private function fetchRequisiteLink(int $entityTypeId, $entityId): ?array
    {
        if (!$this->bankRequisitesReady() || !$entityId) {
            return null;
        }
        try {
            $resp = $this->b24('crm.requisite.link.list', [
                'filter' => ['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId],
            ]);
            return $this->firstRow($resp['result'] ?? null);
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('entity-sync: requisite link fetch failed', [
                'entity_type' => $entityTypeId, 'entity_id' => $entityId, 'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function bankRequisiteLocalId(?array $link): ?int
    {
        if (!$link || !$this->bankRequisitesReady()) {
            return null;
        }
        foreach (['MC_BANK_DETAIL_ID', 'BANK_DETAIL_ID'] as $key) {
            $b24Id = (string) ($link[$key] ?? '');
            if ($b24Id === '' || $b24Id === '0') {
                continue;
            }
            $localId = $this->resolveBankDetail($b24Id);
            if ($localId) {
                return $localId;
            }
        }
        return null;
    }

    private function resolveBankDetail(string $b24Id): ?int
    {
        if (array_key_exists($b24Id, $this->bankDetailCache)) {
            return $this->bankDetailCache[$b24Id];
        }

        $localId = \App\Models\BankRequisite::where('b24_id', $b24Id)->value('id');
        if ($localId) {
            return $this->bankDetailCache[$b24Id] = (int) $localId;
        }

        try {
            $detail = $this->b24('crm.requisite.bankdetail.get', ['id' => $b24Id])['result'] ?? null;
            if (!is_array($detail) || empty($detail['ENTITY_ID'])) {
                return $this->bankDetailCache[$b24Id] = null;
            }
            $requisite = $this->b24('crm.requisite.get', ['id' => $detail['ENTITY_ID']])['result'] ?? null;
            if (!is_array($requisite) || (int) ($requisite['ENTITY_TYPE_ID'] ?? 0) !== 4 || empty($requisite['ENTITY_ID'])) {
                return $this->bankDetailCache[$b24Id] = null;
            }
            $this->pullCompanyById($requisite['ENTITY_ID']);
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('entity-sync: bank detail resolve failed', ['bank_detail_id' => $b24Id, 'error' => $e->getMessage()]);
            return $this->bankDetailCache[$b24Id] = null;
        }

        $localId = \App\Models\BankRequisite::where('b24_id', $b24Id)->value('id');
        return $this->bankDetailCache[$b24Id] = ($localId ? (int) $localId : null);
    }

    private function applyDealRequisiteLink(Deal $model, ?array $link): void
    {
        if (!Schema::hasColumn('deals', 'bank_requisite_id')) {
            return;
        }
        $localId = $this->bankRequisiteLocalId($link);
        if (!$localId) {
            return;
        }
        $current = json_decode((string) $model->bank_requisite_id, true);
        $current = is_array($current) ? array_values(array_map('intval', array_filter($current, 'is_numeric'))) : [];
        if ($current === [$localId]) {
            return;
        }
        $this->writeSyncFieldHistory('deals', $model->id, ['bank_requisite_id' => [$localId]]);
        $model->bank_requisite_id = json_encode([$localId]);
        $model->saveQuietly();
    }

    private function syncDealInvoices(Deal $model, $b24DealId, ?array $rows): void
    {
        if (!$this->paymentInvoicesReady()) {
            return;
        }
        if ($rows === null) {
            $rows = [];
            try {
                $resp = $this->b24('crm.invoice.list', ['filter' => ['UF_DEAL_ID' => $b24DealId]]);
                foreach (($resp['result'] ?? []) as $row) {
                    if (is_array($row) && ($row['UF_DEAL_ID'] ?? null) == $b24DealId) {
                        $rows[] = $row;
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('entity-sync: invoice list failed', ['deal_id' => $b24DealId, 'error' => $e->getMessage()]);
                return;
            }
        }

        $b24Ids = [];
        $cmd = [];
        foreach ($rows as $row) {
            $id = (string) ($row['ID'] ?? '');
            if ($id === '') {
                continue;
            }
            $b24Ids[] = $id;
            $cmd['inv_' . $id] = 'crm.invoice.get?id=' . $id;
            if ($this->bankRequisitesReady()) {
                $cmd['link_' . $id] = 'crm.requisite.link.list?filter[ENTITY_TYPE_ID]=' . self::INVOICE_ENTITY_TYPE . '&filter[ENTITY_ID]=' . $id;
            }
        }
        $res = count($cmd) ? $this->b24Batch($cmd) : [];

        foreach ($rows as $row) {
            $id = (string) ($row['ID'] ?? '');
            if ($id === '') {
                continue;
            }
            try {
                $full = isset($res['inv_' . $id]) && is_array($res['inv_' . $id]) ? $res['inv_' . $id] : $row;
                $link = array_key_exists('link_' . $id, $res) ? $this->firstRow($res['link_' . $id]) : $this->fetchRequisiteLink(self::INVOICE_ENTITY_TYPE, $id);
                $this->upsertInvoiceFromB24($full, $model, $link);
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('entity-sync: invoice upsert failed', ['invoice_id' => $id, 'deal_id' => $b24DealId, 'error' => $e->getMessage()]);
            }
        }

        if (\App\Models\ObjectRelation::ready()) {
            $linkedIds = \App\Models\ObjectRelation::where('source_slug', 'deals')
                ->where('source_id', $model->id)
                ->where('target_slug', 'payment_invoices')
                ->pluck('target_id');
            if ($linkedIds->count()) {
                $stale = \App\Models\PaymentInvoice::whereIn('id', $linkedIds)
                    ->whereNotNull('b24_id')
                    ->where('b24_id', '!=', '')
                    ->when(count($b24Ids), fn ($q) => $q->whereNotIn('b24_id', $b24Ids))
                    ->get();
                foreach ($stale as $invoice) {
                    $invoice->delete();
                }
            }
        }
    }

    public function upsertInvoiceFromB24(array $inv, ?Deal $deal, ?array $link = null): ?\App\Models\PaymentInvoice
    {
        if (!$this->paymentInvoicesReady() || empty($inv['ID'])) {
            return null;
        }
        $b24Id = (string) $inv['ID'];

        $model = \App\Models\PaymentInvoice::withTrashed()->where('b24_id', $b24Id)->first() ?: new \App\Models\PaymentInvoice();
        $isNew = !$model->exists;
        if (!$isNew && $model->trashed()) {
            $model->deleted_at = null;
        }
        $model->b24_id = $b24Id;

        $number = trim((string) ($inv['ACCOUNT_NUMBER'] ?? ''));
        if ($number === '') {
            $number = $b24Id;
        }
        if (Schema::hasColumn('payment_invoices', 'number')) {
            $model->number = $number;
        }

        $billDate = null;
        if (!empty($inv['DATE_BILL'])) {
            try {
                $billDate = \Carbon\Carbon::parse($inv['DATE_BILL'])->setTimezone(config('app.timezone', 'Europe/Moscow'));
            } catch (\Throwable $e) {
                $billDate = null;
            }
        }
        if ($isNew) {
            $model->created_at = $billDate ?: now();
        } elseif ($billDate && $model->created_at && !$model->created_at->isSameDay($billDate)) {
            $model->created_at = $billDate;
        }

        $dateText = ($model->created_at ?: now())->format('d.m.Y');
        $autoName = 'Счет на оплату № ' . $number . ' от ' . $dateText;
        $currentName = $this->plainText($model->name);
        if ($isNew || $currentName === '' || preg_match('/^Счет на оплату № .+ от \d{2}\.\d{2}\.\d{4}$/u', $currentName)) {
            $model->name = $autoName;
        }

        $price = (float) ($inv['PRICE'] ?? 0);
        $model->sum = $price > 0 ? rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.') : null;

        $companyIds = [];
        if (!empty($inv['UF_COMPANY_ID'])) {
            $localId = $this->localIdByB24('companies', $inv['UF_COMPANY_ID']);
            if (!$localId) {
                $localId = $this->pullCompanyById($inv['UF_COMPANY_ID'])?->id;
            }
            if ($localId) {
                $companyIds[] = (int) $localId;
            }
        }
        if (!count($companyIds) && $deal) {
            $dealCompanies = json_decode((string) $deal->company_id, true);
            $companyIds = is_array($dealCompanies) ? array_values(array_map('intval', array_filter($dealCompanies, 'is_numeric'))) : [];
        }
        $model->company_id = count($companyIds) ? json_encode($companyIds) : null;

        $responsibleId = $this->resolveResponsible($inv['RESPONSIBLE_ID'] ?? null);
        $model->user_id = $responsibleId ?: ($model->user_id ?: ($deal?->user_id ?: 1));

        [$products] = $this->fetchDealProducts($b24Id, is_array($inv['PRODUCT_ROWS'] ?? null) ? $inv['PRODUCT_ROWS'] : []);
        if (count($products)) {
            $model->products = json_encode($products, JSON_UNESCAPED_UNICODE);
        }

        if (Schema::hasColumn('payment_invoices', 'bank_requisite_id')) {
            $bankId = $this->bankRequisiteLocalId($link);
            if ($bankId) {
                $model->bank_requisite_id = json_encode([$bankId]);
            } elseif ($isNew && $deal && Schema::hasColumn('deals', 'bank_requisite_id') && $deal->bank_requisite_id) {
                $model->bank_requisite_id = $deal->bank_requisite_id;
            }
        }

        $changed = false;
        if ($isNew) {
            $model->saveQuietly();
            $this->writeSyncCreatedHistory('payment_invoices', $model->id);
            $changed = true;
        } elseif ($model->isDirty()) {
            $this->writeSyncFieldHistory('payment_invoices', $model->id, $model->getDirty());
            $model->saveQuietly();
            $changed = true;
        }

        if ($deal) {
            \App\Models\ObjectRelation::link('deals', $deal->id, 'payment_invoices', $model->id);
        }

        if ($changed || !$model->photo) {
            \App\Services\SaleDocumentService::regenerate('payment_invoices', (int) $model->id);
        }

        return $model;
    }

    private function plainText($value): string
    {
        $value = (string) $value;
        if ($value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = (string) ($decoded['value'] ?? reset($decoded));
            }
        }
        return trim($value);
    }

    public function pullInvoiceById($invoiceId): ?\App\Models\PaymentInvoice
    {
        if (!$this->paymentInvoicesReady()) {
            return null;
        }
        $resp = $this->b24('crm.invoice.get', ['id' => $invoiceId]);
        $inv = $resp['result'] ?? null;
        if (!is_array($inv) || empty($inv['ID'])) {
            return null;
        }

        $deal = null;
        if (!empty($inv['UF_DEAL_ID'])) {
            $localId = $this->localIdByB24('deals', $inv['UF_DEAL_ID']);
            $deal = $localId ? Deal::find($localId) : $this->pullDealById($inv['UF_DEAL_ID']);
        }

        self::$muted = true;
        try {
            return $this->upsertInvoiceFromB24($inv, $deal, $this->fetchRequisiteLink(self::INVOICE_ENTITY_TYPE, $inv['ID']));
        } finally {
            self::$muted = false;
        }
    }

    public function deleteInvoiceByB24Id($invoiceId): void
    {
        if (!$this->paymentInvoicesReady()) {
            return;
        }
        \App\Models\PaymentInvoice::where('b24_id', (string) $invoiceId)->first()?->delete();
    }

    public function backfillDealInvoices(?callable $progress = null): array
    {
        $stat = ['deals' => 0, 'invoices' => 0, 'failed' => 0];
        if (!$this->paymentInvoicesReady() || !Schema::hasColumn('deals', 'b24_id')) {
            $stat['skipped'] = true;
            return $stat;
        }

        Deal::whereNotNull('b24_id')->where('b24_id', '!=', '')->orderBy('id')->chunkById(50, function ($deals) use (&$stat, $progress) {
            $cmd = [];
            foreach ($deals as $deal) {
                $cmd['invoices_' . $deal->b24_id] = 'crm.invoice.list?filter[UF_DEAL_ID]=' . $deal->b24_id;
                if ($this->bankRequisitesReady()) {
                    $cmd['reqlink_' . $deal->b24_id] = 'crm.requisite.link.list?filter[ENTITY_TYPE_ID]=' . self::DEAL_ENTITY_TYPE . '&filter[ENTITY_ID]=' . $deal->b24_id;
                }
            }
            $res = $this->b24Batch($cmd);

            foreach ($deals as $deal) {
                self::$muted = true;
                try {
                    $rows = [];
                    foreach ((isset($res['invoices_' . $deal->b24_id]) && is_array($res['invoices_' . $deal->b24_id]) ? $res['invoices_' . $deal->b24_id] : []) as $row) {
                        if (is_array($row) && ($row['UF_DEAL_ID'] ?? null) == $deal->b24_id) {
                            $rows[] = $row;
                        }
                    }
                    if (array_key_exists('reqlink_' . $deal->b24_id, $res)) {
                        $this->applyDealRequisiteLink($deal, $this->firstRow($res['reqlink_' . $deal->b24_id]));
                    }
                    $this->syncDealInvoices($deal, $deal->b24_id, $rows);
                    $stat['invoices'] += count($rows);
                } catch (\Throwable $e) {
                    $stat['failed']++;
                    Log::channel('bitrix24')->warning('entity-sync: deal invoices backfill failed', ['deal_id' => $deal->id, 'error' => $e->getMessage()]);
                } finally {
                    self::$muted = false;
                }
                $stat['deals']++;
            }
            if ($progress) {
                $progress($stat);
            }
        });

        return $stat;
    }

    private function ourOrganizationInn(): string
    {
        if (!Schema::hasTable('requisites')) {
            return '';
        }
        $inn = DB::table('requisites')
            ->whereNull('deleted_at')
            ->orderByRaw('choosed_at IS NULL')
            ->orderByDesc('choosed_at')
            ->orderBy('id')
            ->value('inn');
        return preg_replace('/\D/', '', (string) $inn);
    }

    private function pushDealRequisiteLink(Deal $deal): void
    {
        if (!$deal->b24_id || !$this->bankRequisitesReady()) {
            return;
        }
        $ids = json_decode((string) $deal->bank_requisite_id, true);
        $bankId = null;
        foreach (is_array($ids) ? $ids : [] as $id) {
            if (is_numeric($id)) {
                $bankId = (int) $id;
                break;
            }
        }
        if (!$bankId) {
            return;
        }
        $bank = \App\Models\BankRequisite::find($bankId);
        if (!$bank || !$bank->b24_id) {
            return;
        }
        $detail = $this->b24('crm.requisite.bankdetail.get', ['id' => $bank->b24_id])['result'] ?? null;
        if (!is_array($detail) || empty($detail['ENTITY_ID'])) {
            return;
        }

        $orgInn = $this->ourOrganizationInn();
        $companyInn = '';
        if ($orgInn !== '' && Schema::hasColumn('companies', 'inn')) {
            $companyInn = preg_replace('/\D/', '', (string) DB::table('companies')->where('id', $bank->company_id)->value('inn'));
        }
        $isOurs = $orgInn !== '' && $companyInn === $orgInn;

        $current = $this->fetchRequisiteLink(self::DEAL_ENTITY_TYPE, $deal->b24_id) ?: [];
        $fields = [
            'ENTITY_TYPE_ID' => self::DEAL_ENTITY_TYPE,
            'ENTITY_ID' => $deal->b24_id,
            'REQUISITE_ID' => $current['REQUISITE_ID'] ?? 0,
            'BANK_DETAIL_ID' => $current['BANK_DETAIL_ID'] ?? 0,
            'MC_REQUISITE_ID' => $current['MC_REQUISITE_ID'] ?? 0,
            'MC_BANK_DETAIL_ID' => $current['MC_BANK_DETAIL_ID'] ?? 0,
        ];
        if ($isOurs) {
            $fields['MC_REQUISITE_ID'] = $detail['ENTITY_ID'];
            $fields['MC_BANK_DETAIL_ID'] = $bank->b24_id;
        } else {
            $fields['REQUISITE_ID'] = $detail['ENTITY_ID'];
            $fields['BANK_DETAIL_ID'] = $bank->b24_id;
        }

        $resp = $this->b24('crm.requisite.link.register', ['fields' => $fields]);
        Log::channel('bitrix24')->info('entity-sync: deal requisite link pushed', [
            'deal_id' => $deal->id, 'b24_id' => $deal->b24_id, 'fields' => $fields, 'result' => $resp['result'] ?? null,
        ]);
    }

    private function resolveResponsible($assignedById): ?int
    {
        if ($assignedById === null || $assignedById === '') {
            return null;
        }
        $key = (string) $assignedById;
        if (array_key_exists($key, $this->responsibleCache)) {
            return $this->responsibleCache[$key];
        }

        $userId = null;
        try {
            $usersTypeId = DB::table('data_types')->where('slug', 'users')->value('id');
            Bitrix24Controller::actualizeBitrix24Employees($this->base, $usersTypeId);

            if (Schema::hasColumn('users', 'b24_responsible')) {
                $userId = \App\Models\User::where('b24_responsible', $key)->value('id');
            }

            if (!$userId) {
                $resp = $this->b24('user.get', ['id' => $key]);
                $manager = $resp['result'][0] ?? null;
                if ($manager && !empty($manager['EMAIL'])) {
                    $userId = \App\Models\User::where('email', $manager['EMAIL'])->value('id');
                }
            }
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('entity-sync: manager match failed', [
                'assigned_by_id' => $key,
                'error'          => $e->getMessage(),
            ]);
        }

        return $this->responsibleCache[$key] = $userId ? (int) $userId : null;
    }

    public function backfillResponsibles(): array
    {
        $map = [
            'deals'     => 'crm.deal.list',
            'contacts'  => 'crm.contact.list',
            'companies' => 'crm.company.list',
        ];
        $out = [];
        foreach ($map as $table => $method) {
            if (!Schema::hasTable($table)
                || !Schema::hasColumn($table, 'user_id')
                || !Schema::hasColumn($table, 'b24_id')) {
                continue;
            }
            $rows = DB::table($table)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('user_id')->orWhere('user_id', 0);
                })
                ->whereNotNull('b24_id')
                ->where('b24_id', '!=', '')
                ->pluck('b24_id', 'id');

            $updated = 0;
            foreach (array_chunk($rows->all(), 50, true) as $chunk) {
                $cmd = [];
                foreach ($chunk as $localId => $b24Id) {
                    $cmd['r_' . $localId] = $method . '?filter[ID]=' . $b24Id . '&select[]=ID&select[]=ASSIGNED_BY_ID';
                }
                $res = $this->b24Batch($cmd);
                foreach ($chunk as $localId => $b24Id) {
                    $item = $res['r_' . $localId][0] ?? null;
                    $userId = $this->resolveResponsible($item['ASSIGNED_BY_ID'] ?? null);
                    if ($userId) {
                        DB::table($table)->where('id', $localId)->update(['user_id' => $userId]);
                        $updated++;
                    }
                }
            }
            $out[$table] = ['empty' => count($rows), 'updated' => $updated];
        }
        return $out;
    }

    private function writeSyncCreatedHistory(string $slug, $id): void
    {
        try {
            $history = new History([
                'entity'    => $slug,
                'entity_id' => $id,
                'user_id'   => null,
                'event'     => 'OBJECT_CREATED',
                'text'      => 'Создана запись: ' . $id . ' (синхронизировано из Bitrix24)',
            ]);
            $history->saveQuietly();
        } catch (\Throwable $e) {
        }
    }

    private function writeSyncFieldHistory(string $slug, $id, array $changed): void
    {
        unset($changed['updated_at'], $changed['created_at'], $changed['b24_id'], $changed['crm_link'], $changed['deleted_at']);
        if (!count($changed)) {
            return;
        }
        try {
            History::saveForObject($slug, [array_merge(['id' => $id], $changed)]);
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('entity-sync: history write failed', [
                'slug' => $slug, 'id' => $id, 'error' => $e->getMessage(),
            ]);
        }
    }

    private function writeMirrorRelationHistory(string $slug, string $modelClass, array $addedIds, array $removedIds, string $relationMethod, string $mirrorField, int $ownerId): void
    {
        foreach ([1 => $addedIds, 0 => $removedIds] as $isAdd => $ids) {
            foreach ($ids as $relId) {
                try {
                    $related = $modelClass::find($relId);
                    if (!$related) {
                        continue;
                    }
                    $current = $related->{$relationMethod}()->pluck($related->{$relationMethod}()->getRelated()->getTable() . '.id')
                        ->map(fn ($v) => (int) $v)->values()->all();
                    $new = $isAdd
                        ? array_values(array_unique(array_merge($current, [$ownerId])))
                        : array_values(array_diff($current, [$ownerId]));
                    History::saveForObject($slug, [['id' => $relId, $mirrorField => $new]]);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    private function linkDealRelations(Deal $model, array $deal, ?array $contactItems = null): void
    {
        if ($contactItems === null) {
            $itemsResp = $this->b24('crm.deal.contact.items.get', ['id' => $deal['ID']]);
            $contactItems = $itemsResp['result'] ?? [];
        }
        $contactIds = [];
        foreach ($contactItems as $item) {
            if (!empty($item['CONTACT_ID'])) {
                $localId = $this->localIdByB24('contacts', $item['CONTACT_ID']);
                if (!$localId) {
                    $localId = $this->pullContactById($item['CONTACT_ID'])?->id;
                }
                if ($localId) {
                    $contactIds[] = (int) $localId;
                }
            }
        }
        $contactIds = array_values(array_unique($contactIds));
        sort($contactIds);

        $currentContacts = $model->contacts()->pluck('contacts.id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        if ($currentContacts !== $contactIds) {
            $this->writeSyncFieldHistory('deals', $model->id, ['contact_id' => $contactIds]);
            $this->writeMirrorRelationHistory(
                'contacts',
                Contact::class,
                array_values(array_diff($contactIds, $currentContacts)),
                array_values(array_diff($currentContacts, $contactIds)),
                'deals',
                'deal_id',
                (int) $model->id
            );
            $model->contacts()->sync($contactIds);
            if (count($contactIds)) {
                DB::table('contacts')->whereIntegerInRaw('id', $contactIds)->update(['deal_id' => $model->id]);
            }
        }
        $model->contact_id = json_encode($contactIds);

        $companyIds = [];
        if (!empty($deal['COMPANY_ID'])) {
            $localId = $this->localIdByB24('companies', $deal['COMPANY_ID']);
            if (!$localId) {
                $localId = $this->pullCompanyById($deal['COMPANY_ID'])?->id;
            }
            if ($localId) {
                $companyIds[] = (int) $localId;
            }
        }

        $currentCompanies = $model->companies()->pluck('companies.id')->map(fn ($v) => (int) $v)->sort()->values()->all();
        if ($currentCompanies !== $companyIds) {
            $this->writeSyncFieldHistory('deals', $model->id, ['company_id' => $companyIds]);
            $this->writeMirrorRelationHistory(
                'companies',
                Company::class,
                array_values(array_diff($companyIds, $currentCompanies)),
                array_values(array_diff($currentCompanies, $companyIds)),
                'deals',
                'deal_id',
                (int) $model->id
            );
            $model->companies()->sync($companyIds);
            if (count($companyIds) && Schema::hasColumn('companies', 'deal_id')) {
                DB::table('companies')->whereIntegerInRaw('id', $companyIds)->update(['deal_id' => $model->id]);
            }
        }
        $model->company_id = json_encode($companyIds);

        if ($model->isDirty()) {
            $model->saveQuietly();
        }
    }

    private function localIdByB24(string $table, $b24Id): ?int
    {
        if (!Schema::hasColumn($table, 'b24_id')) {
            return null;
        }
        $id = DB::table($table)
            ->where('b24_id', (string) $b24Id)
            ->whereNull('deleted_at')
            ->value('id');
        return $id ? (int) $id : null;
    }

    public function upsertContactFromB24(array $contact, ?array $companyItems = null): Contact
    {
        $b24Id = (string) $contact['ID'];

        self::$muted = true;
        try {
            $model = Contact::withTrashed()->where('b24_id', $b24Id)->first() ?: new Contact();
            $isNew = !$model->exists;
            if (!$isNew && $model->trashed()) {
                $model->deleted_at = null;
            }

            $model->b24_id = $b24Id;
            $name = trim(implode(' ', array_filter([
                $contact['LAST_NAME'] ?? null,
                $contact['NAME'] ?? null,
                $contact['SECOND_NAME'] ?? null,
            ])));
            $model->name = $name !== '' ? $name : ('Контакт #' . $b24Id);
            $model->emails = json_encode($this->multiFieldValues($contact['EMAIL'] ?? null), JSON_UNESCAPED_UNICODE);
            $model->phones = json_encode($this->multiFieldValues($contact['PHONE'] ?? null), JSON_UNESCAPED_UNICODE);

            if (Schema::hasColumn('contacts', 'contact_type') && array_key_exists(self::CONTACT_TYPE_UF, $contact)) {
                $raw = $contact[self::CONTACT_TYPE_UF];
                $values = [];
                foreach ((is_array($raw) ? $raw : [$raw]) as $enumId) {
                    if ($enumId === '' || $enumId === null || $enumId === false) {
                        continue;
                    }
                    $label = $this->b24ContactEnumLabel(self::CONTACT_TYPE_UF, $enumId);
                    $local = $this->localOptionValueByLabel('contacts', 'contact_type', $label);
                    if ($local !== null) {
                        $values[] = (int) $local;
                    }
                }
                $model->contact_type = json_encode($values, JSON_UNESCAPED_UNICODE);
            }

            if (Schema::hasColumn('contacts', 'user_id')) {
                $responsibleId = $this->resolveResponsible($contact['ASSIGNED_BY_ID'] ?? null);
                if ($responsibleId) {
                    $model->user_id = $responsibleId;
                }
            }

            if ($isNew) {
                $model->save();
                $this->writeSyncCreatedHistory('contacts', $model->id);
            } elseif (count($model->getDirty())) {
                $this->writeSyncFieldHistory('contacts', $model->id, $model->getDirty());
                $model->save();
            }

            if ($companyItems === null) {
                $itemsResp = $this->b24('crm.contact.company.items.get', ['id' => $b24Id]);
                $companyItems = $itemsResp['result'] ?? [];
            }
            $companyIds = [];
            foreach ($companyItems as $item) {
                if (!empty($item['COMPANY_ID'])) {
                    $localId = $this->localIdByB24('companies', $item['COMPANY_ID']);
                    if (!$localId) {
                        $localId = $this->pullCompanyById($item['COMPANY_ID'])?->id;
                    }
                    if ($localId) {
                        $companyIds[] = (int) $localId;
                    }
                }
            }
            $companyIds = array_values(array_unique($companyIds));
            sort($companyIds);

            $currentCompanies = $model->companies()->pluck('companies.id')->map(fn ($v) => (int) $v)->sort()->values()->all();
            if ($currentCompanies !== $companyIds) {
                $this->writeSyncFieldHistory('contacts', $model->id, ['company_id' => $companyIds]);
                $this->writeMirrorRelationHistory(
                    'companies',
                    Company::class,
                    array_values(array_diff($companyIds, $currentCompanies)),
                    array_values(array_diff($currentCompanies, $companyIds)),
                    'contacts',
                    'contact_id',
                    (int) $model->id
                );
                $model->companies()->sync($companyIds);
            }
            $model->company_id = json_encode($companyIds);
            if ($model->isDirty()) {
                $model->saveQuietly();
            }

            return $model;
        } finally {
            self::$muted = false;
        }
    }

    public function upsertCompanyFromB24(array $company): Company
    {
        $b24Id = (string) $company['ID'];
        $title = trim((string) ($company['TITLE'] ?? ''));

        self::$muted = true;
        try {
            $model = null;
            if (Schema::hasColumn('companies', 'b24_id')) {
                $model = Company::withTrashed()->where('b24_id', $b24Id)->first();
            }
            if (!$model && $title !== '') {
                $model = Company::where('name', $title)->first();
            }
            if (!$model) {
                $model = new Company();
                $model->name = $title !== '' ? $title : ('Компания #' . $b24Id);
                $meta = $this->companyTypeMeta();
                if ($meta) {
                    $model->{$meta->field} = $meta->is_plural ? json_encode([$meta->value]) : $meta->value;
                }
            } elseif ($model->trashed()) {
                $model->deleted_at = null;
            }
            if (Schema::hasColumn('companies', 'b24_id')) {
                $model->b24_id = $b24Id;
            }
            if (Schema::hasColumn('companies', 'user_id')) {
                $responsibleId = $this->resolveResponsible($company['ASSIGNED_BY_ID'] ?? null);
                if ($responsibleId) {
                    $model->user_id = $responsibleId;
                }
            }
            $requisiteData = $this->fetchCompanyRequisiteData([0 => $b24Id])[0] ?? null;
            if ($requisiteData) {
                $this->applyCompanyRequisiteFields($model, $requisiteData['fields'], true);
            }
            if (!$model->exists) {
                $model->saveQuietly();
                $this->writeSyncCreatedHistory('companies', $model->id);
            } elseif ($model->isDirty()) {
                $this->writeSyncFieldHistory('companies', $model->id, $model->getDirty());
                $model->saveQuietly();
            }
            if ($requisiteData) {
                $this->applyCompanyBankDetails($model, $requisiteData['bank']);
            }
            return $model;
        } finally {
            self::$muted = false;
        }
    }

    private $companyTypeMeta = null;

    private function companyTypeMeta(): ?object
    {
        if ($this->companyTypeMeta === null) {
            $this->companyTypeMeta = false;
            $typeId = DB::table('data_types')->where('slug', 'companies')->value('id');
            $row = $typeId ? DB::table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('type', 'select_dropdown')
                ->where('title', 'Тип компании')
                ->where('is_remove', 0)
                ->first() : null;
            if ($row && Schema::hasColumn('companies', $row->field)) {
                $value = null;
                $details = json_decode($row->details ?? '', true);
                foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $option) {
                    if (is_array($option) && mb_strtolower(trim((string) ($option['label'] ?? ''))) === 'клиент') {
                        $value = $option['value'];
                        break;
                    }
                }
                if ($value !== null) {
                    $this->companyTypeMeta = (object) [
                        'field' => $row->field,
                        'value' => $value,
                        'is_plural' => (bool) $row->is_plural,
                    ];
                }
            }
        }

        return $this->companyTypeMeta ?: null;
    }

    public const COMPANY_REQUISITE_FIELDS = [
        'inn', 'kpp', 'ogrn', 'okpo', 'oktmo', 'full_name', 'director', 'accountant',
        'registration_date', 'address', 'fact_address',
    ];

    private const REQUISITE_SELECT = [
        'ID', 'NAME', 'SORT', 'PRESET_ID', 'RQ_INN', 'RQ_KPP', 'RQ_OGRN', 'RQ_OGRNIP', 'RQ_OKPO', 'RQ_OKTMO',
        'RQ_COMPANY_NAME', 'RQ_COMPANY_FULL_NAME', 'RQ_COMPANY_REG_DATE', 'RQ_DIRECTOR', 'RQ_ACCOUNTANT',
    ];

    private const BANK_SELECT = [
        'ID', 'ENTITY_ID', 'NAME', 'SORT', 'ACTIVE', 'COMMENTS', 'RQ_BANK_NAME', 'RQ_BANK_ADDR', 'RQ_BIK',
        'RQ_ACC_NUM', 'RQ_COR_ACC_NUM', 'RQ_SWIFT', 'RQ_ACC_CURRENCY',
    ];

    private function companyRequisiteColumns(): array
    {
        return array_values(array_filter(
            self::COMPANY_REQUISITE_FIELDS,
            fn ($field) => Schema::hasColumn('companies', $field)
        ));
    }

    private function bankRequisitesReady(): bool
    {
        return Schema::hasTable('bank_requisites') && Schema::hasColumn('bank_requisites', 'b24_id');
    }

    private function selectQuery(array $fields): string
    {
        return implode('', array_map(fn ($f) => '&select[]=' . $f, $fields));
    }

    private function fetchCompanyRequisiteData(array $companies): array
    {
        $out = [];
        $cmd = [];
        foreach ($companies as $localId => $b24Id) {
            $out[$localId] = ['requisites' => [], 'addresses' => [], 'bank' => [], 'fields' => []];
            $cmd['req_' . $localId] = 'crm.requisite.list?filter[ENTITY_TYPE_ID]=4&filter[ENTITY_ID]=' . $b24Id
                . $this->selectQuery(self::REQUISITE_SELECT);
            $cmd['addr_' . $localId] = 'crm.address.list?filter[ANCHOR_TYPE_ID]=4&filter[ANCHOR_ID]=' . $b24Id;
        }
        if (!count($cmd)) {
            return $out;
        }

        $res = $this->b24Batch($cmd);

        $cmd2 = [];
        $requisiteOwner = [];
        foreach ($companies as $localId => $b24Id) {
            $requisites = is_array($res['req_' . $localId] ?? null) ? $res['req_' . $localId] : [];
            usort($requisites, fn ($a, $b) => ((int) ($a['SORT'] ?? 0) <=> (int) ($b['SORT'] ?? 0)) ?: ((int) ($a['ID'] ?? 0) <=> (int) ($b['ID'] ?? 0)));
            $out[$localId]['requisites'] = $requisites;
            $out[$localId]['addresses'] = is_array($res['addr_' . $localId] ?? null) ? $res['addr_' . $localId] : [];
            foreach ($requisites as $requisite) {
                $rid = (string) ($requisite['ID'] ?? '');
                if ($rid === '') {
                    continue;
                }
                $requisiteOwner[$rid] = $localId;
                $cmd2['addr_' . $rid] = 'crm.address.list?filter[ANCHOR_TYPE_ID]=8&filter[ANCHOR_ID]=' . $rid;
                if ($this->bankRequisitesReady()) {
                    $cmd2['bank_' . $rid] = 'crm.requisite.bankdetail.list?filter[ENTITY_ID]=' . $rid
                        . $this->selectQuery(self::BANK_SELECT);
                }
            }
        }

        if (count($cmd2)) {
            $res2 = $this->b24Batch($cmd2);
            foreach ($requisiteOwner as $rid => $localId) {
                $addresses = $res2['addr_' . $rid] ?? null;
                if (is_array($addresses)) {
                    $out[$localId]['addresses'] = array_merge($out[$localId]['addresses'], $addresses);
                }
                $bank = $res2['bank_' . $rid] ?? null;
                if (is_array($bank)) {
                    $out[$localId]['bank'] = array_merge($out[$localId]['bank'], $bank);
                }
            }
        }

        foreach ($out as $localId => $data) {
            $out[$localId]['fields'] = $this->parseCompanyRequisites($data['requisites'], $data['addresses']);
        }

        return $out;
    }

    private function formatB24Date($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function parseCompanyRequisites($reqRows, $addrRows): array
    {
        $out = array_fill_keys(self::COMPANY_REQUISITE_FIELDS, null);
        $map = [
            'inn' => ['RQ_INN'],
            'kpp' => ['RQ_KPP'],
            'ogrn' => ['RQ_OGRN', 'RQ_OGRNIP'],
            'okpo' => ['RQ_OKPO'],
            'oktmo' => ['RQ_OKTMO'],
            'full_name' => ['RQ_COMPANY_FULL_NAME', 'RQ_COMPANY_NAME'],
            'director' => ['RQ_DIRECTOR'],
            'accountant' => ['RQ_ACCOUNTANT'],
        ];
        foreach ((is_array($reqRows) ? $reqRows : []) as $requisite) {
            foreach ($map as $field => $keys) {
                if ($out[$field] !== null) {
                    continue;
                }
                foreach ($keys as $key) {
                    $value = trim((string) ($requisite[$key] ?? ''));
                    if ($value !== '') {
                        $out[$field] = $value;
                        break;
                    }
                }
            }
            if ($out['registration_date'] === null) {
                $out['registration_date'] = $this->formatB24Date($requisite['RQ_COMPANY_REG_DATE'] ?? '');
            }
        }

        $addressTypes = ['6' => 'address', '1' => 'fact_address'];
        foreach ((is_array($addrRows) ? $addrRows : []) as $address) {
            $field = $addressTypes[(string) ($address['TYPE_ID'] ?? '')] ?? null;
            if (!$field || $out[$field] !== null) {
                continue;
            }
            $parts = [];
            foreach (['POSTAL_CODE', 'COUNTRY', 'PROVINCE', 'REGION', 'CITY', 'ADDRESS_1', 'ADDRESS_2'] as $key) {
                $value = trim((string) ($address[$key] ?? ''));
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
            if (count($parts)) {
                $out[$field] = implode(', ', $parts);
            }
        }

        return $out;
    }

    private function applyCompanyRequisiteFields(Company $model, array $fields, bool $overwrite): void
    {
        foreach ($this->companyRequisiteColumns() as $column) {
            if ($fields[$column] === null) {
                continue;
            }
            $current = trim((string) ($model->{$column} ?? ''));
            if ($overwrite || $current === '') {
                $model->{$column} = $fields[$column];
            }
        }
    }

    private function applyCompanyBankDetails(Company $model, array $bankRows): array
    {
        $stat = ['created' => 0, 'updated' => 0, 'deleted' => 0];
        if (!$this->bankRequisitesReady() || !$model->id) {
            return $stat;
        }

        $seen = [];
        foreach ($bankRows as $row) {
            $b24Id = (string) ($row['ID'] ?? '');
            $values = [
                'bank_name' => trim((string) ($row['RQ_BANK_NAME'] ?? '')),
                'bic' => trim((string) ($row['RQ_BIK'] ?? '')),
                'account' => trim((string) ($row['RQ_ACC_NUM'] ?? '')),
                'corr_account' => trim((string) ($row['RQ_COR_ACC_NUM'] ?? '')),
                'swift' => trim((string) ($row['RQ_SWIFT'] ?? '')),
                'bank_address' => trim((string) ($row['RQ_BANK_ADDR'] ?? '')),
                'currency' => strtoupper(trim((string) ($row['RQ_ACC_CURRENCY'] ?? ''))),
                'comment' => trim((string) ($row['COMMENTS'] ?? '')),
            ];
            if ($b24Id === '' || ($values['bank_name'] === '' && $values['bic'] === '' && $values['account'] === '')) {
                continue;
            }
            $seen[] = $b24Id;

            $requisite = \App\Models\BankRequisite::withTrashed()->where('b24_id', $b24Id)->first();
            $isNew = !$requisite;
            if ($isNew) {
                $requisite = new \App\Models\BankRequisite();
                $requisite->b24_id = $b24Id;
                $requisite->user_id = $model->user_id ?: 1;
            } elseif ($requisite->trashed()) {
                $requisite->deleted_at = null;
            }

            $name = trim((string) ($row['NAME'] ?? ''));
            if ($name === '') {
                $name = $values['bank_name'] !== '' ? $values['bank_name'] : ('Счёт ' . $values['account']);
            }
            $requisite->name = $name;
            $requisite->company_id = $model->id;
            foreach ($values as $column => $value) {
                if (!Schema::hasColumn('bank_requisites', $column)) {
                    continue;
                }
                if ($column === 'comment' && $value === '' && !$isNew) {
                    continue;
                }
                $requisite->{$column} = $value !== '' ? $value : null;
            }
            if ($values['currency'] === '' && Schema::hasColumn('bank_requisites', 'currency') && !$requisite->currency) {
                $requisite->currency = 'RUB';
            }

            if ($isNew) {
                $requisite->saveQuietly();
                $this->writeSyncCreatedHistory('bank_requisites', $requisite->id);
                $stat['created']++;
            } elseif ($requisite->isDirty()) {
                $this->writeSyncFieldHistory('bank_requisites', $requisite->id, $requisite->getDirty());
                $requisite->saveQuietly();
                $stat['updated']++;
            }
            $requisite->syncDefault();
        }

        $stale = \App\Models\BankRequisite::where('company_id', $model->id)
            ->whereNotNull('b24_id')
            ->where('b24_id', '!=', '')
            ->when(count($seen), fn ($q) => $q->whereNotIn('b24_id', $seen))
            ->get();
        foreach ($stale as $requisite) {
            $requisite->delete();
            $stat['deleted']++;
        }

        if (Schema::hasColumn('companies', 'bank_requisite_id')) {
            $ids = \App\Models\BankRequisite::where('company_id', $model->id)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
            DB::table('companies')->where('id', $model->id)->update(['bank_requisite_id' => count($ids) ? json_encode($ids) : null]);
        }

        return $stat;
    }

    public function backfillCompanyRequisites(bool $force = false): array
    {
        $out = ['checked' => 0, 'updated' => 0, 'bank_created' => 0, 'bank_updated' => 0, 'bank_deleted' => 0];
        if (!Schema::hasTable('companies') || !Schema::hasColumn('companies', 'b24_id')) {
            return $out;
        }
        $columns = $this->companyRequisiteColumns();
        if (!count($columns) && !$this->bankRequisitesReady()) {
            return $out;
        }
        $query = Company::whereNotNull('b24_id')->where('b24_id', '!=', '');
        if (!$force) {
            $query->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhereNull($column)->orWhere($column, '');
                }
            });
        }
        $companies = $query->orderBy('id')->get();
        $out['checked'] = count($companies);

        self::$muted = true;
        try {
            foreach ($companies->chunk(25) as $chunk) {
                $map = [];
                foreach ($chunk as $company) {
                    $map[$company->id] = (string) $company->b24_id;
                }
                $data = $this->fetchCompanyRequisiteData($map);
                foreach ($chunk as $company) {
                    $item = $data[$company->id] ?? null;
                    if (!$item) {
                        continue;
                    }
                    $this->applyCompanyRequisiteFields($company, $item['fields'], $force);
                    if ($company->isDirty()) {
                        $this->writeSyncFieldHistory('companies', $company->id, $company->getDirty());
                        $company->saveQuietly();
                        $out['updated']++;
                    }
                    $bank = $this->applyCompanyBankDetails($company, $item['bank']);
                    $out['bank_created'] += $bank['created'];
                    $out['bank_updated'] += $bank['updated'];
                    $out['bank_deleted'] += $bank['deleted'];
                }
            }
        } finally {
            self::$muted = false;
        }
        return $out;
    }

    private function multiFieldValues($items): array
    {
        if (!is_array($items)) {
            return [];
        }
        return array_values(array_filter(array_map(
            fn ($i) => is_array($i) ? trim((string) ($i['VALUE'] ?? '')) : trim((string) $i),
            $items
        ), fn ($v) => $v !== ''));
    }

    private function fetchDealProducts($dealId, ?array $rows = null): array
    {
        $deliveryPrice = 0;
        $allWeight = 0;
        $products = [];
        if ($rows === null) {
            $resp = $this->b24('crm.deal.productrows.get', ['id' => $dealId]);
            $rows = $resp['result'] ?? [];
        }
        foreach ($rows as $product) {
            $pid = $product['PRODUCT_ID'] ?? null;
            $isDelivery = $pid && in_array($pid, Bitrix24Controller::SKIP_PRODUCT_IDS);
            $prod = Bitrix24Controller::resolveDealProduct($this->base, $product);
            if ($isDelivery) {
                $deliveryPrice += (float) ($product['PRICE'] ?? 0) * ($product['QUANTITY'] ?? 0);
            }
            if ($prod) {
                $qty = $product['QUANTITY'] ?? 0;
                $weight = $prod->weight ?? 0;
                $allWeight += ((float) $weight) * $qty;
                $products[] = [
                    'id'     => $prod->id,
                    'name'   => B24ProductSync::nameText($prod->name),
                    'price'  => $product['PRICE'] ?? 0,
                    'count'  => $qty,
                    'weight' => $weight,
                    'sum'    => ($product['PRICE'] ?? 0) * $qty,
                ];
            } elseif (!$isDelivery) {
                $deliveryPrice += (float) ($product['PRICE'] ?? 0) * ($product['QUANTITY'] ?? 0);
            }
        }
        return [$products, $deliveryPrice, $allWeight];
    }

    private function b24EnumLabel(string $ufCode, $enumId): ?string
    {
        if ($this->b24DealFields === null) {
            $resp = $this->b24('crm.deal.fields', []);
            $this->b24DealFields = $resp['result'] ?? [];
        }
        return $this->enumLabelFromItems($this->b24DealFields[$ufCode]['items'] ?? null, $enumId);
    }

    private function contactFieldsMeta(): array
    {
        if ($this->b24ContactFields === null) {
            $resp = $this->b24('crm.contact.fields', []);
            $this->b24ContactFields = $resp['result'] ?? [];
        }
        return $this->b24ContactFields;
    }

    private function b24ContactEnumLabel(string $ufCode, $enumId): ?string
    {
        return $this->enumLabelFromItems($this->contactFieldsMeta()[$ufCode]['items'] ?? null, $enumId);
    }

    private function b24ContactEnumIdByLabel(string $ufCode, ?string $label): ?string
    {
        if ($label === null || trim($label) === '') {
            return null;
        }
        $items = $this->contactFieldsMeta()[$ufCode]['items'] ?? null;
        if (!is_array($items)) {
            return null;
        }
        $needle = mb_strtolower(trim($label));
        foreach ($items as $item) {
            if (mb_strtolower(trim((string) ($item['VALUE'] ?? ''))) === $needle) {
                return (string) ($item['ID'] ?? '') ?: null;
            }
        }
        return null;
    }

    private function enumLabelFromItems($items, $enumId): ?string
    {
        if (!is_array($items)) {
            return null;
        }
        foreach ($items as $item) {
            if ((string) ($item['ID'] ?? '') === (string) $enumId) {
                return $item['VALUE'] ?? null;
            }
        }
        return null;
    }

    private function localOptionLabelByValue(string $entitySlug, string $fieldKey, $value): ?string
    {
        $row = DB::table('data_rows')
            ->join('data_types', 'data_rows.data_type_id', '=', 'data_types.id')
            ->where('data_types.slug', $entitySlug)
            ->where('data_rows.field', $fieldKey)
            ->select('data_rows.details')
            ->first();
        $details = $row && $row->details ? json_decode($row->details, true) : null;
        foreach ((is_array($details) ? ($details['options'] ?? []) : []) as $key => $option) {
            if (is_array($option)) {
                $optValue = $option['value'] ?? $key;
                $optLabel = $option['label'] ?? null;
                if (is_array($optLabel)) {
                    $optLabel = $optLabel['text'] ?? null;
                }
            } else {
                $optValue = $key;
                $optLabel = $option;
            }
            if ((string) $optValue === (string) $value) {
                return $optLabel !== null ? (string) $optLabel : null;
            }
        }
        return null;
    }

    private function localOptionValueByLabel(string $entitySlug, string $fieldKey, $label)
    {
        if ($label === null || trim((string) $label) === '') {
            return null;
        }
        $row = DB::table('data_rows')
            ->join('data_types', 'data_rows.data_type_id', '=', 'data_types.id')
            ->where('data_types.slug', $entitySlug)
            ->where('data_rows.field', $fieldKey)
            ->select('data_rows.details')
            ->first();
        if (!$row || !$row->details) {
            return null;
        }
        $details = json_decode($row->details, true);
        if (!is_array($details) || empty($details['options'])) {
            return null;
        }
        $needle = mb_strtolower(trim((string) $label));
        foreach ($details['options'] as $key => $option) {
            if (is_array($option)) {
                $optLabel = $option['label'] ?? null;
                if (is_array($optLabel)) {
                    $optLabel = $optLabel['text'] ?? null;
                }
                $optValue = $option['value'] ?? $key;
            } else {
                $optLabel = $option;
                $optValue = $key;
            }
            if ($optLabel !== null && mb_strtolower(trim((string) $optLabel)) === $needle) {
                return $optValue;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------ push

    public function pushDeal(Deal $deal, array $changed): void
    {
        if (in_array('contact_id', $changed, true)) {
            $this->pushDealContacts($deal);
        }
        if (in_array('company_id', $changed, true)) {
            $this->pushDealCompany($deal);
        }
        if (in_array('bank_requisite_id', $changed, true)) {
            try {
                $this->pushDealRequisiteLink($deal);
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('entity-sync: deal requisite link push failed', ['deal_id' => $deal->id, 'error' => $e->getMessage()]);
            }
        }

        $fields = [];
        foreach ($changed as $field) {
            switch ($field) {
                case 'address':
                    $addr = json_decode((string) $deal->address, true);
                    $fields['UF_CRM_1528885851543'] = is_array($addr) ? ($addr['text'] ?? '') : (string) $deal->address;
                    if (is_array($addr) && !empty($addr['coords']) && count($addr['coords']) >= 2) {
                        $fields['UF_CRM_1741758491'] = $addr['coords'][0] . ',' . $addr['coords'][1];
                    }
                    break;
                case 'time':
                    $fields['UF_CRM_1632832553'] = (string) $deal->time;
                    break;
                case 'phone':
                    $phones = json_decode((string) $deal->phone, true);
                    $fields['UF_CRM_1623418181538'] = is_array($phones)
                        ? implode(', ', array_filter($phones, fn ($v) => $v !== null && $v !== ''))
                        : (string) $deal->phone;
                    break;
                case 'delivery_price':
                    $fields['UF_CRM_1633508830'] = (string) $deal->delivery_price;
                    break;
                case 'comment':
                    $fields['UF_CRM_5EAFC3D4C5F76'] = (string) $deal->comment;
                    break;
                case 'pallets_count':
                    $fields['UF_CRM_1696596978695'] = (string) $deal->pallets_count;
                    break;
                case 'delivery_date':
                    $fields['UF_CRM_1738582841'] = $deal->delivery_date;
                    break;
                case 'contact':
                    $fields['UF_CRM_1642670804'] = (string) $deal->contact;
                    break;
            }
        }
        if (!count($fields)) {
            return;
        }
        $resp = $this->b24('crm.deal.update', ['id' => $deal->b24_id, 'fields' => $fields]);
        Log::channel('bitrix24')->info('entity-sync: deal pushed', [
            'deal_id' => $deal->id, 'b24_id' => $deal->b24_id,
            'fields' => array_keys($fields), 'result' => $resp['result'] ?? null,
        ]);
    }

    private function pushDealContacts(Deal $deal): void
    {
        $localIds = json_decode((string) $deal->contact_id, true);
        $localIds = is_array($localIds) ? array_filter($localIds, 'is_numeric') : [];

        $items = [];
        foreach (Contact::whereIntegerInRaw('id', $localIds)->get() as $contact) {
            if (!$contact->b24_id) {
                $this->createContactInB24($contact);
            }
            if ($contact->b24_id) {
                $items[] = ['CONTACT_ID' => (int) $contact->b24_id];
            }
        }

        $resp = $this->b24('crm.deal.contact.items.set', [
            'id'    => $deal->b24_id,
            'items' => $items,
        ]);
        Log::channel('bitrix24')->info('entity-sync: deal contacts pushed', [
            'deal_id' => $deal->id, 'b24_id' => $deal->b24_id,
            'contacts' => array_column($items, 'CONTACT_ID'),
            'result' => $resp['result'] ?? null,
        ]);
    }

    private function pushDealCompany(Deal $deal): void
    {
        $localIds = json_decode((string) $deal->company_id, true);
        $localIds = is_array($localIds) ? array_filter($localIds, 'is_numeric') : [];

        $b24CompanyId = 0;
        if (count($localIds)) {
            $company = Company::whereIntegerInRaw('id', $localIds)->first();
            if ($company) {
                if (!$company->b24_id) {
                    $this->createCompanyInB24($company);
                }
                $b24CompanyId = (int) ($company->b24_id ?: 0);
                if (!$b24CompanyId) {
                    return;
                }
            }
        }

        $resp = $this->b24('crm.deal.update', [
            'id' => $deal->b24_id,
            'fields' => ['COMPANY_ID' => $b24CompanyId],
        ]);
        Log::channel('bitrix24')->info('entity-sync: deal company pushed', [
            'deal_id' => $deal->id, 'b24_id' => $deal->b24_id,
            'company_b24_id' => $b24CompanyId,
            'result' => $resp['result'] ?? null,
        ]);
    }

    private function createContactInB24(Contact $contact): void
    {
        $parts = preg_split('/\s+/', trim((string) $contact->name)) ?: [];
        $fields = [
            'LAST_NAME'   => $parts[0] ?? '',
            'NAME'        => $parts[1] ?? '',
            'SECOND_NAME' => isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '',
        ];
        foreach (['emails' => 'EMAIL', 'phones' => 'PHONE'] as $local => $b24Key) {
            $values = json_decode((string) $contact->{$local}, true);
            if (is_array($values) && count($values)) {
                $fields[$b24Key] = array_map(
                    fn ($v) => ['VALUE' => $v, 'VALUE_TYPE' => 'WORK'],
                    array_values(array_filter($values, fn ($v) => $v !== null && $v !== ''))
                );
            }
        }
        $resp = $this->b24('crm.contact.add', ['fields' => $fields]);
        $newId = $resp['result'] ?? null;
        if ($newId) {
            $contact->b24_id = (string) $newId;
            $contact->saveQuietly();
        }
        Log::channel('bitrix24')->info('entity-sync: contact created in b24', [
            'contact_id' => $contact->id, 'b24_id' => $newId,
        ]);
    }

    private function createCompanyInB24(Company $company): void
    {
        if (!Schema::hasColumn('companies', 'b24_id')) {
            return;
        }
        $resp = $this->b24('crm.company.add', [
            'fields' => ['TITLE' => (string) $company->name],
        ]);
        $newId = $resp['result'] ?? null;
        if ($newId) {
            $company->b24_id = (string) $newId;
            $company->saveQuietly();
        }
        Log::channel('bitrix24')->info('entity-sync: company created in b24', [
            'company_id' => $company->id, 'b24_id' => $newId,
        ]);
    }

    public function pushContact(Contact $contact, array $changed): void
    {
        $fields = [];

        if (in_array('name', $changed, true)) {
            $parts = preg_split('/\s+/', trim((string) $contact->name)) ?: [];
            $fields['LAST_NAME'] = $parts[0] ?? '';
            $fields['NAME'] = $parts[1] ?? '';
            $fields['SECOND_NAME'] = isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '';
        }

        if (in_array('contact_type', $changed, true) && Schema::hasColumn('contacts', 'contact_type')) {
            $values = json_decode((string) $contact->contact_type, true);
            $enumIds = [];
            foreach ((is_array($values) ? $values : []) as $val) {
                $label = $this->localOptionLabelByValue('contacts', 'contact_type', $val);
                $enumId = $this->b24ContactEnumIdByLabel(self::CONTACT_TYPE_UF, $label);
                if ($enumId !== null) {
                    $enumIds[] = $enumId;
                }
            }
            $fields[self::CONTACT_TYPE_UF] = $enumIds;
        }

        $needMulti = array_intersect(['emails', 'phones'], $changed);
        if (count($needMulti)) {
            $currentResp = $this->b24('crm.contact.get', ['id' => $contact->b24_id]);
            $current = $currentResp['result'] ?? [];
            foreach (['emails' => 'EMAIL', 'phones' => 'PHONE'] as $local => $b24Key) {
                if (!in_array($local, $needMulti, true)) {
                    continue;
                }
                $newValues = json_decode((string) $contact->{$local}, true);
                $newValues = is_array($newValues) ? array_values(array_filter($newValues, fn ($v) => $v !== null && $v !== '')) : [];
                $fields[$b24Key] = $this->reconcileMultiField($current[$b24Key] ?? [], $newValues);
            }
        }

        if (!count($fields)) {
            return;
        }
        $resp = $this->b24('crm.contact.update', ['id' => $contact->b24_id, 'fields' => $fields]);
        Log::channel('bitrix24')->info('entity-sync: contact pushed', [
            'contact_id' => $contact->id, 'b24_id' => $contact->b24_id,
            'fields' => array_keys($fields), 'result' => $resp['result'] ?? null,
        ]);
    }

    public function pushCompany(Company $company, array $changed): void
    {
        if (!in_array('name', $changed, true)) {
            return;
        }
        $resp = $this->b24('crm.company.update', [
            'id' => $company->b24_id,
            'fields' => ['TITLE' => (string) $company->name],
        ]);
        Log::channel('bitrix24')->info('entity-sync: company pushed', [
            'company_id' => $company->id, 'b24_id' => $company->b24_id,
            'result' => $resp['result'] ?? null,
        ]);
    }

    /**
     * Мультиполя Bitrix24 (EMAIL/PHONE): существующие значения не из нового
     * списка помечаются к удалению (ID + пустой VALUE), недостающие — добавляются.
     */
    private function reconcileMultiField($currentItems, array $newValues): array
    {
        $result = [];
        $kept = [];
        foreach ((is_array($currentItems) ? $currentItems : []) as $item) {
            $val = trim((string) ($item['VALUE'] ?? ''));
            if (in_array($val, $newValues, true) && !in_array($val, $kept, true)) {
                $kept[] = $val;
                $result[] = ['ID' => $item['ID'], 'VALUE' => $val];
            } else {
                $result[] = ['ID' => $item['ID'], 'VALUE' => ''];
            }
        }
        foreach ($newValues as $val) {
            if (!in_array($val, $kept, true)) {
                $result[] = ['VALUE' => $val, 'VALUE_TYPE' => 'WORK'];
            }
        }
        return $result;
    }
}
