<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdService
{
    public const SLUGS = ['logistic_tasks', 'pickups'];

    public const MONTHS = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня',
        7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];

    public static function output(string $slug, array $ids, bool $withDocs = false): ?string
    {
        $pdf = self::pdf($slug, $ids);
        if (!$pdf) {
            return null;
        }
        $bytes = $pdf->output();
        if (!$withDocs) {
            return $bytes;
        }
        $files = self::linkedDocumentFiles($slug, $ids);
        if (!count($files)) {
            return $bytes;
        }

        return self::merge($bytes, $files) ?? $bytes;
    }

    public static function linkedDocumentFiles(string $slug, array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids) || !\App\Models\ObjectRelation::ready()) {
            return [];
        }
        $targets = array_keys(SaleDocumentService::TARGETS);
        $docs = [];
        foreach ($ids as $id) {
            $own = \App\Models\ObjectRelation::where('source_slug', $slug)
                ->where('source_id', $id)
                ->whereIn('target_slug', $targets)
                ->orderBy('id')
                ->get(['target_slug', 'target_id']);
            foreach ($own as $relation) {
                $docs[$relation->target_slug . '#' . $relation->target_id] = [(string) $relation->target_slug, (int) $relation->target_id];
            }
            $parent = ShipmentService::parentOf($slug, $id);
            if ($parent && $parent[0] === 'deals') {
                $dealDocs = \App\Models\ObjectRelation::where('source_slug', 'deals')
                    ->where('source_id', $parent[1])
                    ->whereIn('target_slug', $targets)
                    ->orderBy('id')
                    ->get(['target_slug', 'target_id']);
                foreach ($dealDocs as $relation) {
                    $docs[$relation->target_slug . '#' . $relation->target_id] = [(string) $relation->target_slug, (int) $relation->target_id];
                }
            }
        }

        $disk = \Storage::disk('public');
        $files = [];
        foreach ($docs as [$docSlug, $docId]) {
            if (!Schema::hasTable($docSlug)) {
                continue;
            }
            $row = DB::table($docSlug)->where('id', $docId)->first();
            if (!$row || (property_exists($row, 'deleted_at') && $row->deleted_at)) {
                continue;
            }
            foreach (SaleDocumentService::documentFiles($row->photo ?? null) as $file) {
                $url = strtok((string) $file['url'], '?');
                $pos = strpos($url, '/app/public/');
                if ($pos === false || !str_ends_with(strtolower($url), '.pdf')) {
                    continue;
                }
                $path = $disk->path(substr($url, $pos + strlen('/app/public/')));
                if (is_file($path) && !in_array($path, $files, true)) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    private static function merge(string $bytes, array $files): ?string
    {
        $gs = trim((string) @shell_exec('command -v gs 2>/dev/null'));
        if ($gs === '') {
            return null;
        }
        $dir = storage_path('app/tmp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $main = tempnam($dir, 'upd_');
        $out = tempnam($dir, 'print_');
        file_put_contents($main, $bytes);
        $inputs = array_merge([$main], $files);
        $cmd = escapeshellarg($gs) . ' -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -sOutputFile=' . escapeshellarg($out)
            . ' ' . implode(' ', array_map('escapeshellarg', $inputs)) . ' 2>&1';
        @exec($cmd, $log, $code);
        $result = $code === 0 && is_file($out) && filesize($out) > 0 ? file_get_contents($out) : null;
        @unlink($main);
        @unlink($out);
        if ($result === null) {
            \Log::warning('upd merge failed', ['code' => $code, 'log' => array_slice((array) $log, 0, 5)]);
        }

        return $result;
    }

    public static function pdf(string $slug, array $ids)
    {
        if (!in_array($slug, self::SLUGS, true) || !Schema::hasTable($slug)) {
            return null;
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids)) {
            return null;
        }

        $documents = [];
        foreach ($ids as $id) {
            $document = self::buildDocument($slug, $id);
            if ($document) {
                $documents[] = $document;
            }
        }
        if (!count($documents)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.upd', ['documents' => $documents])
            ->setPaper('a4', 'landscape');
    }

    private static function buildDocument(string $slug, int $id)
    {
        $object = DB::table($slug)->where('id', $id)->first();
        if (!$object) {
            return null;
        }

        $number = trim((string) ($object->number ?? '')) !== '' ? trim((string) $object->number) : (string) $object->id;
        $date = $object->delivery_date ? strtotime($object->delivery_date) : ($object->created_at ? strtotime($object->created_at) : time());
        $dateHuman = date('j', $date) . ' ' . self::MONTHS[(int) date('n', $date)] . ' ' . date('Y', $date) . ' г.';

        $seller = self::companyById(self::firstId($object->shipment_company_id ?? null) ?: self::dealShipmentCompanyId($slug, $id));
        $buyerCompany = self::companyById(self::firstId($object->company_id ?? null));
        $buyerContact = $buyerCompany ? null : self::contactById(self::firstId($object->contact_id ?? null));

        $sellerVat = null;
        if ($seller && Schema::hasColumn('companies', 'vat')) {
            $raw = self::listValue($seller->vat ?? null);
            $sellerVat = $raw !== null && $raw !== '' && is_numeric($raw) ? (float) $raw : null;
        }

        $products = json_decode((string) ($object->products ?? ''), true);
        $products = is_array($products) ? array_values(array_filter($products, 'is_array')) : [];
        $productVat = self::productVatMap($products);

        $rows = [];
        $totalNet = 0.0;
        $totalTax = 0.0;
        $totalWith = 0.0;
        foreach ($products as $product) {
            $count = (float) ($product['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $price = (float) ($product['price'] ?? 0);
            $productId = (int) ($product['id'] ?? 0);

            $info = $productVat[$productId] ?? null;
            $lineNds = self::listValue($product['nds'] ?? null);
            $lineIncluded = self::listValue($product['nds_included'] ?? null);
            if ($lineNds !== null && $lineNds !== '') {
                $rate = is_numeric($lineNds) ? (float) $lineNds : null;
            } elseif ($info) {
                $rate = $info['rate'];
            } else {
                $rate = $sellerVat;
            }
            if ($lineIncluded !== null && $lineIncluded !== '') {
                $included = $lineIncluded !== '0';
            } else {
                $included = $info ? $info['included'] : true;
            }

            if ($rate !== null && $rate > 0) {
                if ($included) {
                    $with = $price * $count;
                    $net = $with / (1 + $rate / 100);
                    $tax = $with - $net;
                } else {
                    $net = $price * $count;
                    $tax = $net * $rate / 100;
                    $with = $net + $tax;
                }
                $rateLabel = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%';
            } else {
                $net = $price * $count;
                $tax = 0.0;
                $with = $net;
                $rateLabel = 'Без НДС';
            }

            $rows[] = [
                'code' => $productId ?: '--',
                'name' => ShipmentService::plainName($product['name'] ?? ''),
                'count' => rtrim(rtrim(number_format($count, 3, ',', ''), '0'), ','),
                'price' => number_format($count > 0 ? $net / $count : 0, 2, ',', ' '),
                'net' => number_format($net, 2, ',', ' '),
                'rate' => $rateLabel,
                'tax' => $tax > 0 ? number_format($tax, 2, ',', ' ') : 'Без НДС',
                'with' => number_format($with, 2, ',', ' '),
            ];
            $totalNet += $net;
            $totalTax += $tax;
            $totalWith += $with;
        }

        $buyerName = $buyerCompany
            ? (self::plain($buyerCompany->full_name ?? '') ?: self::plainName($buyerCompany->name ?? ''))
            : ($buyerContact ? self::plainName($buyerContact->name ?? '') : '');
        $buyerAddress = $buyerCompany ? self::companyAddress($buyerCompany) : '';
        $buyerInnKpp = $buyerCompany
            ? trim(self::digits($buyerCompany->inn ?? '') . (self::digits($buyerCompany->kpp ?? '') !== '' ? '/' . self::digits($buyerCompany->kpp ?? '') : ''), '/')
            : ($buyerContact ? self::digits($buyerContact->inn ?? '') : '');

        return [
            'number' => $number,
            'date' => $dateHuman,
            'seller_name' => $seller ? (self::plain($seller->full_name ?? '') ?: self::plainName($seller->name ?? '')) : '',
            'seller_address' => $seller ? self::companyAddress($seller) : '',
            'seller_inn_kpp' => $seller
                ? trim(self::digits($seller->inn ?? '') . (self::digits($seller->kpp ?? '') !== '' ? '/' . self::digits($seller->kpp ?? '') : ''), '/')
                : '',
            'seller_director' => $seller ? self::plain($seller->director ?? '') : '',
            'seller_accountant' => $seller ? self::plain($seller->accountant ?? '') : '',
            'buyer_name' => $buyerName,
            'buyer_address' => $buyerAddress,
            'buyer_inn_kpp' => $buyerInnKpp,
            'consignee' => trim($buyerName . ($buyerAddress !== '' ? ', ' . $buyerAddress : '')),
            'rows' => $rows,
            'total_net' => number_format($totalNet, 2, ',', ' '),
            'total_tax' => $totalTax > 0 ? number_format($totalTax, 2, ',', ' ') : 'Без НДС',
            'total_with' => number_format($totalWith, 2, ',', ' '),
            'weight' => (float) ($object->weight ?? 0),
            'volume' => (float) ($object->volume ?? 0),
            'qr' => self::qrDataUri($slug, $id),
        ];
    }

    private static function qrDataUri(string $slug, int $id): ?string
    {
        if (!class_exists(\TCPDF2DBarcode::class)) {
            return null;
        }
        $tenant = tenant('id');
        $host = app()->runningInConsole() ? null : request()->getHost();
        $origin = $host ? 'https://' . $host : ($tenant ? 'https://' . $tenant . '.compas.pro' : 'https://compas.pro');
        $url = $origin . '/objects/' . $slug . '/' . $id . '?attach_employee=1';
        try {
            $png = (new \TCPDF2DBarcode($url, 'QRCODE,M'))->getBarcodePngData(6, 6, [0, 0, 0]);
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($png) && $png !== '' ? 'data:image/png;base64,' . base64_encode($png) : null;
    }

    private static function dealShipmentCompanyId(string $slug, int $id): ?int
    {
        try {
            $parent = ShipmentService::parentOf($slug, $id);
            if (!$parent || $parent[0] !== 'deals' || !Schema::hasColumn('deals', 'shipment_company_id')) {
                return null;
            }
            $value = DB::table('deals')->where('id', $parent[1])->value('shipment_company_id');

            return self::firstId($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function productVatMap(array $products): array
    {
        $ids = array_values(array_filter(array_map(fn ($p) => (int) ($p['id'] ?? 0), $products)));
        if (!count($ids) || !Schema::hasTable('products') || !Schema::hasColumn('products', 'nds')) {
            return [];
        }
        $hasIncluded = Schema::hasColumn('products', 'nds_included');
        $columns = $hasIncluded ? ['id', 'nds', 'nds_included'] : ['id', 'nds'];

        $map = [];
        foreach (DB::table('products')->whereIn('id', $ids)->get($columns) as $row) {
            $nds = self::listValue($row->nds);
            if ($nds === null || $nds === '') {
                continue;
            }
            $included = $hasIncluded ? self::listValue($row->nds_included) : null;
            $map[(int) $row->id] = [
                'rate' => is_numeric($nds) ? (float) $nds : null,
                'included' => $included === null || $included === '' ? true : $included !== '0',
            ];
        }

        return $map;
    }

    private static function companyById($id): ?Company
    {
        return $id ? Company::find($id) : null;
    }

    private static function contactById($id)
    {
        if (!$id || !Schema::hasTable('contacts')) {
            return null;
        }

        return DB::table('contacts')->where('id', $id)->first();
    }

    private static function companyAddress(Company $company): string
    {
        foreach (['address', 'fact_address'] as $field) {
            $value = $company->{$field} ?? null;
            if (is_string($value) && $value !== '') {
                if ($value[0] === '{' || $value[0] === '[') {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? ($decoded['text'] ?? ($decoded['value'] ?? '')) : $value;
                }
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private static function firstId($value): ?int
    {
        if (is_string($value) && ($value !== '') && ($value[0] === '[' || $value[0] === '{')) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? (reset($decoded) ?: null) : $value;
        }
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        return is_numeric($value) && (int) $value ? (int) $value : null;
    }

    private static function listValue($raw): ?string
    {
        if (is_string($raw) && is_array($decoded = json_decode($raw, true))) {
            $raw = $decoded[0] ?? null;
        }

        return $raw === null ? null : trim((string) $raw);
    }

    private static function plain($value): string
    {
        if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded['value'] ?? (is_scalar(reset($decoded)) ? reset($decoded) : '');
            }
        }

        return trim((string) $value);
    }

    private static function plainName($value): string
    {
        return ShipmentService::plainName($value);
    }

    private static function digits($value): string
    {
        return preg_replace('/\D/', '', (string) self::plain($value));
    }
}
