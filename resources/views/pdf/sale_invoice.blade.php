<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8"/>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        .bank td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        .goods th, .goods td { border: 1px solid #000; padding: 4px 6px; }
        .goods th { background: #f0f0f0; }
        .title { font-size: 13pt; font-weight: bold; margin: 14px 0 4px; }
        .hr { border-bottom: 2px solid #000; margin: 2px 0 10px; }
        .muted { color: #444; font-size: 8pt; }
        .nb { white-space: nowrap; }
        .right { text-align: right; }
        .center { text-align: center; }
        .sign { margin-top: 26px; }
        .sign td { padding: 10px 6px 0; }
        .sign .line { border-bottom: 1px solid #000; min-width: 150px; }
    </style>
</head>
<body>
    <table class="bank">
        <tr>
            <td style="width: 55%;">
                <div>{{ $bank->bank_name ?? '' }}</div>
                <div class="muted">Банк получателя</div>
            </td>
            <td style="width: 12%;">
                <div>БИК</div>
                <div>Сч. №</div>
            </td>
            <td style="width: 33%;">
                <div>{{ $bank->bic ?? '' }}</div>
                <div>{{ $bank->corr_account ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div>ИНН {{ $company->inn ?? '' }} @if(!empty($company->kpp)) КПП {{ $company->kpp }} @endif</div>
                <div>{{ $companyName }}</div>
                <div class="muted">Получатель</div>
            </td>
            <td>Сч. №</td>
            <td>{{ $bank->account ?? '' }}</td>
        </tr>
    </table>

    <div class="title">Счет на оплату № {{ $number }} от {{ $date }}</div>
    <div class="hr"></div>

    <p>
        <b>Поставщик:</b>
        {{ $companyName }}@if(!empty($company->inn)), ИНН {{ $company->inn }}@endif @if(!empty($company->kpp)), КПП {{ $company->kpp }}@endif @if(!empty($company->address)), {{ $company->address }}@endif
    </p>
    <p>
        <b>Покупатель:</b>
        {{ $buyer !== '' ? $buyer : '—' }}
    </p>
    @if($dealName !== '')
        <p><b>Основание:</b> Заказ покупателя «{{ $dealName }}» № {{ $dealId }}</p>
    @endif

    <table class="goods" style="margin-top: 8px;">
        <tr>
            <th style="width: 5%;">№</th>
            <th>Товары (работы, услуги)</th>
            <th style="width: 10%;">Кол-во</th>
            <th style="width: 15%;">Цена, руб.</th>
            <th style="width: 15%;">Сумма, руб.</th>
        </tr>
        @forelse($products as $i => $product)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $product['name'] ?? '' }}</td>
                <td class="center">{{ $product['count'] ?? '' }}</td>
                <td class="right nb">{{ number_format((float) ($product['price'] ?? 0), 2, ',', ' ') }}</td>
                <td class="right nb">{{ number_format((float) ($product['total'] ?? 0), 2, ',', ' ') }}</td>
            </tr>
        @empty
            <tr>
                <td class="center">1</td>
                <td>По заказу покупателя № {{ $dealId }}</td>
                <td class="center">1</td>
                <td class="right nb">{{ number_format($total, 2, ',', ' ') }}</td>
                <td class="right nb">{{ number_format($total, 2, ',', ' ') }}</td>
            </tr>
        @endforelse
        <tr>
            <td colspan="4" class="right" style="border: none; padding-top: 6px;"><b>Итого:</b></td>
            <td class="right nb" style="border: none; padding-top: 6px;"><b>{{ number_format($total, 2, ',', ' ') }}</b></td>
        </tr>
    </table>

    <p style="margin-top: 10px;">
        Всего наименований {{ max(count($products), 1) }}, на сумму {{ number_format($total, 2, ',', ' ') }} руб.
    </p>

    <table class="sign">
        <tr>
            <td style="width: 18%;"><b>Руководитель</b></td>
            <td class="line" style="width: 32%;">{{ $company->director ?? '' }}</td>
            <td style="width: 18%;"><b>Гл. бухгалтер</b></td>
            <td class="line" style="width: 32%;">{{ $company->accountant ?? '' }}</td>
        </tr>
    </table>
</body>
</html>
