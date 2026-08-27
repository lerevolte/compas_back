<?php

namespace App\Services;

use App\Jobs\RegenerateSaleDocuments;
use App\Models\BankRequisite;
use App\Models\Company;
use App\Models\File;
use App\Models\ObjectRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SaleDocumentService
{
    public const TARGETS = [
        'payment_invoices' => [
            'model' => \App\Models\PaymentInvoice::class,
            'view' => 'pdf.sale_invoice',
            'title' => 'Счет на оплату',
            'file' => 'invoice',
        ],
        'expense_invoices' => [
            'model' => \App\Models\ExpenseInvoice::class,
            'view' => 'pdf.expense_invoice',
            'title' => 'Расходная накладная',
            'file' => 'nakladnaya',
        ],
    ];

    public const DEAL_FIELDS = ['name', 'company_id', 'contact_id', 'products', 'shipment_company_id', 'bank_requisite_id'];

    public const COMPANY_FIELDS = [
        'name', 'full_name', 'inn', 'kpp', 'ogrn', 'okpo', 'oktmo', 'director', 'accountant', 'address', 'fact_address',
    ];

    public const BANK_FIELDS = ['bank_name', 'bic', 'account', 'corr_account', 'is_default', 'company_id', 'deleted_at'];

    public const ORG_FIELDS = ['name', 'inn', 'kpp', 'address', 'fact_address', 'account', 'choosed_at', 'deleted_at'];

    private static bool $busy = false;

    public static function generateFor(string $targetSlug, $targetId, string $sourceSlug, $sourceId): bool
    {
        if (!isset(self::TARGETS[$targetSlug]) || $sourceSlug !== 'deals') {
            return false;
        }

        return self::run($targetSlug, (int) $targetId, (int) $sourceId);
    }

    public static function regenerate(string $slug, int $id): bool
    {
        if (!isset(self::TARGETS[$slug])) {
            return false;
        }

        return self::run($slug, $id, self::dealIdFor($slug, $id));
    }

    public static function queue(array $docs): void
    {
        $tenant = tenant('id');
        if (!$tenant || !count($docs)) {
            return;
        }
        $unique = [];
        foreach ($docs as $doc) {
            if (!isset($doc[0], $doc[1]) || !isset(self::TARGETS[$doc[0]])) {
                continue;
            }
            $unique[$doc[0] . '#' . (int) $doc[1]] = [(string) $doc[0], (int) $doc[1]];
        }
        if (!count($unique)) {
            return;
        }
        try {
            RegenerateSaleDocuments::dispatch((string) $tenant, array_values($unique));
        } catch (\Throwable $e) {
            Log::warning('sale-doc: не удалось поставить регенерацию в очередь', ['error' => $e->getMessage()]);
        }
    }

    public static function queueForDeal(int $dealId): void
    {
        self::queue(self::docsForDeals([$dealId]));
    }

    public static function syncFromDeal($deal): void
    {
        $dealId = (int) ($deal->id ?? 0);
        if (!$dealId) {
            return;
        }
        $products = (new self())->decodeProducts($deal->products ?? null);
        $dealSum = (float) ($deal->sum ?? 0);
        $queued = [];
        foreach (self::docsForDeals([$dealId]) as [$slug, $id]) {
            $doc = self::TARGETS[$slug]['model']::find($id);
            if (!$doc) {
                continue;
            }
            if (trim((string) ($doc->b24_id ?? '')) !== '') {
                $queued[] = [$slug, $id];
                continue;
            }
            if (count($products)) {
                $doc->setProducts($products, $dealSum > 0 ? $deal->sum : null);
                continue;
            }
            if ($dealSum > 0 && (float) ($doc->sum ?? 0) !== $dealSum) {
                $doc->sum = $deal->sum;
                $doc->saveQuietly();
            }
            $queued[] = [$slug, $id];
        }
        self::queue($queued);
    }

    public static function queueForCompany(int $companyId): void
    {
        self::queue(self::docsForCompany($companyId));
    }

    public static function queueForBank(BankRequisite $bank): void
    {
        $docs = self::docsWhere(fn ($row) => in_array((int) $bank->id, self::ids($row->bank_requisite_id ?? null), true));
        $companyId = (int) $bank->company_id;
        if ($companyId) {
            $docs = array_merge($docs, self::docsForCompany($companyId));
            if (self::isOurOrganization($companyId)) {
                $docs = array_merge($docs, self::docsWithoutSupplier());
            }
        }
        self::queue($docs);
    }

    public static function queueForOrganization(): void
    {
        self::queue(self::docsWithoutSupplier());
    }

    public static function docsForDeals(array $dealIds): array
    {
        $dealIds = array_values(array_filter(array_map('intval', $dealIds)));
        if (!count($dealIds) || !ObjectRelation::ready()) {
            return [];
        }

        return ObjectRelation::where('source_slug', 'deals')
            ->whereIn('source_id', $dealIds)
            ->whereIn('target_slug', array_keys(self::TARGETS))
            ->get(['target_slug', 'target_id'])
            ->map(fn ($r) => [(string) $r->target_slug, (int) $r->target_id])
            ->all();
    }

    public static function docsForCompany(int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        $docs = self::docsWhere(fn ($row) =>
            in_array($companyId, self::ids($row->company_id ?? null), true)
            || in_array($companyId, self::ids($row->shipment_company_id ?? null), true)
        );

        if (Schema::hasTable('deals')) {
            $query = DB::table('deals')->whereNull('deleted_at');
            $query->where(function ($q) use ($companyId) {
                if (Schema::hasColumn('deals', 'company_id')) {
                    self::whereListHas($q, 'company_id', $companyId, true);
                }
                if (Schema::hasColumn('deals', 'shipment_company_id')) {
                    self::whereListHas($q, 'shipment_company_id', $companyId, true);
                }
                if (!Schema::hasColumn('deals', 'company_id') && !Schema::hasColumn('deals', 'shipment_company_id')) {
                    $q->whereRaw('1 = 0');
                }
            });
            $docs = array_merge($docs, self::docsForDeals($query->pluck('id')->all()));
        }

        return $docs;
    }

    public static function docsWithoutSupplier(): array
    {
        $docs = self::docsWhere(fn ($row) => !self::firstIdStatic($row->shipment_company_id ?? null));
        if (!count($docs) || !ObjectRelation::ready() || !Schema::hasTable('deals') || !Schema::hasColumn('deals', 'shipment_company_id')) {
            return $docs;
        }

        $withSupplier = DB::table('deals')
            ->whereNotNull('shipment_company_id')
            ->where('shipment_company_id', '!=', '')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (!count($withSupplier)) {
            return $docs;
        }

        $covered = [];
        foreach (self::docsForDeals($withSupplier) as $doc) {
            $covered[$doc[0] . '#' . $doc[1]] = true;
        }

        return array_values(array_filter($docs, fn ($doc) => !isset($covered[$doc[0] . '#' . $doc[1]])));
    }

    private static function docsWhere(callable $filter): array
    {
        $out = [];
        foreach (self::TARGETS as $slug => $meta) {
            if (!Schema::hasTable($slug)) {
                continue;
            }
            $columns = ['id'];
            foreach (['company_id', 'shipment_company_id', 'bank_requisite_id'] as $column) {
                if (Schema::hasColumn($slug, $column)) {
                    $columns[] = $column;
                }
            }
            $rows = DB::table($slug)->whereNull('deleted_at')->get($columns);
            foreach ($rows as $row) {
                if ($filter($row)) {
                    $out[] = [$slug, (int) $row->id];
                }
            }
        }
        return $out;
    }

    private static function whereListHas($query, string $column, int $id, bool $or = false): void
    {
        $sql = "CONCAT(',', REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(`{$column}`, ''), '[', ''), ']', ''), '\"', ''), ' ', ''), ',') LIKE ?";
        $or ? $query->orWhereRaw($sql, ["%,{$id},%"]) : $query->whereRaw($sql, ["%,{$id},%"]);
    }

    private static function dealIdFor(string $slug, int $id): ?int
    {
        if (!ObjectRelation::ready()) {
            return null;
        }
        $dealId = ObjectRelation::where('source_slug', 'deals')
            ->where('target_slug', $slug)
            ->where('target_id', $id)
            ->orderBy('id')
            ->value('source_id');

        return $dealId ? (int) $dealId : null;
    }

    private static function isOurOrganization(int $companyId): bool
    {
        if (!Schema::hasTable('requisites') || !Schema::hasColumn('companies', 'inn')) {
            return false;
        }
        $companyInn = preg_replace('/\D/', '', (string) DB::table('companies')->where('id', $companyId)->value('inn'));
        if ($companyInn === '') {
            return false;
        }
        $orgInn = preg_replace('/\D/', '', (string) DB::table('requisites')->whereNull('deleted_at')->value('inn'));

        return $orgInn !== '' && $orgInn === $companyInn;
    }

    private static function run(string $slug, int $id, ?int $dealId): bool
    {
        if (self::$busy) {
            return false;
        }

        self::$busy = true;
        try {
            return (new self())->generate($slug, $id, $dealId);
        } catch (\Throwable $e) {
            Log::warning('sale-doc: генерация печатной формы не удалась', [
                'target' => $slug . '#' . $id,
                'source' => 'deals#' . ($dealId ?? '-'),
                'error' => $e->getMessage(),
            ]);
            return false;
        } finally {
            self::$busy = false;
        }
    }

    private function generate(string $slug, int $id, ?int $dealId): bool
    {
        $meta = self::TARGETS[$slug];
        $doc = $meta['model']::find($id);
        if (!$doc) {
            return false;
        }

        $deal = $dealId && Schema::hasTable('deals') ? DB::table('deals')->where('id', $dealId)->first() : null;

        $companyId = $this->firstId($doc->company_id) ?: ($deal ? $this->firstId($deal->company_id ?? null) : null);
        $company = $companyId ? Company::find($companyId) : null;

        $supplierId = $this->firstId($doc->shipment_company_id ?? null) ?: ($deal ? $this->firstId($deal->shipment_company_id ?? null) : null);
        $supplier = $supplierId ? Company::find($supplierId) : null;

        if ($supplier) {
            $org = $this->companyAsOrganization($supplier);
            $bank = $this->documentBank($doc, fn (BankRequisite $b) => (int) $b->company_id === (int) $supplier->id)
                ?: $supplier->defaultBankRequisite();
        } else {
            $org = $this->ourOrganization();
            $orgInn = preg_replace('/\D/', '', (string) ($org->inn ?? ''));
            $bank = $this->documentBank($doc, fn (BankRequisite $b) => $orgInn === '' || $this->companyInn($b->company_id) === $orgInn)
                ?: ($org ? $this->orgBank($org) : null);
        }

        $products = $this->decodeProducts($doc->products);
        if (!count($products) && $deal) {
            $products = $this->decodeProducts($deal->products ?? null);
        }

        $total = 0.0;
        foreach ($products as $k => $product) {
            $count = (float) ($product['count'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            $products[$k]['total'] = $count * $price;
            $total += $count * $price;
        }
        if ((float) ($doc->sum ?? 0) > 0) {
            $total = (float) $doc->sum;
        }

        $number = trim((string) ($doc->number ?? '')) !== '' ? trim((string) $doc->number) : (string) $doc->id;
        $date = $doc->created_at ? $doc->created_at->format('d.m.Y') : date('d.m.Y');

        $buyer = $dealId ? $this->dealContacts($dealId) : '';
        $dealName = $deal ? $this->plainName($deal->name ?? '') : '';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($meta['view'], [
            'number' => $number,
            'date' => $date,
            'org' => $org,
            'bank' => $bank,
            'company' => $company,
            'companyName' => $company ? ($this->plain($company->full_name ?? '') ?: $this->plainName($company->name ?? '')) : '',
            'products' => $products,
            'total' => $total,
            'buyer' => $buyer,
            'dealName' => $dealName,
            'dealId' => $dealId ?? '',
        ]);

        $disk = \Storage::disk('public');
        $dir = 'sale_docs/' . $slug . '/' . $id;
        if (!\File::isDirectory($disk->path($dir))) {
            \File::makeDirectory($disk->path($dir), 0775, true);
        }
        $filename = $meta['file'] . '_' . $id . '.pdf';
        $path = $dir . '/' . $filename;
        $pdf->save($disk->path($path));
        $this->fixOwnership($disk, ['sale_docs', 'sale_docs/' . $slug, $dir, $path]);

        $tenant = tenant('id');
        $url = $tenant
            ? 'https://' . $tenant . '.compas.pro/storage/tenant' . $tenant . '/app/public/' . $path
            : 'https://compas.pro/storage/app/public/' . $path;

        $file = new File();
        $file->name = $filename;
        $file->path = $path;
        $file->save();

        $generated = [
            'id' => $file->id,
            'name' => $meta['title'] . ' № ' . $number . ' от ' . $date . '.pdf',
            'url' => '/files/pdfSmall.svg',
            'file' => $url . '?v=' . time(),
            'extension' => 'pdf',
            'sort' => 0,
            'ext' => 'pdf',
        ];
        $photos = [$generated];
        $existing = json_decode((string) $doc->photo, true);
        foreach (is_array($existing) ? $existing : [] as $item) {
            if (is_array($item) && !str_contains((string) ($item['file'] ?? ''), '/sale_docs/')) {
                $item['sort'] = count($photos);
                $photos[] = $item;
            }
        }
        $doc->photo = json_encode($photos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($total > 0 && !(float) ($doc->sum ?? 0)) {
            $doc->sum = rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.');
        }
        $currentName = $this->plain($doc->name);
        if ($currentName === '' || $currentName === $dealName || preg_match('/^' . preg_quote($meta['title'], '/') . ' № .+ от \d{2}\.\d{2}\.\d{4}$/u', $currentName)) {
            $doc->name = $meta['title'] . ' № ' . $number . ' от ' . $date;
        }
        if (count($products) && !$this->decodeProducts($doc->products)) {
            $doc->products = json_encode($products, JSON_UNESCAPED_UNICODE);
        }
        $doc->saveQuietly();

        try {
            $settings = app('settings');
            $fieldIds = collect($settings[$slug]['fields'] ?? [])
                ->whereIn('field', ['photo', 'name', 'sum', 'products'])
                ->pluck('id')
                ->all();
            \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $doc->getData($fieldIds, $settings));
        } catch (\Throwable $e) {
        }

        return true;
    }

    private function fixOwnership($disk, array $paths): void
    {
        if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            return;
        }
        $root = rtrim($disk->path(''), '/');
        $uid = @fileowner($root);
        $gid = @filegroup($root);
        if (!$uid) {
            return;
        }
        foreach ($paths as $relative) {
            $absolute = $disk->path($relative);
            if (!file_exists($absolute) || @fileowner($absolute) === $uid) {
                continue;
            }
            @chown($absolute, $uid);
            if ($gid) {
                @chgrp($absolute, $gid);
            }
        }
    }

    public static function documentFiles($photo): array
    {
        $decoded = is_array($photo) ? $photo : json_decode((string) $photo, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $link = trim((string) ($item['file'] ?? ($item['url'] ?? '')));
            if ($link === '') {
                continue;
            }
            $out[] = ['name' => (string) ($item['name'] ?? 'Документ'), 'url' => $link];
        }
        return $out;
    }

    private function companyAsOrganization(Company $company): object
    {
        return (object) [
            'name' => $this->plain($company->full_name ?? '') ?: $this->plainName($company->name ?? ''),
            'inn' => (string) ($company->inn ?? ''),
            'kpp' => (string) ($company->kpp ?? ''),
            'address' => (string) ($company->address ?? ''),
            'fact_address' => (string) ($company->fact_address ?? ''),
            'director' => (string) ($company->director ?? ''),
            'accountant' => (string) ($company->accountant ?? ''),
            'account' => null,
        ];
    }

    private function ourOrganization(): ?object
    {
        if (!Schema::hasTable('requisites')) {
            return null;
        }

        return DB::table('requisites')
            ->whereNull('deleted_at')
            ->orderByRaw('choosed_at IS NULL')
            ->orderByDesc('choosed_at')
            ->orderBy('id')
            ->first();
    }

    private function documentBank($doc, callable $accepts): ?BankRequisite
    {
        if (!Schema::hasTable('bank_requisites')) {
            return null;
        }

        $bankId = $this->firstId($doc->bank_requisite_id ?? null);
        if (!$bankId) {
            return null;
        }

        $bank = BankRequisite::find($bankId);
        if (!$bank) {
            return null;
        }

        return $accepts($bank) ? $bank : null;
    }

    private function companyInn($companyId): string
    {
        if (!$companyId || !Schema::hasColumn('companies', 'inn')) {
            return '';
        }

        return preg_replace('/\D/', '', (string) DB::table('companies')->where('id', $companyId)->value('inn'));
    }

    private function orgBank(object $org): ?BankRequisite
    {
        $inn = preg_replace('/\D/', '', (string) ($org->inn ?? ''));
        if ($inn === '' || !Schema::hasTable('bank_requisites') || !Schema::hasColumn('companies', 'inn')) {
            return null;
        }

        $companyId = DB::table('companies')
            ->whereNull('deleted_at')
            ->where('inn', $inn)
            ->value('id');
        if (!$companyId) {
            return null;
        }

        return BankRequisite::where('company_id', $companyId)->where('is_default', '1')->first()
            ?: BankRequisite::where('company_id', $companyId)->orderBy('id')->first();
    }

    private function firstId($value): ?int
    {
        return self::firstIdStatic($value);
    }

    private static function firstIdStatic($value): ?int
    {
        $ids = self::ids($value);

        return count($ids) ? $ids[0] : null;
    }

    private static function ids($value): array
    {
        if (is_numeric($value)) {
            return (int) $value ? [(int) $value] : [];
        }
        if (is_array($value)) {
            $decoded = $value;
        } else {
            $decoded = json_decode((string) $value, true);
        }
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (is_array($item)) {
                $item = $item['id'] ?? ($item['value'] ?? null);
            }
            if (is_numeric($item) && (int) $item) {
                $out[] = (int) $item;
            }
        }
        return $out;
    }

    private function decodeProducts($value): array
    {
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter($decoded, 'is_array'));
    }

    private function dealContacts(int $dealId): string
    {
        if (!Schema::hasTable('contact_deal') || !Schema::hasTable('contacts')) {
            return '';
        }
        $ids = DB::table('contact_deal')->where('deal_id', $dealId)->pluck('contact_id');
        if (!count($ids)) {
            return '';
        }
        $names = DB::table('contacts')->whereIn('id', $ids)->pluck('name')
            ->map(fn ($name) => $this->plainName($name))
            ->filter()
            ->all();
        return implode(', ', $names);
    }

    private function plainName($name): string
    {
        $name = (string) $name;
        if ($name !== '' && ($name[0] === '{' || $name[0] === '[')) {
            $decoded = json_decode($name, true);
            if (is_array($decoded)) {
                $name = (string) ($decoded['value'] ?? reset($decoded));
            }
        }
        return trim($name);
    }

    private function plain($value): string
    {
        return $this->plainName($value);
    }
}
