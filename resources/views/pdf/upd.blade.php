<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8"/>
    <style>
        @page { margin: 14px 20px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 6.6pt; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        .doc { page-break-after: always; }
        .doc:last-child { page-break-after: auto; }
        .small { font-size: 5.4pt; color: #333; }
        .badge { border: 1px solid #000; padding: 3px 4px; font-weight: bold; text-align: center; }
        .status { border: 1px solid #000; width: 18px; height: 14px; display: inline-block; text-align: center; font-weight: bold; }
        .header-note { text-align: right; font-size: 5.4pt; }
        .head td { vertical-align: top; padding: 1px 3px; }
        .head .k { white-space: nowrap; }
        .head .v { border-bottom: 1px solid #000; width: 42%; }
        .goods th, .goods td { border: 1px solid #000; padding: 2px 3px; vertical-align: middle; }
        .goods th { font-weight: normal; text-align: center; font-size: 5.8pt; }
        .goods td { font-size: 6.4pt; }
        .goods .num { text-align: center; font-size: 5.4pt; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nb { white-space: nowrap; }
        .b { font-weight: bold; }
        .sign td { vertical-align: bottom; padding: 4px 4px 0; }
        .line { border-bottom: 1px solid #000; min-height: 12px; }
        .cap { font-size: 5.2pt; color: #333; text-align: center; }
        .half { width: 50%; vertical-align: top; }
        .footer-table td { padding: 2px 4px; vertical-align: bottom; }
        .section-line { border-bottom: 1px solid #000; }
    </style>
</head>
<body>
@foreach($documents as $doc)
    <div class="doc">
        <table>
            <tr>
                <td style="width: 13%; vertical-align: top;">
                    <div class="badge">Универсальный<br>передаточный<br>документ</div>
                    <div style="margin-top: 4px;">Статус: <span class="status">1</span></div>
                    <div class="small" style="margin-top: 3px;">
                        1 – счет-фактура и передаточный документ (акт)<br>
                        2 – передаточный документ (акт)
                    </div>
                </td>
                <td style="vertical-align: top; padding-left: 8px;">
                    <div class="header-note">
                        Приложение № 1 к постановлению Правительства Российской Федерации от 26 декабря 2011 г. № 1137<br>
                        (в редакции постановления Правительства Российской Федерации от 23 января 2026 г. № 26)
                    </div>
                    <table class="head">
                        <tr>
                            <td class="k b">Счет-фактура № {{ $doc['number'] }} от {{ $doc['date'] }}</td>
                            <td class="small nb">(1)</td>
                            <td style="width: 4%;"></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="k">Исправление № -- от --</td>
                            <td class="small nb">(1а)</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                    <table class="head">
                        <tr>
                            <td class="k">Продавец:</td>
                            <td class="v b">{{ $doc['seller_name'] }}</td>
                            <td class="small nb">(2)</td>
                            <td class="k">Покупатель:</td>
                            <td class="v b">{{ $doc['buyer_name'] }}</td>
                            <td class="small nb">(6)</td>
                        </tr>
                        <tr>
                            <td class="k">Адрес:</td>
                            <td class="v">{{ $doc['seller_address'] }}</td>
                            <td class="small nb">(2а)</td>
                            <td class="k">Адрес:</td>
                            <td class="v">{{ $doc['buyer_address'] }}</td>
                            <td class="small nb">(6а)</td>
                        </tr>
                        <tr>
                            <td class="k">ИНН/КПП продавца:</td>
                            <td class="v">{{ $doc['seller_inn_kpp'] }}</td>
                            <td class="small nb">(2б)</td>
                            <td class="k">ИНН/КПП покупателя:</td>
                            <td class="v">{{ $doc['buyer_inn_kpp'] }}</td>
                            <td class="small nb">(6б)</td>
                        </tr>
                        <tr>
                            <td class="k">Грузоотправитель и его адрес:</td>
                            <td class="v">он же</td>
                            <td class="small nb">(3)</td>
                            <td class="k">Валюта: наименование, код</td>
                            <td class="v">Российский рубль, 643</td>
                            <td class="small nb">(7)</td>
                        </tr>
                        <tr>
                            <td class="k">Грузополучатель и его адрес:</td>
                            <td class="v">{{ $doc['consignee'] }}</td>
                            <td class="small nb">(4)</td>
                            <td class="k">Идентификатор государственного контракта,<br>договора (соглашения) (при наличии):</td>
                            <td class="v"></td>
                            <td class="small nb">(8)</td>
                        </tr>
                        <tr>
                            <td class="k">Документ об отгрузке</td>
                            <td class="v">№ {{ $doc['number'] }} от {{ $doc['date'] }}</td>
                            <td class="small nb">(5а)</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="goods" style="margin-top: 5px;">
            <tr>
                <th rowspan="2" style="width: 7%;">Код товара/<br>работ, услуг</th>
                <th rowspan="2" style="width: 3%;">№<br>п/п</th>
                <th rowspan="2">Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</th>
                <th rowspan="2" style="width: 4%;">Код вида товара</th>
                <th colspan="2" style="width: 8%;">Единица измерения</th>
                <th rowspan="2" style="width: 6%;">Количество (объем)</th>
                <th rowspan="2" style="width: 7%;">Цена (тариф) за единицу измерения</th>
                <th rowspan="2" style="width: 9%;">Стоимость товаров (работ, услуг), имущественных прав без налога – всего</th>
                <th rowspan="2" style="width: 5%;">В том числе сумма акциза</th>
                <th rowspan="2" style="width: 5%;">Налоговая ставка</th>
                <th rowspan="2" style="width: 8%;">Сумма налога, предъявляемая покупателю</th>
                <th rowspan="2" style="width: 9%;">Стоимость товаров (работ, услуг), имущественных прав с налогом – всего</th>
                <th colspan="2" style="width: 8%;">Страна происхождения товара</th>
                <th rowspan="2" style="width: 8%;">Регистрационный номер декларации на товары или регистрационный номер партии товара, подлежащего прослеживаемости</th>
            </tr>
            <tr>
                <th>код</th>
                <th>условное обозначение (национальное)</th>
                <th>цифровой код</th>
                <th>краткое наименование</th>
            </tr>
            <tr>
                <td class="num">А</td>
                <td class="num">1</td>
                <td class="num">1а</td>
                <td class="num">1б</td>
                <td class="num">2</td>
                <td class="num">2а</td>
                <td class="num">3</td>
                <td class="num">4</td>
                <td class="num">5</td>
                <td class="num">6</td>
                <td class="num">7</td>
                <td class="num">8</td>
                <td class="num">9</td>
                <td class="num">10</td>
                <td class="num">10а</td>
                <td class="num">11</td>
            </tr>
            @foreach($doc['rows'] as $i => $row)
                <tr>
                    <td class="center nb">{{ $row['code'] }}</td>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="center">--</td>
                    <td class="center">796</td>
                    <td class="center">шт</td>
                    <td class="right nb">{{ $row['count'] }}</td>
                    <td class="right nb">{{ $row['price'] }}</td>
                    <td class="right nb">{{ $row['net'] }}</td>
                    <td class="center nb">Без акциза</td>
                    <td class="center nb">{{ $row['rate'] }}</td>
                    <td class="right nb">{{ $row['tax'] }}</td>
                    <td class="right nb">{{ $row['with'] }}</td>
                    <td class="center">--</td>
                    <td class="center">--</td>
                    <td class="center">--</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="8" class="b">Всего к оплате (9)</td>
                <td class="right nb b">{{ $doc['total_net'] }}</td>
                <td class="center b">Х</td>
                <td></td>
                <td class="right nb b">{{ $doc['total_tax'] }}</td>
                <td class="right nb b">{{ $doc['total_with'] }}</td>
                <td colspan="3"></td>
            </tr>
        </table>

        <table class="footer-table" style="margin-top: 4px;">
            <tr>
                <td style="width: 10%; vertical-align: top;">Документ составлен на 1 листе</td>
                <td style="width: 22%;">Руководитель организации<br>или иное уполномоченное лицо</td>
                <td style="width: 13%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                <td style="width: 13%;"><div class="line center b">{{ $doc['seller_director'] }}</div><div class="cap">(ф.и.о.)</div></td>
                <td style="width: 16%;">Главный бухгалтер<br>или иное уполномоченное лицо</td>
                <td style="width: 13%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                <td style="width: 13%;"><div class="line center b">{{ $doc['seller_accountant'] !== '' ? $doc['seller_accountant'] : $doc['seller_director'] }}</div><div class="cap">(ф.и.о.)</div></td>
            </tr>
        </table>

        <div class="section-line" style="margin: 3px 0;"></div>

        <table class="footer-table">
            <tr>
                <td style="width: 30%;">Основание передачи (сдачи) / получения (приемки)</td>
                <td><div class="line">--</div><div class="cap">(договор; доверенность и др.)</div></td>
                <td class="small nb">[8]</td>
            </tr>
            <tr>
                <td>Данные о транспортировке и грузе</td>
                <td>
                    <div class="line">
                        @if($doc['weight'] > 0)масса брутто: {{ rtrim(rtrim(number_format($doc['weight'], 3, ',', ' '), '0'), ',') }} кг@endif
                        @if($doc['volume'] > 0) объем: {{ rtrim(rtrim(number_format($doc['volume'] / 1000, 3, ',', ' '), '0'), ',') }} м³@endif
                    </div>
                    <div class="cap">(транспортная накладная, поручение экспедитору, экспедиторская / складская расписка и др. / масса нетто/брутто груза)</div>
                </td>
                <td class="small nb">[9]</td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="half" style="border-right: 1px solid #000; padding-right: 8px;">
                    <div class="b">Товар (груз) передал / услуги, результаты работ, права сдал</div>
                    <table class="footer-table">
                        <tr>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(должность)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                            <td style="width: 33%;"><div class="line center b">{{ $doc['seller_director'] }}</div><div class="cap">(ф.и.о.)</div></td>
                        </tr>
                    </table>
                    <div style="margin-top: 3px;">Дата отгрузки, передачи (сдачи) «{{ $doc['date'] }}» <span class="small">[11]</span></div>
                    <div style="margin-top: 3px;">Иные сведения об отгрузке, передаче</div>
                    <div class="line"></div>
                    <div style="margin-top: 3px;">Ответственный за правильность оформления факта хозяйственной жизни</div>
                    <table class="footer-table">
                        <tr>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(должность)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                            <td style="width: 33%;"><div class="line center b">{{ $doc['seller_director'] }}</div><div class="cap">(ф.и.о.)</div></td>
                        </tr>
                    </table>
                    <div style="margin-top: 3px;">Наименование экономического субъекта – составителя документа</div>
                    <div class="line">{{ $doc['seller_name'] }}@if($doc['seller_inn_kpp'] !== ''), ИНН/КПП {{ $doc['seller_inn_kpp'] }}@endif</div>
                    <div style="margin-top: 4px;">М.П.</div>
                </td>
                <td class="half" style="padding-left: 8px;">
                    <div class="b">Товар (груз) получил / услуги, результаты работ, права принял</div>
                    <table class="footer-table">
                        <tr>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(должность)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(ф.и.о.)</div></td>
                        </tr>
                    </table>
                    <div style="margin-top: 3px;">Дата получения (приемки) «___» ____________ 20___ года <span class="small">[16]</span></div>
                    <div style="margin-top: 3px;">Иные сведения о получении, приемке</div>
                    <div class="line"></div>
                    <div style="margin-top: 3px;">Ответственный за правильность оформления факта хозяйственной жизни</div>
                    <table class="footer-table">
                        <tr>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(должность)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(подпись)</div></td>
                            <td style="width: 33%;"><div class="line"></div><div class="cap">(ф.и.о.)</div></td>
                        </tr>
                    </table>
                    <div style="margin-top: 3px;">Наименование экономического субъекта – составителя документа</div>
                    <div class="line">{{ $doc['buyer_name'] }}@if($doc['buyer_inn_kpp'] !== ''), ИНН {{ $doc['buyer_inn_kpp'] }}@endif</div>
                    <div style="margin-top: 4px;">М.П.</div>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
