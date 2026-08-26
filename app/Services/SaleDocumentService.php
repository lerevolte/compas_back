<?php

namespace App\Services;

use App\Models\BankRequisite;
use App\Models\Company;
use App\Models\File;
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

        $dealId = null;
        if (\App\Models\ObjectRelation::ready()) {
            $dealId = \App\Models\ObjectRelation::where('source_slug', 'deals')
                ->where('target_slug', $slug)
                ->where('target_id', $id)
                ->orderBy('id')
                ->value('source_id');
        }

        return self::run($slug, $id, $dealId ? (int) $dealId : null);
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

        $org = $this->ourOrganization();
        $bank = $this->documentBank($doc, $org) ?: ($org ? $this->orgBank($org) : null);

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
        if ((float) ($doc->sum ?? 0) > 0 && $total <= 0) {
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
            \File::makeDirectory($disk->path($dir), 0755, true);
        }
        $filename = $meta['file'] . '_' . $id . '.pdf';
        $path = $dir . '/' . $filename;
        $pdf->save($disk->path($path));

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
            'file' => $url,
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

        return true;
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

    private function documentBank($doc, ?object $org): ?BankRequisite
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

        $orgInn = preg_replace('/\D/', '', (string) ($org->inn ?? ''));
        if ($orgInn === '' || !Schema::hasColumn('companies', 'inn')) {
            return $bank;
        }

        $companyInn = preg_replace('/\D/', '', (string) DB::table('companies')->where('id', $bank->company_id)->value('inn'));

        return $companyInn === $orgInn ? $bank : null;
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
        if (is_numeric($value)) {
            return (int) $value;
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_numeric($item)) {
                    return (int) $item;
                }
            }
        }
        return null;
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
