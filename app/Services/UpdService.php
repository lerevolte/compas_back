<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdService
{
    public const SLUGS = ['logistic_tasks', 'pickups'];

    public const MAIN_COPIES = ['upd' => 2, 'receipt' => 1, 'transfer' => 1];

    public const MONTHS = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня',
        7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];

    public static function output(string $slug, array $ids, bool $withDocs = false, ?array $docKeys = null, bool $includeUpd = true, ?int $mainCopies = null): ?string
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids)) {
            return null;
        }
        $transferIds = array_values(array_filter($ids, fn ($id) => ShipmentService::isTransfer($slug, $id)));
        $receiptIds = array_values(array_filter(array_diff($ids, $transferIds), fn ($id) => self::isReceipt($slug, $id)));
        $updIds = array_values(array_diff($ids, $receiptIds, $transferIds));

        if (!$withDocs && count($transferIds) && !count($receiptIds) && !count($updIds)) {
            $pdf = self::transferPdf($slug, $transferIds);

            return $pdf ? $pdf->output() : null;
        }
        if (!$withDocs && !count($transferIds)) {
            if (!count($receiptIds)) {
                $pdf = self::pdf($slug, $updIds);

                return $pdf ? $pdf->output() : null;
            }
            if (!count($updIds)) {
                $pdf = self::receiptPdf($slug, $receiptIds);

                return $pdf ? $pdf->output() : null;
            }
        }

        $attachmentCopies = self::attachmentCopies($docKeys);
        $attachments = [];
        if ($withDocs) {
            foreach (PrintAttachmentService::rows($slug, $ids) as $row) {
                if ($attachmentCopies !== null && !isset($attachmentCopies[(int) $row->id])) {
                    continue;
                }
                $attachments[(int) $row->object_id][] = [$row, $attachmentCopies[(int) $row->id] ?? (int) $row->copies];
            }
        }

        $parts = [];
        $temp = [];
        $writeTemp = fn (string $bytes) => self::writeTemp($bytes);
        foreach ($ids as $id) {
            if ($withDocs) {
                foreach (self::linkedDocumentFiles($slug, [$id], $docKeys) as $file => $copies) {
                    array_push($parts, ...array_fill(0, $copies, $file));
                }
            }
            if ($includeUpd) {
                $kind = in_array($id, $transferIds, true) ? 'transfer' : (in_array($id, $receiptIds, true) ? 'receipt' : 'upd');
                $pdf = $kind === 'transfer' ? self::transferPdf($slug, [$id]) : ($kind === 'receipt' ? self::receiptPdf($slug, [$id]) : self::pdf($slug, [$id]));
                $path = $pdf ? self::writeTemp($pdf->output()) : null;
                if ($path !== null) {
                    $temp[] = $path;
                    $copies = $mainCopies !== null ? PrintAttachmentService::copies($mainCopies) : self::MAIN_COPIES[$kind];
                    array_push($parts, ...array_fill(0, $copies, $path));
                }
            }
            foreach ($attachments[$id] ?? [] as [$row, $copies]) {
                $path = PrintAttachmentService::pdfPath($row, $writeTemp, $temp);
                if ($path !== null) {
                    array_push($parts, ...array_fill(0, PrintAttachmentService::copies($copies), $path));
                }
            }
        }

        try {
            if (!count($parts)) {
                return $includeUpd ? self::fallbackUpd($slug, $updIds, $receiptIds) : null;
            }
            if (count($parts) === 1) {
                $bytes = @file_get_contents($parts[0]);

                return $bytes === false ? ($includeUpd ? self::fallbackUpd($slug, $updIds, $receiptIds) : null) : $bytes;
            }

            return self::mergeFiles($parts) ?? ($includeUpd ? self::fallbackUpd($slug, $updIds, $receiptIds) : null);
        } finally {
            foreach ($temp as $path) {
                @unlink($path);
            }
        }
    }

    public static function isReceipt(string $slug, int $id): bool
    {
        if (!in_array($slug, self::SLUGS, true) || !Schema::hasTable($slug)) {
            return false;
        }
        $object = DB::table($slug)->where('id', $id)->first();

        return $object && !self::firstId($object->company_id ?? null);
    }

    public static function receiptPdf(string $slug, array $ids)
    {
        if (!in_array($slug, self::SLUGS, true) || !Schema::hasTable($slug)) {
            return null;
        }
        $documents = [];
        foreach (array_values(array_filter(array_map('intval', $ids))) as $id) {
            $document = self::buildReceipt($slug, $id);
            if ($document) {
                $documents[] = $document;
            }
        }
        if (!count($documents)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.goods_receipt', ['documents' => $documents])
            ->setPaper('a4', 'portrait');
    }

    public static function transferPdf(string $slug, array $ids)
    {
        if ($slug !== 'logistic_tasks' || !Schema::hasTable($slug)) {
            return null;
        }
        $documents = [];
        foreach (array_values(array_filter(array_map('intval', $ids))) as $id) {
            $document = self::buildTransfer($slug, $id);
            if ($document) {
                $documents[] = $document;
            }
        }
        if (!count($documents)) {
            return null;
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.transfer', ['documents' => $documents])
            ->setPaper('a4', 'portrait');
    }

    private static function buildTransfer(string $slug, int $id): ?array
    {
        $object = DB::table($slug)->where('id', $id)->first();
        if (!$object) {
            return null;
        }
        $date = $object->delivery_date ? strtotime($object->delivery_date) : ($object->created_at ? strtotime($object->created_at) : time());
        $order = self::orderNumber($slug, $id);
        $products = json_decode((string) ($object->products ?? ''), true);
        $products = is_array($products) ? array_values(array_filter($products, 'is_array')) : [];
        $rows = [];
        $num = 0;
        foreach ($products as $product) {
            $count = (float) ($product['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $productId = (int) ($product['id'] ?? 0);
            $rows[] = [
                'num' => ++$num,
                'code' => $productId ?: '--',
                'name' => ShipmentService::plainName($product['name'] ?? ''),
                'count' => rtrim(rtrim(number_format($count, 3, ',', ''), '0'), ','),
                'unit' => trim((string) ($product['unit'] ?? ($product['measure'] ?? ''))) ?: 'шт',
                'order' => $order === '—' ? '' : $order,
            ];
        }

        return [
            'number' => (string) $object->id,
            'date' => date('j', $date) . ' ' . self::MONTHS[(int) date('n', $date)] . ' ' . date('Y', $date) . ' г.',
            'sender' => StorehouseService::name(StorehouseService::valueOf($slug, $id, StorehouseService::WRITE_OFF)),
            'receiver' => StorehouseService::name(StorehouseService::valueOf($slug, $id, StorehouseService::RECEIPT)),
            'order_number' => self::orderLabel($slug, $id),
            'rows' => $rows,
            'qr' => self::qrDataUri($slug, $id),
        ];
    }

    private static function buildReceipt(string $slug, int $id): ?array
    {
        $object = DB::table($slug)->where('id', $id)->first();
        if (!$object) {
            return null;
        }
        $date = $object->delivery_date ? strtotime($object->delivery_date) : ($object->created_at ? strtotime($object->created_at) : time());
        $dateHuman = date('j', $date) . ' ' . self::MONTHS[(int) date('n', $date)] . ' ' . date('Y', $date) . ' г.';

        $consignee = '';
        $contact = self::contactById(self::firstId($object->contact_id ?? null));
        if ($contact) {
            $consignee = isset($contact->last_name)
                ? \App\Helpers\ValueHelper::personName($contact->name ?? '', $contact->last_name)
                : self::plainName($contact->name ?? '');
        }
        if ($consignee === '') {
            $company = self::companyById(self::firstId($object->company_id ?? null));
            $consignee = $company ? (self::plain($company->full_name ?? '') ?: self::plainName($company->name ?? '')) : '';
        }
        if ($consignee === '') {
            $consignee = 'физ. лицо';
        }

        $products = json_decode((string) ($object->products ?? ''), true);
        $products = is_array($products) ? array_values(array_filter($products, 'is_array')) : [];
        $rows = [];
        $total = 0.0;
        $num = 0;
        foreach ($products as $product) {
            $count = (float) ($product['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $price = (float) ($product['price'] ?? 0);
            $productId = (int) ($product['id'] ?? 0);
            $sum = $price * $count;
            $rows[] = [
                'num' => ++$num,
                'code' => $productId ?: '--',
                'name' => ShipmentService::plainName($product['name'] ?? ''),
                'count' => rtrim(rtrim(number_format($count, 3, ',', ''), '0'), ','),
                'unit' => trim((string) ($product['unit'] ?? ($product['measure'] ?? ''))) ?: 'шт',
                'price' => number_format($price, 2, ',', ' '),
                'sum' => number_format($sum, 2, ',', ' '),
            ];
            $total += $sum;
        }
        $number = (string) $object->id;

        return [
            'kind' => 'receipt',
            'number' => $number,
            'date' => $dateHuman,
            'consignee' => $consignee,
            'order_number' => self::orderLabel($slug, $id),
            'rows' => $rows,
            'total' => number_format($total, 2, ',', ' '),
            'qr' => self::qrDataUri($slug, $id),
            'pay_qr' => self::staticPayQrDataUri() ?? self::paymentQrDataUri($total, 'Оплата по товарному чеку № ' . $number . ' от ' . date('d.m.Y', $date)),
        ];
    }

    private static function orderNumber(string $slug, int $id): string
    {
        try {
            $parent = ShipmentService::parentOf($slug, $id);
            if ($parent && $parent[0] === 'deals' && Schema::hasTable('deals')) {
                $deal = DB::table('deals')->where('id', $parent[1])->first(['id', 'b24_id']);
                if ($deal) {
                    return trim((string) ($deal->b24_id ?? '')) ?: (string) $deal->id;
                }
            }
        } catch (\Throwable $e) {
        }

        return '—';
    }

    private static function orderLabel(string $slug, int $id): string
    {
        $order = self::orderNumber($slug, $id);

        return $order === '—' ? (string) $id : $order . ' / ' . $id;
    }

    private static function basisText(string $slug, int $id): string
    {
        if (!\App\Models\ObjectRelation::ready() || !Schema::hasTable('payment_invoices')) {
            return '';
        }
        try {
            $ids = \App\Models\ObjectRelation::where('source_slug', $slug)
                ->where('source_id', $id)
                ->where('target_slug', 'payment_invoices')
                ->pluck('target_id')
                ->all();
            $parent = ShipmentService::parentOf($slug, $id);
            if ($parent && $parent[0] === 'deals') {
                $ids = array_merge($ids, \App\Models\ObjectRelation::where('source_slug', 'deals')
                    ->where('source_id', $parent[1])
                    ->where('target_slug', 'payment_invoices')
                    ->pluck('target_id')
                    ->all());
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (!count($ids)) {
                return '';
            }
            $query = DB::table('payment_invoices')->whereIn('id', $ids)->orderBy('id');
            if (Schema::hasColumn('payment_invoices', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $parts = [];
            foreach ($query->get() as $invoice) {
                $number = trim((string) ($invoice->number ?? '')) !== '' ? trim((string) $invoice->number) : (string) $invoice->id;
                $date = $invoice->created_at ? date('d.m.Y', strtotime($invoice->created_at)) : '';
                $parts[] = 'Счет-договор № ' . $number . ($date !== '' ? ' от ' . $date : '');
            }

            return implode('; ', $parts);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function staticPayQrDataUri(): ?string
    {
        $tenant = function_exists('tenant') ? (string) tenant('id') : '';
        if ($tenant === '' || !preg_match('/^[a-z0-9_-]+$/i', $tenant)) {
            return null;
        }
        foreach (['png' => 'image/png', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg'] as $ext => $mime) {
            $path = resource_path('pdf/pay_qr/' . $tenant . '.' . $ext);
            if (is_file($path)) {
                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }

        return null;
    }

    private static function paymentQrDataUri(float $sum, string $purpose): ?string
    {
        if ($sum <= 0 || !class_exists(\TCPDF2DBarcode::class) || !Schema::hasTable('requisites')) {
            return null;
        }
        $org = DB::table('requisites')
            ->whereNull('deleted_at')
            ->orderByRaw('choosed_at IS NULL')
            ->orderByDesc('choosed_at')
            ->orderBy('id')
            ->first();
        if (!$org) {
            return null;
        }
        $inn = self::digits($org->inn ?? '');
        $bank = null;
        if ($inn !== '' && Schema::hasTable('bank_requisites') && Schema::hasColumn('companies', 'inn')) {
            $companyId = DB::table('companies')->whereNull('deleted_at')->where('inn', $inn)->value('id');
            if ($companyId) {
                $bank = DB::table('bank_requisites')->where('company_id', $companyId)->orderByRaw("is_default = '1' DESC")->orderBy('id')->first();
            }
        }
        $account = self::digits($bank->account ?? '') ?: self::digits($org->account ?? '');
        $bic = self::digits($bank->bic ?? '');
        if ($account === '' || $bic === '') {
            return null;
        }
        $clean = fn ($value) => trim(str_replace('|', ' ', (string) self::plain($value)));
        $fields = [
            'Name' => $clean($org->name ?? ''),
            'PersonalAcc' => $account,
            'BankName' => $clean($bank->bank_name ?? ''),
            'BIC' => $bic,
            'CorrespAcc' => self::digits($bank->corr_account ?? ''),
            'PayeeINN' => $inn,
            'KPP' => self::digits($org->kpp ?? ''),
            'Sum' => (string) (int) round($sum * 100),
            'Purpose' => $clean($purpose),
        ];
        $parts = ['ST00012'];
        foreach ($fields as $key => $value) {
            if ($value !== '') {
                $parts[] = $key . '=' . $value;
            }
        }
        return self::qrImage(implode('|', $parts));
    }

    private static function fallbackUpd(string $slug, array $ids, array $receiptIds = []): ?string
    {
        $pdf = self::pdf($slug, $ids, $receiptIds);

        return $pdf ? $pdf->output() : null;
    }

    public static function linkedDocumentFiles(string $slug, array $ids, ?array $docKeys = null): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!count($ids) || !\App\Models\ObjectRelation::ready()) {
            return [];
        }
        $targets = array_values(array_diff(array_keys(SaleDocumentService::TARGETS), SaleDocumentService::NOT_PRINTED));
        $docs = [];
        if ($docKeys !== null) {
            foreach ($docKeys as $key) {
                $parsed = self::parseDocKey((string) $key);
                if (!$parsed || !in_array($parsed[0], $targets, true)) {
                    continue;
                }
                $docs[$parsed[0] . '#' . $parsed[1]] = $parsed;
            }

            return self::filesOf($docs, null);
        }
        $own = \App\Models\ObjectRelation::where('source_slug', $slug)
            ->whereIn('source_id', $ids)
            ->whereIn('target_slug', $targets)
            ->orderBy('id')
            ->get(['target_slug', 'target_id']);
        foreach ($own as $relation) {
            $docs[$relation->target_slug . '#' . $relation->target_id] = [(string) $relation->target_slug, (int) $relation->target_id, 1];
        }
        $dealIds = \App\Models\ObjectRelation::where('source_slug', 'deals')
            ->where('target_slug', $slug)
            ->whereIn('target_id', $ids)
            ->pluck('source_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
        if (count($dealIds)) {
            $dealDocs = \App\Models\ObjectRelation::where('source_slug', 'deals')
                ->whereIn('source_id', $dealIds)
                ->whereIn('target_slug', $targets)
                ->orderBy('id')
                ->get(['target_slug', 'target_id']);
            foreach ($dealDocs as $relation) {
                $docs[$relation->target_slug . '#' . $relation->target_id] = [(string) $relation->target_slug, (int) $relation->target_id, 1];
            }
        }

        return self::filesOf($docs, null);
    }

    public static function parseDocKey(string $key): ?array
    {
        if (!preg_match('/^([a-z_]+)#(\d+)(?:\*(\d+))?$/', trim($key), $m)) {
            return null;
        }

        return [$m[1], (int) $m[2], PrintAttachmentService::copies($m[3] ?? null)];
    }

    private static function attachmentCopies(?array $docKeys): ?array
    {
        if ($docKeys === null) {
            return null;
        }
        $out = [];
        foreach ($docKeys as $key) {
            $parsed = self::parseDocKey((string) $key);
            if ($parsed && $parsed[0] === 'attachment') {
                $out[$parsed[1]] = $parsed[2];
            }
        }

        return $out;
    }

    private static function filesOf(array $docs, ?array $docKeys): array
    {
        $order = array_flip(array_keys(SaleDocumentService::TARGETS));
        uasort($docs, function ($a, $b) use ($order) {
            $byType = ($order[$a[0]] ?? 99) <=> ($order[$b[0]] ?? 99);

            return $byType !== 0 ? $byType : ($a[1] <=> $b[1]);
        });

        $bySlug = [];
        foreach ($docs as [$docSlug, $docId]) {
            $bySlug[$docSlug][] = $docId;
        }
        $rows = [];
        foreach ($bySlug as $docSlug => $docIds) {
            if (!Schema::hasTable($docSlug)) {
                continue;
            }
            foreach (DB::table($docSlug)->whereIn('id', $docIds)->get() as $row) {
                $rows[$docSlug . '#' . $row->id] = $row;
            }
        }

        $disk = \Storage::disk('public');
        $files = [];
        foreach ($docs as $key => [$docSlug, $docId, $copies]) {
            if ($docKeys !== null && !in_array($key, $docKeys, true)) {
                continue;
            }
            $row = $rows[$key] ?? null;
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
                if (is_file($path) && !isset($files[$path])) {
                    $files[$path] = PrintAttachmentService::copies($copies);
                }
            }
        }

        return $files;
    }

    private static function tmpDir(): string
    {
        $dir = storage_path('app/tmp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return is_dir($dir) && is_writable($dir) ? $dir : sys_get_temp_dir();
    }

    private static function writeTemp(string $bytes): ?string
    {
        $path = self::tmpDir() . '/upd_' . uniqid('', true) . '.pdf';
        if (@file_put_contents($path, $bytes) === false) {
            \Log::warning('upd merge: не удалось записать временный файл', ['dir' => self::tmpDir()]);
            return null;
        }

        return $path;
    }

    private static function mergeFiles(array $inputs): ?string
    {
        $gs = trim((string) @shell_exec('command -v gs 2>/dev/null'));
        if ($gs === '') {
            return null;
        }
        $out = self::tmpDir() . '/print_' . uniqid('', true) . '.pdf';
        $cmd = escapeshellarg($gs) . ' -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -sOutputFile=' . escapeshellarg($out)
            . ' ' . implode(' ', array_map('escapeshellarg', $inputs)) . ' 2>&1';
        @exec($cmd, $log, $code);
        $result = $code === 0 && is_file($out) && filesize($out) > 0 ? file_get_contents($out) : null;
        @unlink($out);
        if ($result === null) {
            \Log::warning('upd merge failed', ['code' => $code, 'log' => array_slice((array) $log, 0, 5)]);
        }

        return $result;
    }

    public static function pdf(string $slug, array $ids, array $receiptIds = [])
    {
        if (!in_array($slug, self::SLUGS, true) || !Schema::hasTable($slug)) {
            return null;
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        $receiptIds = array_values(array_filter(array_map('intval', $receiptIds)));
        if (!count($ids) && !count($receiptIds)) {
            return null;
        }

        $documents = [];
        foreach ($ids as $id) {
            $document = self::buildDocument($slug, $id);
            if ($document) {
                $documents[] = $document;
            }
        }
        foreach ($receiptIds as $id) {
            $document = self::buildReceipt($slug, $id);
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

        $number = (string) $object->id;
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
            'status' => $buyerCompany ? 1 : 2,
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
            'order_number' => self::orderLabel($slug, $id),
            'basis' => self::basisText($slug, $id),
        ];
    }

    private static function qrDataUri(string $slug, int $id): ?string
    {
        $tenant = tenant('id');
        $host = app()->runningInConsole() ? null : request()->getHost();
        $origin = $host ? 'https://' . $host : ($tenant ? 'https://' . $tenant . '.compas.pro' : 'https://compas.pro');

        return self::qrImage($origin . '/objects/' . $slug . '/' . $id . '?attach_employee=1');
    }

    public static function qrImage(string $data): ?string
    {
        if (!class_exists(\TCPDF2DBarcode::class)) {
            return null;
        }
        try {
            $png = (new \TCPDF2DBarcode($data, 'QRCODE,Q'))->getBarcodePngData(6, 6, [0, 0, 0]);
        } catch (\Throwable $e) {
            return null;
        }
        if (!is_string($png) || $png === '') {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(self::withLogo($png));
    }

    private static function withLogo(string $png): string
    {
        $path = resource_path('pdf/qr_logo.png');
        if (!function_exists('imagecreatefromstring') || !is_file($path)) {
            return $png;
        }
        try {
            $qr = @imagecreatefromstring($png);
            $logo = @imagecreatefrompng($path);
            if (!$qr || !$logo) {
                return $png;
            }
            $width = imagesx($qr);
            $height = imagesy($qr);
            $canvas = imagecreatetruecolor($width, $height);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $qr, 0, 0, 0, 0, $width, $height);
            $size = (int) round(min($width, $height) * 0.20);
            imagecopyresampled($canvas, $logo, (int) (($width - $size) / 2), (int) (($height - $size) / 2), 0, 0, $size, $size, imagesx($logo), imagesy($logo));
            ob_start();
            imagepng($canvas);
            $result = (string) ob_get_clean();
            imagedestroy($canvas);
            imagedestroy($qr);
            imagedestroy($logo);

            return $result !== '' ? $result : $png;
        } catch (\Throwable $e) {
            return $png;
        }
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
