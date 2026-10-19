<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8"/>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #000; font-size: 8.5pt; }
        .doc { page-break-after: always; }
        .doc:last-child { page-break-after: auto; }
        table { border-collapse: collapse; }
        .b { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .nb { white-space: nowrap; }
        .tr-head { width: 100%; }
        .tr-head td { vertical-align: top; }
        .tr-title { font-size: 13pt; font-weight: bold; padding-top: 4px; }
        .tr-order { font-size: 8.5pt; margin-top: 6px; }
        .tr-qr-box { width: 38%; }
        .tr-qr-box table { width: 100%; border: 1.5px solid #000; }
        .tr-qr-box td { padding: 6px; vertical-align: middle; }
        .tr-qr-img { width: 86px; }
        .tr-qr-img img { width: 80px; height: 80px; display: block; }
        .tr-qr-text { font-size: 12pt; font-weight: bold; line-height: 1.25; }
        .tr-hr { border-bottom: 2px solid #000; margin: 8px 0 10px; }
        .tr-meta { width: 100%; }
        .tr-meta td { padding: 3px 6px 3px 0; vertical-align: top; font-size: 9pt; }
        .tr-meta .tr-k { width: 90px; white-space: nowrap; }
        .tr-goods { width: 100%; margin-top: 10px; border-top: 2px solid #000; border-bottom: 2px solid #000; }
        .tr-goods th { border: 1px solid #000; padding: 4px; font-size: 8.5pt; font-weight: bold; text-align: center; }
        .tr-goods td { border: 1px solid #000; padding: 4px; font-size: 7.8pt; vertical-align: middle; }
        .tr-goods .tr-count { border-right: none; text-align: right; }
        .tr-goods .tr-unit { border-left: none; width: 28px; }
        .tr-sign { width: 100%; margin-top: 22px; }
        .tr-sign td { padding: 12px 6px 0 0; vertical-align: bottom; }
        .tr-sign-k { width: 14%; font-size: 9.5pt; font-weight: bold; }
        .tr-line-in { border-bottom: 1px solid #000; height: 12px; }
        .tr-cap { font-size: 6.5pt; color: #333; text-align: center; margin-top: 1px; }
        .tr-mp { margin: 10px 0 0 62%; font-size: 8pt; }
    </style>
</head>
<body>
@foreach($documents as $doc)
    <div class="doc">
        <table class="tr-head">
            <tr>
                <td>
                    <div class="tr-title">Перемещение запасов № {{ $doc['number'] }} от {{ $doc['date'] }}</div>
                    <div class="tr-order">Номер заказа: <span class="b">{{ $doc['order_number'] }}</span></div>
                </td>
                <td class="tr-qr-box">
                    @if(!empty($doc['qr']))
                        <table>
                            <tr>
                                <td class="tr-qr-img"><img src="{{ $doc['qr'] }}" alt=""></td>
                                <td class="tr-qr-text">QR-код для отгрузки на складе</td>
                            </tr>
                        </table>
                    @endif
                </td>
            </tr>
        </table>
        <div class="tr-hr"></div>

        <table class="tr-meta">
            <tr>
                <td class="tr-k">Отправитель:</td>
                <td class="b">{{ $doc['sender'] }}</td>
            </tr>
            <tr>
                <td class="tr-k">Получатель:</td>
                <td class="b">{{ $doc['receiver'] }}</td>
            </tr>
        </table>

        <table class="tr-goods">
            <thead>
                <tr>
                    <th style="width: 24px;">№</th>
                    <th style="width: 70px;">Код</th>
                    <th>Товары</th>
                    <th colspan="2" style="width: 90px;">Количество</th>
                    <th style="width: 60px;">Резерв</th>
                    <th style="width: 90px;">Заказ<br>покупателя</th>
                </tr>
            </thead>
            <tbody>
                @foreach($doc['rows'] as $row)
                    <tr>
                        <td class="center">{{ $row['num'] }}</td>
                        <td class="nb">{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="tr-count nb">{{ $row['count'] }}</td>
                        <td class="tr-unit">{{ $row['unit'] }}</td>
                        <td></td>
                        <td class="center nb">{{ $row['order'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="tr-sign">
            <tr>
                <td class="tr-sign-k">Отпустил</td>
                <td style="width: 26%;"><div class="tr-line-in"></div><div class="tr-cap">должность</div></td>
                <td style="width: 4%;"></td>
                <td style="width: 22%;"><div class="tr-line-in"></div><div class="tr-cap">подпись</div></td>
                <td style="width: 4%;"></td>
                <td style="width: 30%;"><div class="tr-line-in"></div><div class="tr-cap">расшифровка подписи</div></td>
            </tr>
            <tr>
                <td class="tr-sign-k">Принял</td>
                <td><div class="tr-line-in"></div><div class="tr-cap">должность</div></td>
                <td></td>
                <td><div class="tr-line-in"></div><div class="tr-cap">подпись</div></td>
                <td></td>
                <td><div class="tr-line-in"></div><div class="tr-cap">расшифровка подписи</div></td>
            </tr>
        </table>
        <div class="tr-mp">М.П.</div>
    </div>
@endforeach
</body>
</html>
