<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8"/>
    <style>
        @page { margin: 16px 26px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 7.4pt; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        .head td { vertical-align: top; }
        .logo { width: 22%; text-align: center; vertical-align: middle !important; }
        .logo img { max-width: 130px; max-height: 56px; }
        .bank td { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
        .bank .lbl { font-size: 7.5pt; color: #333; }
        .title { font-size: 11.5pt; font-weight: bold; margin: 7px 0 3px; }
        .hr { border-bottom: 2px solid #000; margin: 2px 0 6px; }
        .party td { padding: 2px 0; vertical-align: top; }
        .party .k { width: 15%; }
        .party .v { font-weight: bold; }
        .offer { margin: 4px 0 3px; font-size: 6.6pt; line-height: 1.15; }
        .goods th, .goods td { border: 1px solid #000; padding: 2px 4px; }
        .goods th { font-weight: bold; }
        .totals td { padding: 1px 5px; }
        .totals .k { text-align: right; font-weight: bold; }
        .totals .v { text-align: right; width: 22%; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nb { white-space: nowrap; }
        .terms { font-size: 5.4pt; line-height: 1.1; margin-top: 4px; text-align: justify; }
        .terms p { margin: 0 0 1px; }
        .sign { margin-top: 6px; border-top: 2px solid #000; padding-top: 4px; }
        .sign td { vertical-align: bottom; padding: 3px 4px 0; }
        .sign .role { width: 16%; font-weight: bold; font-size: 9pt; }
        .sign .line { border-bottom: 1px solid #000; height: 30px; text-align: center; position: relative; }
        .sign .cap { font-size: 7pt; color: #333; text-align: center; padding-top: 2px; }
        .sign .name { font-weight: bold; text-align: center; }
        .sign img.s { max-height: 30px; max-width: 95px; }
        .sign .stamp { width: 18%; text-align: center; vertical-align: middle; }
        .sign .stamp img { width: 95px; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td class="logo">@if($logo)<img src="{{ $logo }}" alt="">@endif</td>
            <td>
                <table class="bank">
                    <tr>
                        <td style="width: 58%;">
                            <div>{{ $bank->bank_name ?? '' }}</div>
                            <div class="lbl">Банк получателя</div>
                        </td>
                        <td style="width: 12%;">
                            <div>БИК</div>
                            <div>Сч. №</div>
                        </td>
                        <td style="width: 30%;">
                            <div>{{ $bank->bic ?? '' }}</div>
                            <div>{{ $bank->corr_account ?? '' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div>ИНН {{ $org->inn ?? '' }} @if(!empty($org->kpp)) &nbsp; КПП {{ $org->kpp }} @endif</div>
                            <div>{{ $org->name ?? '' }}</div>
                            <div class="lbl">Получатель</div>
                        </td>
                        <td>Сч. №</td>
                        <td>{{ $bank->account ?? ($org->account ?? '') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="title">Счет-Договор № {{ $number }} от {{ $date }} г</div>
    <div class="hr"></div>

    <table class="party">
        <tr>
            <td class="k">Поставщик<br>(исполнитель):</td>
            <td class="v">{{ $org->name ?? '' }}@if(!empty($org->inn)), &nbsp;ИНН {{ $org->inn }}@endif @if(!empty($org->kpp)), &nbsp;КПП {{ $org->kpp }}@endif @if(!empty($org->address) || !empty($org->fact_address)), &nbsp;{{ $org->address ?: $org->fact_address }}@endif</td>
        </tr>
        <tr>
            <td class="k">Покупатель<br>(заказчик):</td>
            <td class="v">
                @if($companyName !== ''){{ $companyName }}@if(!empty($company->inn)), &nbsp;ИНН {{ $company->inn }}@endif @if(!empty($company->kpp)), &nbsp;КПП {{ $company->kpp }}@endif @if(!empty($company->address)), &nbsp;{{ $company->address }}@endif @if($companyPhone !== ''), &nbsp;тел.: {{ $companyPhone }}@endif @if($buyer !== '') ({{ $buyer }})@endif @else{{ $buyer !== '' ? $buyer : '—' }}@endif
            </td>
        </tr>
    </table>

    <div class="offer">
        Настоящий счет является письменной офертой Поставщика (ст. 432–444 ГК РФ). Оплата счета Покупателем является акцептом оферты и означает его полное и безоговорочное согласие с условиями договора (п. 3 ст. 434, ст. 438 ГК РФ). Предмет счета-договора:
    </div>

    <table class="goods">
        <tr>
            <th style="width: 4%;">№</th>
            <th>Товар (Услуга)</th>
            <th style="width: 9%;">Кол-во</th>
            <th style="width: 8%;">Ед.</th>
            <th style="width: 9%;">НДС, %</th>
            <th style="width: 12%;">Цена</th>
            <th style="width: 14%;">Сумма</th>
        </tr>
        @forelse($products as $i => $product)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $product['name'] ?? '' }}</td>
                <td class="right">{{ $product['count'] ?? '' }}</td>
                <td class="center">{{ $product['unit'] ?? 'шт' }}</td>
                <td class="right">{{ $product['tax_label'] ?? '' }}</td>
                <td class="right nb">{{ number_format((float) ($product['price'] ?? 0), 2, '.', ' ') }}</td>
                <td class="right nb">{{ number_format((float) ($product['total'] ?? 0), 2, '.', ' ') }}</td>
            </tr>
        @empty
            <tr>
                <td class="center">1</td>
                <td>@if($dealId !== '')По заказу покупателя № {{ $dealId }}@else Товары и услуги по счету № {{ $number }}@endif</td>
                <td class="right">1</td>
                <td class="center">шт</td>
                <td class="right">{{ $vatRate === null ? 'Без НДС' : rtrim(rtrim(number_format($vatRate, 2, '.', ''), '0'), '.') }}</td>
                <td class="right nb">{{ number_format($total, 2, '.', ' ') }}</td>
                <td class="right nb">{{ number_format($total, 2, '.', ' ') }}</td>
            </tr>
        @endforelse
    </table>

    <table class="totals" style="margin-top: 6px;">
        <tr><td class="k">Итого:</td><td class="v nb">{{ number_format($total, 2, '.', ' ') }} руб.</td></tr>
        <tr><td class="k">{{ ($vatOnTop ?? 0) > 0 ? 'НДС:' : 'В том числе НДС:' }}</td><td class="v nb">{{ $vatTotal > 0 ? number_format($vatTotal, 2, '.', ' ') . ' руб.' : 'Без НДС' }}</td></tr>
        <tr><td class="k">Всего к оплате:</td><td class="v nb" style="font-size: 10pt;">{{ number_format($grandTotal ?? $total, 2, '.', ' ') }} руб.</td></tr>
    </table>

    <p style="margin: 5px 0 0;">Всего наименований {{ max(count($products), 1) }}, на сумму {{ number_format($grandTotal ?? $total, 2, '.', ' ') }} руб.</p>
    <p style="margin: 2px 0 0;"><b>{{ $totalWords }}</b></p>

    <div class="terms">
        <p><b>1.</b> Цена товара: Цены товара (включая НДС, упаковку и маркировку) согласованы в настоящем Счет-договоре. Цены в разных партиях могут отличаться. При просрочке оплаты расчеты производятся по ценам, действующим на дату отгрузки.</p>
        <p><b>2.</b> Порядок расчетов: Счет действителен для оплаты в течение 3 банковских дней. Датой платежа считается поступление 100% средств на расчетный счет Поставщика. Частичная оплата, оплата третьими лицами или без указания номера счета не допускаются. При нарушении этих условий заявка аннулируется. Для последующей покупки оформляется и оплачивается новая заявка.</p>
        <p><b>3.</b> Поставка: Товар отпускается на самовывоз либо доставляется силами Поставщика за счет Покупателя в адрес Грузополучателя. Моментом поставки считается передача товара представителю Покупателя. Право собственности и риски гибели/повреждения товара переходят к Покупателю с момента подписания УПД, накладной или перевозочных документов.</p>
        <p><b>3.1.</b> Самовывоз: Покупатель обязан вывезти товар в течение 5 рабочих дней после оплаты и уведомления о готовности к забору. Представитель Покупателя должен иметь паспорт и доверенность, иначе товар не отпускается.</p>
        <p><b>3.2.</b> Перегруз: Покупатель обязан подавать транспорт под погрузку с учетом технических характеристик в свидетельстве о регистрации ТС. При предоставлении недостоверных данных ответственность за перегруз полностью ложится на Покупателя. Он возмещает Поставщику все убытки в размере законного штрафа за перегруз ТС.</p>
        <p><b>3.3.</b> Доставка: Расходы на доставку оговариваются отдельно. Адрес Покупатель сообщает письменно до выставления счета и не позднее 2 рабочих дней до поставки. Разгрузка осуществляется силами Покупателя либо Поставщика (при этом Покупатель обязан обеспечить условия для разгрузки из раздела «Дополнительные условия разгрузок» по ссылке Условия разгрузки OPT6). На разгрузку отводится 60 минут с момента оповещения, далее действует штраф 1700 руб./час. При отсутствии представителя, документов, связи (от 3 звонков) или невыходе на связь после загрузки машины на складе Поставщика, услуга считается оказанной, товар возвращается на склад Поставщика, а повторная доставка оплачивается отдельно.</p>
        <p><b>4.</b> Хранение: По истечении срока самовывоза (п. 3.1) за каждый день хранения начисляется неустойка в размере 0,3% от суммы счета. При нарушении условий оплаты и вывоза наличие товара на складе не гарантируется.</p>
        <p><b>5.</b> Приемка: Покупатель обязан проверить количество и качество товара в момент передачи груза и подписания УПД. При обнаружении дефектов или некомплектности составляется двусторонний Акт. Без Акта претензии по видимым недостаткам не принимаются.</p>
        <p><b>6.</b> Подписание УПД (накладной) подтверждает согласие Покупателя с комплектностью и надлежащим качеством товара в полном объеме.</p>
        <p><b>7.</b> Возврат средств: При невозможности поставки возврат денег производится на основании оригинала официального письма Покупателя.</p>
    </div>

    <table class="sign">
        <tr>
            <td class="role">Руководитель</td>
            <td style="width: 30%;">
                <div class="line">@if($directorSignature)<img class="s" src="{{ $directorSignature }}" alt="">@endif</div>
                <div class="cap">подпись</div>
            </td>
            <td class="stamp" rowspan="2">@if($stamp)<img src="{{ $stamp }}" alt="">@endif</td>
            <td>
                <div class="line name" style="height: auto; padding-top: 18px;">{{ $org->director ?? '' }}</div>
                <div class="cap">расшифровка подписи</div>
            </td>
        </tr>
        <tr>
            <td class="role">Бухгалтер</td>
            <td>
                <div class="line">@if($accountantSignature)<img class="s" src="{{ $accountantSignature }}" alt="">@endif</div>
                <div class="cap">подпись</div>
            </td>
            <td>
                <div class="line name" style="height: auto; padding-top: 18px;">{{ $org->accountant ?? '' }}</div>
                <div class="cap">расшифровка подписи</div>
            </td>
        </tr>
    </table>
</body>
</html>
