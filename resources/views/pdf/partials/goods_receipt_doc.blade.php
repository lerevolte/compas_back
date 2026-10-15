<div class="rc">
    <table class="rc-qrs">
        <tr>
            <td class="rc-qr-box">
                @if(!empty($doc['qr']))
                    <table>
                        <tr>
                            <td class="rc-qr-img"><img src="{{ $doc['qr'] }}" alt=""></td>
                            <td class="rc-qr-text">QR-код для отгрузки на складе</td>
                        </tr>
                    </table>
                @endif
            </td>
            <td class="rc-qr-gap"></td>
            <td class="rc-qr-box">
                @if(!empty($doc['pay_qr']))
                    <table>
                        <tr>
                            <td class="rc-qr-img"><img src="{{ $doc['pay_qr'] }}" alt=""></td>
                            <td class="rc-qr-text">QR-код для оплаты клиентом</td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <div class="rc-title">Товарный чек № {{ $doc['number'] }} от {{ $doc['date'] }}</div>
    <div class="rc-hr"></div>

    <table class="rc-meta">
        <tr>
            <td class="rc-k">Грузополучатель:</td>
            <td class="b">{{ $doc['consignee'] }}</td>
        </tr>
        <tr>
            <td class="rc-k">Номер заказа:</td>
            <td class="b">{{ $doc['order_number'] }}</td>
        </tr>
    </table>

    <table class="rc-goods">
        <thead>
            <tr>
                <th style="width: 24px;">№</th>
                <th style="width: 80px;">Код</th>
                <th>Название товара</th>
                <th colspan="2" style="width: 90px;">Количество</th>
                <th style="width: 80px;">Цена</th>
                <th style="width: 90px;">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doc['rows'] as $row)
                <tr>
                    <td class="center">{{ $row['num'] }}</td>
                    <td class="nb">{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="rc-count nb">{{ $row['count'] }}</td>
                    <td class="rc-unit">{{ $row['unit'] }}</td>
                    <td class="right nb">{{ $row['price'] }}</td>
                    <td class="right nb">{{ $row['sum'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="rc-total">
        <tr>
            <td class="right b">Итого:</td>
            <td class="right b rc-total-sum">{{ $doc['total'] }}</td>
        </tr>
    </table>
    <div class="rc-hr"></div>

    <table class="rc-sign">
        <tr>
            <td class="rc-sign-k b">Отгружено:</td>
            <td class="rc-line"><div class="rc-line-in"></div><div class="rc-cap">подпись</div></td>
            <td class="rc-line rc-line_wide"><div class="rc-line-in"></div><div class="rc-cap">расшифровка подписи</div></td>
        </tr>
        <tr>
            <td class="rc-sign-k b">Принято:</td>
            <td class="rc-line"><div class="rc-line-in"></div><div class="rc-cap">подпись</div></td>
            <td class="rc-line rc-line_wide"><div class="rc-line-in"></div><div class="rc-cap">расшифровка подписи</div></td>
        </tr>
    </table>
    <div class="rc-mp">М.П.</div>
</div>
