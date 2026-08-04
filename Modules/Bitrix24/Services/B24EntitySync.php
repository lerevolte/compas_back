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
    private array $responsibleCache = [];

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

        $options = [];
        foreach (array_values($items) as $i => $st) {
            $options[] = [
                'value' => (string) ($st['STATUS_ID'] ?? ''),
                'label' => (string) ($st['NAME'] ?? ''),
                'color' => (string) (($st['COLOR'] ?? '') ?: self::STAGE_PALETTE[$i % count(self::STAGE_PALETTE)]),
            ];
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
                foreach ($res['invoices_' . $id] as $invoice) {
                    if (($invoice['UF_DEAL_ID'] ?? null) == $id) {
                        $invoices[] = $invoice['ACCOUNT_NUMBER'] ?? $invoice['ID'];
                    }
                }
                $item['invoices'] = $invoices;
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
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PHONE', 'DATE_MODIFY'],
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

    // --------------------------------------------------------------- upserts

    public function upsertDealFromB24(array $deal, ?array $pre = null): Deal
    {
        $dealId = $deal['ID'];

        self::$muted = true;
        try {
            $model = Deal::withTrashed()->where('b24_id', (string) $dealId)->first() ?: new Deal();
            $isNew = !$model->exists;

            $model->b24_id = (string) $dealId;
            $model->stage = (string) ($deal['STAGE_ID'] ?? '');
            $crmLink = 'https://crm6.ru/crm/deal/details/' . $dealId . '/';
            $model->crm_link = $crmLink;
            $model->name = json_encode([
                'value'         => (string) ($deal['TITLE'] ?? $dealId),
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
            $model->time = $deal['UF_CRM_1632832553'] ?? null;

            if (!empty($deal['UF_CRM_1738582841'])) {
                try {
                    $dt = new \DateTime($deal['UF_CRM_1738582841']);
                    $dt->setTimezone(new \DateTimeZone(config('app.timezone', 'Europe/Moscow')));
                    $model->delivery_date = $dt->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }

            if (!empty($deal['UF_CRM_1623418181538'])) {
                $model->phone = $deal['UF_CRM_1623418181538'];
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
                $model->save();
            }

            $this->linkDealRelations($model, $deal, $pre['contact_items'] ?? null);

            return $model;
        } finally {
            self::$muted = false;
        }
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

    /**
     * Массовый pull не пишет полевую историю (см. инцидент 2026-07-30: ~93k
     * строк histories за день) — только одна запись о создании объекта.
     */
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

            $model->b24_id = $b24Id;
            $name = trim(implode(' ', array_filter([
                $contact['LAST_NAME'] ?? null,
                $contact['NAME'] ?? null,
                $contact['SECOND_NAME'] ?? null,
            ])));
            $model->name = $name !== '' ? $name : ('Контакт #' . $b24Id);
            $model->emails = $this->multiFieldValues($contact['EMAIL'] ?? null);
            $model->phones = $this->multiFieldValues($contact['PHONE'] ?? null);

            if ($isNew) {
                $model->save();
                $this->writeSyncCreatedHistory('contacts', $model->id);
            } elseif (count($model->getDirty())) {
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
            }
            if (Schema::hasColumn('companies', 'b24_id')) {
                $model->b24_id = $b24Id;
            }
            if ($model->isDirty() || !$model->exists) {
                $model->saveQuietly();
            }
            return $model;
        } finally {
            self::$muted = false;
        }
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
            if ($pid && !in_array($pid, Bitrix24Controller::SKIP_PRODUCT_IDS)) {
                $weight = 0;
                try {
                    $prod = \Modules\Products\Entities\Product::where('id_b24', $pid)->first();
                    $weight = $prod->weight ?? 0;
                    $name = $prod->name ?? ($product['PRODUCT_NAME'] ?? ('Товар #' . $pid));
                } catch (\Throwable $e) {
                    $name = $product['PRODUCT_NAME'] ?? ('Товар #' . $pid);
                }
                $qty = $product['QUANTITY'] ?? 0;
                $allWeight += ((float) $weight) * $qty;
                $products[] = [
                    'name'   => $name,
                    'price'  => $product['PRICE'] ?? 0,
                    'count'  => $qty,
                    'weight' => $weight,
                    'sum'    => ($product['PRICE'] ?? 0) * $qty,
                ];
            } else {
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
        $items = $this->b24DealFields[$ufCode]['items'] ?? null;
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
                    $fields['UF_CRM_1623418181538'] = (string) $deal->phone;
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

    public function pushContact(Contact $contact, array $changed): void
    {
        $fields = [];

        if (in_array('name', $changed, true)) {
            $parts = preg_split('/\s+/', trim((string) $contact->name)) ?: [];
            $fields['LAST_NAME'] = $parts[0] ?? '';
            $fields['NAME'] = $parts[1] ?? '';
            $fields['SECOND_NAME'] = isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : '';
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
