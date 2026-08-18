<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class InstallSabyModule extends Command
{
    protected $signature = 'saby:install
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Установить модуль «Транспортные накладные Saby»: таблицы saby_config/saby_waybills и поля маршрута, автопарка, сотрудника, товара';

    private const TARE_TYPES = [
        ['value' => '1A', 'label' => '1A — Барабан стальной'],
        ['value' => '1B', 'label' => '1B — Барабан алюминиевый'],
        ['value' => '1D', 'label' => '1D — Барабан фанерный'],
        ['value' => '1F', 'label' => '1F — Контейнер гибкий'],
        ['value' => '1G', 'label' => '1G — Барабан фибровый'],
        ['value' => '1W', 'label' => '1W — Барабан деревянный'],
        ['value' => '2C', 'label' => '2C — Бочка деревянная (емкостью около 164 л)'],
        ['value' => '3A', 'label' => '3A — Канистра стальная'],
        ['value' => '3H', 'label' => '3H — Канистра пластмассовая'],
        ['value' => '43', 'label' => '43 — Мешок большой для крупноразмерных навалочных грузов'],
        ['value' => '44', 'label' => '44 — Мешок полиэтиленовый'],
        ['value' => '4A', 'label' => '4A — Коробка стальная'],
        ['value' => '4B', 'label' => '4B — Коробка алюминиевая'],
        ['value' => '4C', 'label' => '4C — Коробка из естественной древесины'],
        ['value' => '4D', 'label' => '4D — Коробка фанерная'],
        ['value' => '4F', 'label' => '4F — Коробка из древесного материала'],
        ['value' => '4G', 'label' => '4G — Коробка из фибрового картона'],
        ['value' => '4H', 'label' => '4H — Коробка пластмассовая'],
        ['value' => '5H', 'label' => '5H — Мешок из полимерной ткани'],
        ['value' => '5L', 'label' => '5L — Мешок текстильный'],
        ['value' => '5M', 'label' => '5M — Мешок бумажный'],
        ['value' => '6H', 'label' => '6H — Комбинированная упаковка: пластмассовый сосуд'],
        ['value' => '6P', 'label' => '6P — Комбинированная упаковка: стеклянный сосуд'],
        ['value' => '7A', 'label' => '7A — Ящик автомобильный'],
        ['value' => '7B', 'label' => '7B — Ящик деревянный'],
        ['value' => '8A', 'label' => '8A — Поддон деревянный'],
        ['value' => '8B', 'label' => '8B — Ящик деревянный'],
        ['value' => '8C', 'label' => '8C — Пачка деревянная'],
        ['value' => 'AA', 'label' => 'AA — Контейнер средней грузоподъемности для массовых грузов из жесткой пластмассы'],
        ['value' => 'AB', 'label' => 'AB — Сосуд фибровый'],
        ['value' => 'AC', 'label' => 'AC — Сосуд бумажный'],
        ['value' => 'AD', 'label' => 'AD — Сосуд деревянный'],
        ['value' => 'AE', 'label' => 'AE — Аэрозольная упаковка'],
        ['value' => 'AF', 'label' => 'AF — Поддон модульный с обечайкой 80 x 60 см'],
        ['value' => 'AG', 'label' => 'AG — Поддон в термоусадочной пленке'],
        ['value' => 'AH', 'label' => 'AH — Поддон 100 x 110 см'],
        ['value' => 'AI', 'label' => 'AI — Грейферный ковш'],
        ['value' => 'AJ', 'label' => 'AJ — Кулек'],
        ['value' => 'AL', 'label' => 'AL — Шар'],
        ['value' => 'AM', 'label' => 'AM — Ампула незащищенная'],
        ['value' => 'AP', 'label' => 'AP — Ампула защищенная'],
        ['value' => 'AT', 'label' => 'AT — Пульверизатор'],
        ['value' => 'AV', 'label' => 'AV — Капсула'],
        ['value' => 'B4', 'label' => 'B4 — Лента'],
        ['value' => 'BA', 'label' => 'BA — Бочка (емкостью около 164 л)'],
        ['value' => 'BB', 'label' => 'BB — Бобина'],
        ['value' => 'BC', 'label' => 'BC — Ящик решетчатый для бутылок'],
        ['value' => 'BD', 'label' => 'BD — Доска'],
        ['value' => 'BE', 'label' => 'BE — Пакет (пачка/связка)'],
        ['value' => 'BF', 'label' => 'BF — Баллон незащищенный'],
        ['value' => 'BG', 'label' => 'BG — Мешок'],
        ['value' => 'BH', 'label' => 'BH — Пачка (пакет/связка)'],
        ['value' => 'BI', 'label' => 'BI — Бункер'],
        ['value' => 'BJ', 'label' => 'BJ — Бадья'],
        ['value' => 'BK', 'label' => 'BK — Корзина'],
        ['value' => 'BL', 'label' => 'BL — Кипа спрессованная'],
        ['value' => 'BM', 'label' => 'BM — Чан'],
        ['value' => 'BN', 'label' => 'BN — Кипа неспрессованная'],
        ['value' => 'BO', 'label' => 'BO — Бутылка цилиндрическая незащищенная'],
        ['value' => 'BP', 'label' => 'BP — Баллон защищенный'],
        ['value' => 'BQ', 'label' => 'BQ — Бутылка цилиндрическая защищенная'],
        ['value' => 'BR', 'label' => 'BR — Брус (брусок)'],
        ['value' => 'BS', 'label' => 'BS — Бутылка с выпуклыми стенками незащищенная'],
        ['value' => 'BT', 'label' => 'BT — Рулон (обивочного или настилочного материала)'],
        ['value' => 'BU', 'label' => 'BU — Бочка для вина или пива'],
        ['value' => 'BV', 'label' => 'BV — Бутылка с выпуклыми стенками защищенная'],
        ['value' => 'BW', 'label' => 'BW — Коробка для жидкостей'],
        ['value' => 'BX', 'label' => 'BX — Коробка'],
        ['value' => 'BY', 'label' => 'BY — Доска в пакете/пачке/связке'],
        ['value' => 'BZ', 'label' => 'BZ — Брус (брусок) в пакете/пачке/связке'],
        ['value' => 'CA', 'label' => 'CA — Банка жестяная прямоугольная (емкостью менее 5 л)'],
        ['value' => 'CB', 'label' => 'CB — Ящик решетчатый для пива'],
        ['value' => 'CC', 'label' => 'CC — Бидон'],
        ['value' => 'CD', 'label' => 'CD — Банка жестяная с ручкой и выпускным отверстием (емкостью менее 5 л)'],
        ['value' => 'CE', 'label' => 'CE — Корзина рыбацкая'],
        ['value' => 'CF', 'label' => 'CF — Кофр'],
        ['value' => 'CG', 'label' => 'CG — Клеть'],
        ['value' => 'CH', 'label' => 'CH — Сундук'],
        ['value' => 'CI', 'label' => 'CI — Банка жестяная для сухих продуктов (массой до 2,2 кг)'],
        ['value' => 'CJ', 'label' => 'CJ — Гроб'],
        ['value' => 'CK', 'label' => 'CK — Бочка'],
        ['value' => 'CL', 'label' => 'CL — Бухта'],
        ['value' => 'CM', 'label' => 'CM — Кардная лента'],
        ['value' => 'CN', 'label' => 'CN — Контейнер, прочее транспортировочное оборудование, кроме поименованного'],
        ['value' => 'CO', 'label' => 'CO — Бутыль оплетенная незащищенная'],
        ['value' => 'CP', 'label' => 'CP — Бутыль оплетенная защищенная'],
        ['value' => 'CQ', 'label' => 'CQ — Кассета'],
        ['value' => 'CR', 'label' => 'CR — Ящик решетчатый (или обрешетка)'],
        ['value' => 'CS', 'label' => 'CS — Ящик'],
        ['value' => 'CT', 'label' => 'CT — Коробка картонная'],
        ['value' => 'CU', 'label' => 'CU — Чаша'],
        ['value' => 'CV', 'label' => 'CV — Чехол'],
        ['value' => 'CW', 'label' => 'CW — Клеть роликовая'],
        ['value' => 'CX', 'label' => 'CX — Банка жестяная цилиндрическая (емкостью менее 5 л)'],
        ['value' => 'CY', 'label' => 'CY — Цилиндр'],
        ['value' => 'CZ', 'label' => 'CZ — Брезент'],
        ['value' => 'DA', 'label' => 'DA — Ящик решетчатый многослойный пластмассовый'],
        ['value' => 'DB', 'label' => 'DB — Ящик решетчатый многослойный деревянный'],
        ['value' => 'DC', 'label' => 'DC — Ящик решетчатый многослойный картонный'],
        ['value' => 'DG', 'label' => 'DG — Клеть (многооборотная) Общего фонда транспортировочного оборудования ЕС'],
        ['value' => 'DH', 'label' => 'DH — Коробка (многооборотная) из Общего фонда транспортировочного оборудования ЕС, Еврокоробка'],
        ['value' => 'DI', 'label' => 'DI — Барабан железный'],
        ['value' => 'DJ', 'label' => 'DJ — Бутыль оплетенная большая (от 9 до 54 л) незащищенная'],
        ['value' => 'DK', 'label' => 'DK — Ящик решетчатый для массовых грузов картонный'],
        ['value' => 'DL', 'label' => 'DL — Ящик решетчатый для массовых грузов пластмассовый'],
        ['value' => 'DM', 'label' => 'DM — Ящик решетчатый для массовых грузов деревянный'],
        ['value' => 'DN', 'label' => 'DN — Дозатор'],
        ['value' => 'DP', 'label' => 'DP — Бутыль оплетенная большая (от 9 до 54 л) защищенная'],
        ['value' => 'DR', 'label' => 'DR — Барабан'],
        ['value' => 'DS', 'label' => 'DS — Лоток с одним настилом без покрытия пластмассовый'],
        ['value' => 'DT', 'label' => 'DT — Лоток с одним настилом без покрытия деревянный'],
        ['value' => 'DU', 'label' => 'DU — Лоток с одним настилом без покрытия полистироловый'],
        ['value' => 'DV', 'label' => 'DV — Лоток с одним настилом без покрытия картонный'],
        ['value' => 'DW', 'label' => 'DW — Лоток с двумя настилами без покрытия пластмассовый'],
        ['value' => 'DX', 'label' => 'DX — Лоток с двумя настилами без покрытия деревянный'],
        ['value' => 'DY', 'label' => 'DY — Лоток с двумя настилами без покрытия картонный'],
        ['value' => 'EC', 'label' => 'EC — Мешок пластмассовый'],
        ['value' => 'ED', 'label' => 'ED — Ящик с поддоном'],
        ['value' => 'EE', 'label' => 'EE — Ящик с поддоном деревянный'],
        ['value' => 'EF', 'label' => 'EF — Ящик с поддоном картонный'],
        ['value' => 'EG', 'label' => 'EG — Ящик с поддоном пластмассовый'],
        ['value' => 'EH', 'label' => 'EH — Ящик с поддоном металлический'],
        ['value' => 'EI', 'label' => 'EI — Ящик изотермический'],
        ['value' => 'EN', 'label' => 'EN — Конверт'],
        ['value' => 'FC', 'label' => 'FC — Ящик решетчатый для фруктов'],
        ['value' => 'FD', 'label' => 'FD — Ящик решетчатый (или обрешетка) рамный'],
        ['value' => 'FE', 'label' => 'FE — Мягкий мешок, гибкая цистерна'],
        ['value' => 'FI', 'label' => 'FI — Бочонок (емкостью около 41 л)'],
        ['value' => 'FL', 'label' => 'FL — Фляга'],
        ['value' => 'FO', 'label' => 'FO — Сундучок'],
        ['value' => 'FP', 'label' => 'FP — Кассета с пленкой (фильмпак)'],
        ['value' => 'FR', 'label' => 'FR — Рама'],
        ['value' => 'FT', 'label' => 'FT — Контейнер для пищевых продуктов'],
        ['value' => 'FX', 'label' => 'FX — Мешок, гибкий контейнер'],
        ['value' => 'GB', 'label' => 'GB — Баллон газовый'],
        ['value' => 'GI', 'label' => 'GI — Балка'],
        ['value' => 'GL', 'label' => 'GL — Контейнер галлон'],
        ['value' => 'GR', 'label' => 'GR — Сосуд стеклянный'],
        ['value' => 'GY', 'label' => 'GY — Мешок из мешковины'],
        ['value' => 'GZ', 'label' => 'GZ — Балка в пакете/пачке/связке'],
        ['value' => 'HA', 'label' => 'HA — Корзина с ручкой пластмассовая'],
        ['value' => 'HB', 'label' => 'HB — Корзина с ручкой из древесины'],
        ['value' => 'HC', 'label' => 'HC — Корзина с ручкой картонная'],
        ['value' => 'HG', 'label' => 'HG — Бочка емкостью 238 л (хогсхед)'],
        ['value' => 'HN', 'label' => 'HN — Крюк'],
        ['value' => 'HR', 'label' => 'HR — Корзина с крышкой'],
        ['value' => 'IA', 'label' => 'IA — Упаковка демонстрационная деревянная'],
        ['value' => 'IB', 'label' => 'IB — Упаковка демонстрационная картонная'],
        ['value' => 'IC', 'label' => 'IC — Упаковка демонстрационная пластмассовая'],
        ['value' => 'ID', 'label' => 'ID — Упаковка демонстрационная металлическая'],
        ['value' => 'IE', 'label' => 'IE — Упаковка выставочная'],
        ['value' => 'IF', 'label' => 'IF — Упаковка выпрессованная'],
        ['value' => 'IG', 'label' => 'IG — Упаковка в оберточной бумаге'],
        ['value' => 'IH', 'label' => 'IH — Барабан пластмассовый'],
        ['value' => 'IK', 'label' => 'IK — Упаковка картонная с отверстиями для бутылок'],
        ['value' => 'IN', 'label' => 'IN — Слиток'],
        ['value' => 'IZ', 'label' => 'IZ — Слитки в пакете/пачке/связке'],
        ['value' => 'JB', 'label' => 'JB — Мешок большой'],
        ['value' => 'JC', 'label' => 'JC — Канистра прямоугольная'],
        ['value' => 'JG', 'label' => 'JG — Кувшин маленький'],
        ['value' => 'JR', 'label' => 'JR — Банка широкогорлая (емкостью около 4,5 л)'],
        ['value' => 'JT', 'label' => 'JT — Мешок джутовый'],
        ['value' => 'JY', 'label' => 'JY — Канистра цилиндрическая'],
        ['value' => 'KG', 'label' => 'KG — Бочонок (емкостью около 46 л)'],
        ['value' => 'KI', 'label' => 'KI — Набор'],
        ['value' => 'LE', 'label' => 'LE — Багаж'],
        ['value' => 'LG', 'label' => 'LG — Бревно'],
        ['value' => 'LT', 'label' => 'LT — Грузовая партия (лот)'],
        ['value' => 'LU', 'label' => 'LU — Ящик'],
        ['value' => 'LV', 'label' => 'LV — Короб деревянный (лифтван) около 220 x 115 x 220 см'],
        ['value' => 'LZ', 'label' => 'LZ — Бревно в пакете/пачке/связке'],
        ['value' => 'MA', 'label' => 'MA — Ящик металлический'],
        ['value' => 'MB', 'label' => 'MB — Пакет бумажный многослойный'],
        ['value' => 'MC', 'label' => 'MC — Ящик решетчатый для молока'],
        ['value' => 'ME', 'label' => 'ME — Контейнер металлический'],
        ['value' => 'MR', 'label' => 'MR — Сосуд металлический'],
        ['value' => 'MS', 'label' => 'MS — Мешок (куль) многослойный'],
        ['value' => 'MT', 'label' => 'MT — Мешок рогожный'],
        ['value' => 'MW', 'label' => 'MW — Сосуд с пластмассовым покрытием'],
        ['value' => 'MX', 'label' => 'MX — Спичечный коробок'],
        ['value' => 'NA', 'label' => 'NA — Нет сведений'],
        ['value' => 'NE', 'label' => 'NE — Неупакованный или нерасфасованный'],
        ['value' => 'NF', 'label' => 'NF — Неупакованный или нерасфасованный одноместный груз'],
        ['value' => 'NG', 'label' => 'NG — Неупакованный или нерасфасованный многоместный груз'],
        ['value' => 'NS', 'label' => 'NS — Гнездо (ячейка)'],
        ['value' => 'NT', 'label' => 'NT — Сетка'],
        ['value' => 'NU', 'label' => 'NU — Сетка трубчатая пластмассовая'],
        ['value' => 'NV', 'label' => 'NV — Сетка трубчатая текстильная'],
        ['value' => 'OT', 'label' => 'OT — Октабин'],
        ['value' => 'OU', 'label' => 'OU — Контейнер наружный'],
        ['value' => 'P2', 'label' => 'P2 — Лоток'],
        ['value' => 'PA', 'label' => 'PA — Пакет'],
        ['value' => 'PB', 'label' => 'PB — Поддон ящичный'],
        ['value' => 'PC', 'label' => 'PC — Бандероль'],
        ['value' => 'PD', 'label' => 'PD — Поддон модульный с обечайкой 80 x 100 см'],
        ['value' => 'PE', 'label' => 'PE — Поддон модульный с обечайкой 80 x 120 см'],
        ['value' => 'PF', 'label' => 'PF — Штабель'],
        ['value' => 'PG', 'label' => 'PG — Плита'],
        ['value' => 'PH', 'label' => 'PH — Кувшин большой'],
        ['value' => 'PI', 'label' => 'PI — Труба'],
        ['value' => 'PJ', 'label' => 'PJ — Корзина из шпона для ягод и фруктов'],
        ['value' => 'PK', 'label' => 'PK — Упаковка'],
        ['value' => 'PL', 'label' => 'PL — Ведро'],
        ['value' => 'PN', 'label' => 'PN — Доска толстая'],
        ['value' => 'PO', 'label' => 'PO — Пакет (мешочек)'],
        ['value' => 'PP', 'label' => 'PP — Штука'],
        ['value' => 'PR', 'label' => 'PR — Сосуд пластмассовый'],
        ['value' => 'PT', 'label' => 'PT — Горшок'],
        ['value' => 'PU', 'label' => 'PU — Лоток'],
        ['value' => 'PV', 'label' => 'PV — Труба в пакете/пачке/связке'],
        ['value' => 'PX', 'label' => 'PX — Поддон'],
        ['value' => 'PY', 'label' => 'PY — Плиты в пакете/пачке/связке'],
        ['value' => 'PZ', 'label' => 'PZ — Доска толстая в пакете/пачке/связке'],
        ['value' => 'QA', 'label' => 'QA — Барабан стальной с несъемным днищем'],
        ['value' => 'QB', 'label' => 'QB — Барабан стальной со съемным днищем'],
        ['value' => 'QC', 'label' => 'QC — Барабан алюминиевый с несъемным днищем'],
        ['value' => 'QD', 'label' => 'QD — Барабан алюминиевый со съемным днищем'],
        ['value' => 'QF', 'label' => 'QF — Барабан пластмассовый с несъемным днищем'],
        ['value' => 'QG', 'label' => 'QG — Барабан пластмассовый со съемным днищем'],
        ['value' => 'QH', 'label' => 'QH — Бочка (около 164 л) деревянная шпунтованная'],
        ['value' => 'QJ', 'label' => 'QJ — Бочка (около 164 л) деревянная со съемным днищем'],
        ['value' => 'QK', 'label' => 'QK — Канистра стальная с несъемным днищем'],
        ['value' => 'QL', 'label' => 'QL — Канистра стальная со съемным днищем'],
        ['value' => 'QM', 'label' => 'QM — Канистра пластмассовая с несъемным днищем'],
        ['value' => 'QN', 'label' => 'QN — Канистра пластмассовая со съемным днищем'],
        ['value' => 'QP', 'label' => 'QP — Коробка деревянная из естественной древесины обыкновенная'],
        ['value' => 'QQ', 'label' => 'QQ — Коробка деревянная из естественной древесины с плотно пригнанными стенками'],
        ['value' => 'QR', 'label' => 'QR — Коробка пенопластовая'],
        ['value' => 'QS', 'label' => 'QS — Коробка из твердой пластмассы'],
        ['value' => 'RD', 'label' => 'RD — Прут'],
        ['value' => 'RG', 'label' => 'RG — Кольцо'],
        ['value' => 'RJ', 'label' => 'RJ — Стойка, вешалка для одежды'],
        ['value' => 'RK', 'label' => 'RK — Стойка'],
        ['value' => 'RL', 'label' => 'RL — Катушка'],
        ['value' => 'RO', 'label' => 'RO — Рулон (полосового материала)'],
        ['value' => 'RT', 'label' => 'RT — Сетка типа используемой для овощей или фруктов'],
        ['value' => 'RZ', 'label' => 'RZ — Прут в пакете/пачке/связке'],
        ['value' => 'SA', 'label' => 'SA — Мешок (куль)'],
        ['value' => 'SB', 'label' => 'SB — Сляб'],
        ['value' => 'SC', 'label' => 'SC — Ящик решетчатый (или обрешетка) мелкий'],
        ['value' => 'SD', 'label' => 'SD — Шпиндель'],
        ['value' => 'SE', 'label' => 'SE — Сундук морской'],
        ['value' => 'SH', 'label' => 'SH — Пакетик'],
        ['value' => 'SI', 'label' => 'SI — Стеллаж'],
        ['value' => 'SK', 'label' => 'SK — Ящик каркасный'],
        ['value' => 'SL', 'label' => 'SL — Лист прокладной'],
        ['value' => 'SM', 'label' => 'SM — Лист металлический'],
        ['value' => 'SO', 'label' => 'SO — Шпулька'],
        ['value' => 'SP', 'label' => 'SP — Лист с пластмассовым покрытием'],
        ['value' => 'SS', 'label' => 'SS — Ящик стальной'],
        ['value' => 'ST', 'label' => 'ST — Лист'],
        ['value' => 'SU', 'label' => 'SU — Чемодан'],
        ['value' => 'SV', 'label' => 'SV — Конверт стальной'],
        ['value' => 'SW', 'label' => 'SW — В термоусадочной пленке'],
        ['value' => 'SX', 'label' => 'SX — Комплект'],
        ['value' => 'SY', 'label' => 'SY — Гильза'],
        ['value' => 'SZ', 'label' => 'SZ — Лист в пакете/пачке/связке'],
        ['value' => 'T1', 'label' => 'T1 — Таблетка'],
        ['value' => 'TB', 'label' => 'TB — Кадка'],
        ['value' => 'TC', 'label' => 'TC — Чайная коробка'],
        ['value' => 'TD', 'label' => 'TD — Трубка или туба складывающаяся'],
        ['value' => 'TE', 'label' => 'TE — Шина'],
        ['value' => 'TG', 'label' => 'TG — Цистерна контейнер универсальный'],
        ['value' => 'TI', 'label' => 'TI — Бочка деревянная (емкостью около 200 л)'],
        ['value' => 'TK', 'label' => 'TK — Цистерна прямоугольная'],
        ['value' => 'TL', 'label' => 'TL — Кадка с крышкой'],
        ['value' => 'TN', 'label' => 'TN — Банка жестяная (консервная)'],
        ['value' => 'TO', 'label' => 'TO — Бочка для вина или пива большая (около 1146 л) (тан)'],
        ['value' => 'TR', 'label' => 'TR — Сундук дорожный'],
        ['value' => 'TS', 'label' => 'TS — Связка'],
        ['value' => 'TT', 'label' => 'TT — Мешок тоте'],
        ['value' => 'TU', 'label' => 'TU — Трубка или туба'],
        ['value' => 'TV', 'label' => 'TV — Трубка или туба с насадкой'],
        ['value' => 'TW', 'label' => 'TW — Поддон триволл'],
        ['value' => 'TY', 'label' => 'TY — Цистерна цилиндрическая'],
        ['value' => 'TZ', 'label' => 'TZ — Трубка или туба в пакете/пачке/связке'],
        ['value' => 'UC', 'label' => 'UC — Без клети'],
        ['value' => 'UN', 'label' => 'UN — Единица'],
        ['value' => 'VA', 'label' => 'VA — Бак'],
        ['value' => 'VG', 'label' => 'VG — Наливом газ (при 1031 мБар и 15°C)'],
        ['value' => 'VI', 'label' => 'VI — Флакон'],
        ['value' => 'VK', 'label' => 'VK — Консоль для оборудования, помещающаяся в минифургон'],
        ['value' => 'VL', 'label' => 'VL — Наливом жидкость'],
        ['value' => 'VO', 'label' => 'VO — Насыпью твердые крупные частицы (мелкие куски)'],
        ['value' => 'VP', 'label' => 'VP — В вакуумной упаковке'],
        ['value' => 'VQ', 'label' => 'VQ — Наливом газ сжиженный (при температуре/давлении, отличающихся от нормальных)'],
        ['value' => 'VR', 'label' => 'VR — Насыпью твердые гранулированные частицы (гранулы)'],
        ['value' => 'VS', 'label' => 'VS — Навалом металлолом'],
        ['value' => 'VY', 'label' => 'VY — Насыпью твердые мелкие частицы (порошки)'],
        ['value' => 'WA', 'label' => 'WA — Контейнер средней грузоподъемности для массовых грузов'],
        ['value' => 'WB', 'label' => 'WB — Бутылка оплетенная'],
        ['value' => 'WC', 'label' => 'WC — Контейнер средней грузоподъемности для массовых грузов стальной'],
        ['value' => 'WD', 'label' => 'WD — Контейнер средней грузоподъемности для массовых грузов алюминиевый'],
        ['value' => 'WF', 'label' => 'WF — Контейнер средней грузоподъемности для массовых грузов металлический'],
        ['value' => 'WG', 'label' => 'WG — Контейнер средней грузоподъемности для массовых грузов герметизированный свыше 10 КПа'],
        ['value' => 'WH', 'label' => 'WH — Контейнер средней грузоподъемности для массовых грузов алюминиевый герметизированный свыше 10 КПа'],
        ['value' => 'WJ', 'label' => 'WJ — Контейнер средней грузоподъемности для массовых грузов герметизированный 10 КПа'],
        ['value' => 'WK', 'label' => 'WK — Контейнер средней грузоподъемности для наливных грузов стальной'],
        ['value' => 'WL', 'label' => 'WL — Контейнер средней грузоподъемности для наливных грузов алюминиевый'],
        ['value' => 'WM', 'label' => 'WM — Контейнер средней грузоподъемности для наливных грузов металлический'],
        ['value' => 'WN', 'label' => 'WN — Контейнер средней грузоподъемности для массовых грузов из полимерной ткани без покрытия/вкладыша'],
        ['value' => 'WP', 'label' => 'WP — Контейнер средней грузоподъемности для массовых грузов из полимерной ткани с покрытием'],
        ['value' => 'WQ', 'label' => 'WQ — Контейнер средней грузоподъемности для массовых грузов из полимерной ткани с вкладышем'],
        ['value' => 'WR', 'label' => 'WR — Контейнер средней грузоподъемности для массовых грузов из пластикового волокна с покрытием и вкладышем'],
        ['value' => 'WS', 'label' => 'WS — Контейнер средней грузоподъемности для массовых грузов из полимерной пленки'],
        ['value' => 'WT', 'label' => 'WT — Контейнер средней грузоподъемности для массовых грузов текстильный без покрытия/вкладыша'],
        ['value' => 'WU', 'label' => 'WU — Контейнер средней грузоподъемности для массовых грузов из естественной древесины с внутренним вкладышем'],
        ['value' => 'WV', 'label' => 'WV — Контейнер средней грузоподъемности для массовых грузов текстильный с покрытием'],
        ['value' => 'WW', 'label' => 'WW — Контейнер средней грузоподъемности для массовых грузов текстильный с вкладышем'],
        ['value' => 'WX', 'label' => 'WX — Контейнер средней грузоподъемности для массовых грузов текстильный с покрытием и вкладышем'],
        ['value' => 'WY', 'label' => 'WY — Контейнер средней грузоподъемности для массовых грузов фанерный с внутренним вкладышем'],
        ['value' => 'WZ', 'label' => 'WZ — Контейнер средней грузоподъемности для массовых грузов из древесного материала с внутренним вкладышем'],
        ['value' => 'XA', 'label' => 'XA — Мешок из полимерной ткани без внутреннего покрытия/вкладыша'],
        ['value' => 'XB', 'label' => 'XB — Мешок из полимерной ткани плотный'],
        ['value' => 'XC', 'label' => 'XC — Мешок из полимерной ткани влагонепроницаемый'],
        ['value' => 'XD', 'label' => 'XD — Мешок из полимерной пленки'],
        ['value' => 'XF', 'label' => 'XF — Мешок текстильный без внутреннего покрытия/вкладыша'],
        ['value' => 'XG', 'label' => 'XG — Мешок текстильный плотный'],
        ['value' => 'XH', 'label' => 'XH — Мешок текстильный влагонепроницаемый'],
        ['value' => 'XJ', 'label' => 'XJ — Мешок бумажный многослойный'],
        ['value' => 'XK', 'label' => 'XK — Мешок бумажный многослойный влагонепроницаемый'],
        ['value' => 'YA', 'label' => 'YA — Комбинированная упаковка: пластмассовый сосуд в барабане стальном'],
        ['value' => 'YB', 'label' => 'YB — Комбинированная упаковка: пластмассовый сосуд в ящике решетчатом (или обрешетке) из стали'],
        ['value' => 'YC', 'label' => 'YC — Комбинированная упаковка: пластмассовый сосуд в барабане алюминиевом'],
        ['value' => 'YD', 'label' => 'YD — Комбинированная упаковка: пластмассовый сосуд в ящике решетчатом (или обрешетке) из алюминия'],
        ['value' => 'YF', 'label' => 'YF — Комбинированная упаковка: пластмассовый сосуд в деревянной коробке'],
        ['value' => 'YH', 'label' => 'YH — Комбинированная упаковка: пластмассовый сосуд в коробке фанерной'],
        ['value' => 'YJ', 'label' => 'YJ — Комбинированная упаковка: пластмассовый сосуд в барабане фибровом'],
        ['value' => 'YK', 'label' => 'YK — Комбинированная упаковка: пластмассовый сосуд в коробке из фибрового картона'],
        ['value' => 'YL', 'label' => 'YL — Комбинированная упаковка: пластмассовый сосуд в барабане пластмассовом'],
        ['value' => 'YM', 'label' => 'YM — Комбинированная упаковка: пластмассовый сосуд в коробке из твердой пластмассы'],
        ['value' => 'YN', 'label' => 'YN — Комбинированная упаковка: стеклянный сосуд в стальном барабане'],
        ['value' => 'YP', 'label' => 'YP — Комбинированная упаковка: стеклянный сосуд в ящике решетчатом (или обрешетке) из стали'],
        ['value' => 'YQ', 'label' => 'YQ — Комбинированная упаковка: стеклянный сосуд в барабане алюминиевом'],
        ['value' => 'YR', 'label' => 'YR — Комбинированная упаковка: стеклянный сосуд в ящике решетчатом (или обрешетке) из алюминия'],
        ['value' => 'YS', 'label' => 'YS — Комбинированная упаковка: стеклянный сосуд в коробке деревянной'],
        ['value' => 'YT', 'label' => 'YT — Комбинированная упаковка: стеклянный сосуд в барабане фанерном'],
        ['value' => 'YV', 'label' => 'YV — Комбинированная упаковка: стеклянный сосуд в корзине плетеной с крышкой'],
        ['value' => 'YW', 'label' => 'YW — Комбинированная упаковка: стеклянный сосуд в барабане фибровом'],
        ['value' => 'YX', 'label' => 'YX — Комбинированная упаковка: стеклянный сосуд в коробке из фибрового картона'],
        ['value' => 'YY', 'label' => 'YY — Комбинированная упаковка: стеклянный сосуд в пакете пенопластовом'],
        ['value' => 'YZ', 'label' => 'YZ — Комбинированная упаковка: стеклянный сосуд в пакете из твердой пластмассы'],
        ['value' => 'ZA', 'label' => 'ZA — Контейнер средней грузоподъемности для массовых грузов бумажный многослойный'],
        ['value' => 'ZB', 'label' => 'ZB — Мешок большой'],
        ['value' => 'ZC', 'label' => 'ZC — Контейнер средней грузоподъемности для массовых грузов бумажный многослойный влагонепроницаемый'],
        ['value' => 'ZD', 'label' => 'ZD — Контейнер средней грузоподъемности для твердых навалочных/насыпных грузов из жесткой пластмассы с конструкционным оснащением'],
        ['value' => 'ZF', 'label' => 'ZF — Контейнер средней грузоподъемности для твердых навалочных/насыпных грузов из жесткой пластмассы автономный'],
        ['value' => 'ZG', 'label' => 'ZG — Контейнер средней грузоподъемности для массовых грузов из жесткой пластмассы с конструкционным оснащением герметизированный'],
        ['value' => 'ZH', 'label' => 'ZH — Контейнер средней грузоподъемности для массовых грузов из жесткой пластмассы автономный герметизированный'],
        ['value' => 'ZJ', 'label' => 'ZJ — Контейнер средней грузоподъемности для наливных грузов из жесткой пластмассы с конструкционным оснащением'],
        ['value' => 'ZK', 'label' => 'ZK — Контейнер средней грузоподъемности для наливных грузов из жесткой пластмассы автономный'],
        ['value' => 'ZL', 'label' => 'ZL — Контейнер средней грузоподъемности для твердых навалочных/насыпных грузов составной из жесткой пластмассы'],
        ['value' => 'ZM', 'label' => 'ZM — Контейнер средней грузоподъемности для твердых навалочных/насыпных грузов составной из гибкой пластмассы'],
        ['value' => 'ZN', 'label' => 'ZN — Контейнер средней грузоподъемности для массовых грузов составной из жесткой пластмассы герметизированный'],
        ['value' => 'ZP', 'label' => 'ZP — Контейнер средней грузоподъемности для массовых грузов составной из гибкой пластмассы герметизированный'],
        ['value' => 'ZQ', 'label' => 'ZQ — Контейнер средней грузоподъемности для наливных грузов составной из жесткой пластмассы'],
        ['value' => 'ZR', 'label' => 'ZR — Контейнер средней грузоподъемности для наливных грузов составной из гибкой пластмассы'],
        ['value' => 'ZS', 'label' => 'ZS — Контейнер средней грузоподъемности для массовых грузов составной'],
        ['value' => 'ZT', 'label' => 'ZT — Контейнер средней грузоподъемности для массовых грузов из фибрового картона'],
        ['value' => 'ZU', 'label' => 'ZU — Контейнер средней грузоподъемности для массовых грузов гибкий'],
        ['value' => 'ZV', 'label' => 'ZV — Контейнер средней грузоподъемности для массовых грузов из прочего металла, кроме стали'],
        ['value' => 'ZW', 'label' => 'ZW — Контейнер средней грузоподъемности для массовых грузов из естественной древесины'],
        ['value' => 'ZX', 'label' => 'ZX — Контейнер средней грузоподъемности для массовых грузов фанерный'],
        ['value' => 'ZY', 'label' => 'ZY — Контейнер средней грузоподъемности для массовых грузов из древесного материала'],
        ['value' => 'ZZ', 'label' => 'ZZ — По взаимному определению'],
    ];

    public const MODULE_SLUG = 'saby';
    public const MODULE_NAME = 'Транспортные накладные';

    private const MODULE_FIELDS = [
        'logistic_tasks' => ['saby_waybills', 'company_id', 'contact_id', 'employee_id', 'address', 'products', 'delivery_date'],
        'routes' => ['company_id', 'car_id'],
        'companies' => ['name', 'inn', 'kpp', 'address'],
        'employees' => ['name', 'phone', 'inn'],
        'cars' => ['name', 'number', 'ownership_type', 'osago_mark', 'osago_model', 'weight_max', 'volume_max'],
        'products' => ['name', 'packing_method', 'tare_type', 'weight', 'volume'],
    ];

    private const OBSOLETE_FIELDS = [
        'routes' => ['receiver_company_id', 'request_number', 'request_date', 'saby_waybills'],
    ];

    private const OWNERSHIP_TYPES = [
        ['value' => '1', 'label' => 'Собственность'],
        ['value' => '2', 'label' => 'Совместная собственность супругов'],
        ['value' => '3', 'label' => 'Аренда'],
        ['value' => '4', 'label' => 'Лизинг'],
    ];

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds');
            $this->info('Готово: admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function installInto(ConnectionInterface $db, string $label): void
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->line("    [{$label}] будут созданы таблицы saby_config, saby_waybills и поля модуля");
            return;
        }

        $this->ensureTables($db);

        $this->removeObsoleteFields($db);

        $this->addField($db, 'logistic_tasks', 'company_id', [
            'type' => 'relation',
            'title' => 'Компания',
            'relation_table' => 'companies',
            'details' => json_encode(['table' => 'companies'], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->addField($db, 'logistic_tasks', 'contact_id', [
            'type' => 'relation',
            'title' => 'Контакт',
            'relation_table' => 'contacts',
            'details' => json_encode(['table' => 'contacts'], JSON_UNESCAPED_UNICODE),
            'is_plural' => 1,
        ], 'text');

        $this->addField($db, 'logistic_tasks', 'saby_waybills', [
            'type' => 'waybills',
            'title' => 'Транспортные накладные',
            'only_read' => 1,
            'visible_always' => 1,
        ], 'text');

        $this->addField($db, 'companies', 'inn', [
            'type' => 'text',
            'title' => 'ИНН',
        ], 'varchar(20)');

        $this->addField($db, 'companies', 'kpp', [
            'type' => 'text',
            'title' => 'КПП',
        ], 'varchar(20)');

        $this->addField($db, 'companies', 'address', [
            'type' => 'text',
            'title' => 'Юридический адрес',
        ], 'text');

        $this->addField($db, 'cars', 'number', [
            'type' => 'text',
            'title' => 'Гос. номер',
        ], 'varchar(32)');

        $this->addField($db, 'cars', 'ownership_type', [
            'type' => 'select_dropdown',
            'title' => 'Тип владения ТС',
            'details' => json_encode(['options' => self::OWNERSHIP_TYPES], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->addField($db, 'employees', 'inn', [
            'type' => 'text',
            'title' => 'ИНН',
        ], 'text');

        $this->addField($db, 'products', 'packing_method', [
            'type' => 'text',
            'title' => 'Способ упаковки',
        ], 'text');

        $this->addField($db, 'products', 'tare_type', [
            'type' => 'select_dropdown',
            'title' => 'Вид тары',
            'details' => json_encode(['options' => self::TARE_TYPES], JSON_UNESCAPED_UNICODE),
        ], 'text');

        $this->installModuleTab($db, $label);

        $this->clearCache();

        $this->line("    [{$label}] модуль установлен");
    }

    private function removeObsoleteFields(ConnectionInterface $db): void
    {
        foreach (self::OBSOLETE_FIELDS as $entity => $fields) {
            $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
            if (!$typeId) {
                continue;
            }

            $rows = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->whereIn('field', $fields)
                ->get(['id', 'field']);

            if ($rows->isEmpty()) {
                continue;
            }

            $ids = $rows->pluck('id')->all();
            $db->table('section_fields_sort')->whereIn('field_id', $ids)->delete();
            $db->table('data_rows')->whereIn('id', $ids)->delete();

            $this->line("      удалены устаревшие поля {$entity}: " . $rows->pluck('field')->implode(', '));
        }
    }

    private function installModuleTab(ConnectionInterface $db, string $label): void
    {
        $now = now();

        if (!$db->table('modules')->where('slug', self::MODULE_SLUG)->exists()) {
            $db->table('modules')->insert([
                'name' => self::MODULE_NAME,
                'config' => '',
                'entities' => '',
                'slug' => self::MODULE_SLUG,
                'enabled' => 1,
            ]);
            $this->line("      добавлена запись модуля " . self::MODULE_SLUG . " в modules");
        }

        foreach (self::MODULE_FIELDS as $entity => $fields) {
            $typeId = $db->table('data_types')->where('slug', $entity)->value('id');
            if (!$typeId) {
                continue;
            }

            $sectionId = $db->table('field_sections')
                ->where('page', $entity)
                ->where('module', self::MODULE_SLUG)
                ->value('id');

            if (!$sectionId) {
                $sectionId = $db->table('field_sections')->insertGetId([
                    'sort' => 0, 'name' => 'Используемые поля в модуле', 'domain_key' => null, 'page' => $entity,
                    'created_at' => $now, 'updated_at' => $now, 'account_id' => null, 'hide' => 0,
                    'column_id' => 1, 'module' => self::MODULE_SLUG, 'parent_id' => null, '_lft' => 0, '_rgt' => 0, 'is_short' => null,
                ]);
            }

            $names = $fields;
            if ($entity === 'companies') {
                $names = array_merge($names, $this->phoneFields($db, $typeId));
            }

            $rows = $db->table('data_rows')
                ->where('data_type_id', $typeId)
                ->where('is_remove', 0)
                ->whereIn('field', $names)
                ->get(['id', 'field', 'module', 'module_section_id']);

            $ordered = [];
            foreach ($names as $name) {
                $row = $rows->firstWhere('field', $name);
                if (!$row) {
                    continue;
                }
                $ordered[] = $row->id;
                $this->attachField($db, $row, (int) $sectionId);
            }

            $this->syncSectionSort($db, (int) $sectionId, $ordered);
            $this->syncMenu($db, $entity);

            $db->table('local_cache')->where('url', "fields/{$entity}")->update(['updated_at' => $now]);

            $this->line("      [{$entity}] секция модуля #{$sectionId}, полей: " . count($ordered));
        }
    }

    private function phoneFields(ConnectionInterface $db, int $typeId): array
    {
        return $db->table('data_rows')
            ->where('data_type_id', $typeId)
            ->where('is_remove', 0)
            ->where('title', 'LIKE', '%елефон%')
            ->pluck('field')
            ->all();
    }

    private function attachField(ConnectionInterface $db, $row, int $sectionId): void
    {
        $modules = $this->decodeJsonList($row->module);
        $sections = array_map('intval', $this->decodeJsonList($row->module_section_id));

        $changed = false;
        if (!in_array(self::MODULE_SLUG, $modules, true)) {
            $modules[] = self::MODULE_SLUG;
            $changed = true;
        }
        if (!in_array($sectionId, $sections, true)) {
            $sections[] = $sectionId;
            $changed = true;
        }

        if ($changed) {
            $db->table('data_rows')->where('id', $row->id)->update([
                'module' => json_encode(array_values($modules)),
                'module_section_id' => json_encode(array_values($sections)),
            ]);
        }
    }

    private function syncSectionSort(ConnectionInterface $db, int $sectionId, array $ordered): void
    {
        $db->table('section_fields_sort')->where('section_id', $sectionId)->delete();

        if (!$ordered) {
            return;
        }

        $insert = [];
        foreach ($ordered as $i => $fieldId) {
            $insert[] = ['section_id' => $sectionId, 'field_id' => $fieldId, 'sort' => $i];
        }
        $db->table('section_fields_sort')->insert($insert);
    }

    private function syncMenu(ConnectionInterface $db, string $entity): void
    {
        $child = ['title' => self::MODULE_NAME, 'sort' => 2, 'enabled' => 1, 'id' => 0, 'alias' => self::MODULE_SLUG];

        $menus = $db->table('settings')->where(['type' => 'menu', 'entity' => $entity])->get();

        if ($menus->isEmpty()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    [
                        'title' => 'Модули', 'tab' => 'modules', 'sort' => 1, 'enabled' => 1, 'id' => 1,
                        'childs' => [$child],
                        'component' => ['name' => 'AsyncComponentWrapper'],
                        'roles_read' => [], 'has_roles_read' => false,
                    ],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 3, 'enabled' => true, 'id' => 3, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => $entity, 'user_id' => null,
            ]);
            return;
        }

        foreach ($menus as $menu) {
            $tabs = json_decode($menu->value, true);
            if (!is_array($tabs)) {
                continue;
            }

            $modulesKey = null;
            foreach ($tabs as $k => $tab) {
                if (($tab['tab'] ?? null) === 'modules') {
                    $modulesKey = $k;
                    break;
                }
            }

            if ($modulesKey === null) {
                $maxSort = 0;
                $maxId = 0;
                foreach ($tabs as $tab) {
                    $maxSort = max($maxSort, (int) ($tab['sort'] ?? 0));
                    $maxId = max($maxId, (int) ($tab['id'] ?? 0));
                }
                $tabs[] = [
                    'title' => 'Модули', 'tab' => 'modules', 'sort' => $maxSort + 1, 'enabled' => 1, 'id' => $maxId + 1,
                    'childs' => [$child],
                    'component' => ['name' => 'AsyncComponentWrapper'],
                    'roles_read' => [], 'has_roles_read' => false,
                ];
            } else {
                $tabs[$modulesKey]['enabled'] = 1;
                $childs = $tabs[$modulesKey]['childs'] ?? [];
                $exists = false;
                foreach ($childs as $ck => $item) {
                    if (($item['alias'] ?? null) === self::MODULE_SLUG) {
                        $childs[$ck]['enabled'] = 1;
                        $childs[$ck]['title'] = self::MODULE_NAME;
                        $exists = true;
                    }
                }
                if (!$exists) {
                    $childs[] = $child;
                }
                $tabs[$modulesKey]['childs'] = array_values($childs);
            }

            $db->table('settings')->where('id', $menu->id)->update([
                'value' => json_encode($tabs, JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    private function decodeJsonList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, fn ($v) => $v !== null && $v !== ''));
        }

        return [(string) $value];
    }

    private function ensureTables(ConnectionInterface $db): void
    {
        $sb = $db->getSchemaBuilder();

        if (!$sb->hasTable('saby_config')) {
            $db->statement("
                CREATE TABLE `saby_config` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `login` varchar(255) DEFAULT NULL,
                    `password` varchar(255) DEFAULT NULL,
                    `config` text,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (!$sb->hasTable('saby_waybills')) {
            $db->statement("
                CREATE TABLE `saby_waybills` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `route_id` bigint unsigned DEFAULT NULL,
                    `doc_id` varchar(64) DEFAULT NULL,
                    `attachment_id` varchar(64) DEFAULT NULL,
                    `number` varchar(64) DEFAULT NULL,
                    `date` varchar(32) DEFAULT NULL,
                    `status` varchar(255) DEFAULT NULL,
                    `pdf_url` text,
                    `cabinet_url` text,
                    `archive_url` text,
                    `qr_url` text,
                    `payload` longtext,
                    `error` text,
                    `user_id` bigint unsigned DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `saby_waybills_route_id_index` (`route_id`),
                    KEY `saby_waybills_doc_id_index` (`doc_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (!$sb->hasColumn('saby_waybills', 'qr_url')) {
            $db->statement("ALTER TABLE `saby_waybills` ADD COLUMN `qr_url` TEXT NULL");
        }

        if (!$sb->hasTable('logistic_task_contact')) {
            $db->statement("
                CREATE TABLE `logistic_task_contact` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `logistic_task_id` bigint unsigned NOT NULL,
                    `contact_id` bigint unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `logistic_task_contact_pair_unique` (`logistic_task_id`, `contact_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        if (!$sb->hasColumn('saby_waybills', 'task_id')) {
            $db->statement("ALTER TABLE `saby_waybills` ADD COLUMN `task_id` BIGINT UNSIGNED NULL");
            $db->statement("ALTER TABLE `saby_waybills` ADD INDEX `saby_waybills_task_id_index` (`task_id`)");
        }
    }

    private function addField(ConnectionInterface $db, string $entity, string $field, array $attrs, string $columnType): void
    {
        $sb = $db->getSchemaBuilder();

        $dataType = $db->table('data_types')->where('slug', $entity)->first();
        if (!$dataType || !$sb->hasTable($entity)) {
            $this->warn("      сущность {$entity} не найдена, поле {$field} пропущено");
            return;
        }

        if (!$sb->hasColumn($entity, $field)) {
            $db->statement("ALTER TABLE `{$entity}` ADD COLUMN `{$field}` {$columnType} NULL");
        }

        $existing = $db->table('data_rows')
            ->where('data_type_id', $dataType->id)
            ->where('field', $field)
            ->first();

        if ($existing) {
            $patch = array_intersect_key($attrs, array_flip(['type', 'title', 'relation_table', 'details', 'only_read', 'visible_always']));
            if (count($patch)) {
                $db->table('data_rows')->where('id', $existing->id)->update($patch);
            }
            $this->line("      {$entity}.{$field} обновлено (id {$existing->id})");
            return;
        }

        $sectionId = $db->table('field_sections')
            ->where('page', $entity)
            ->whereNull('module')
            ->orderBy('sort')
            ->value('id');

        $maxSort = (int) $db->table('data_rows')->where('data_type_id', $dataType->id)->max('sort');

        $row = $this->baseRow($dataType->id, $sectionId);
        $row['field'] = $field;
        $row['sort'] = $maxSort + 1;
        $row = array_merge($row, $attrs);

        $id = $db->table('data_rows')->insertGetId($row);

        $this->line("      создано поле {$entity}.{$field} (id {$id})");
    }

    private function baseRow(int $typeId, $sectionId): array
    {
        return [
            'data_type_id' => $typeId, 'field' => null, 'type' => 'text', 'title' => '',
            'required' => 0, 'details' => null, 'visible_always' => 1, 'label_color' => '',
            'section_id' => $sectionId, 'group_id' => null, 'sort' => 0,
            'created_at' => null, 'updated_at' => null, 'button_name' => 'Загрузить',
            'show_file_image' => 0, 'hide' => 0, 'is_plural' => 0, 'roles_read' => '',
            'roles_write' => '', 'is_remove' => 0, 'mobile_pages' => '', 'display_parent_name' => null,
            'rules' => null, 'only_read' => 0, 'is_permanent' => 1, 'show_file_name' => 0,
            'external_link' => '', 'is_external_link' => 0, 'module' => '', 'is_link' => 0,
            'unit' => '', 'module_section_id' => null, 'is_default' => 0, 'is_inactive' => 0,
            'blocked_changes' => 0, 'mask' => null, 'permanent_required' => 0, 'permanent_name' => 0,
            'relation_table' => null, 'options' => null, 'set_color' => 0, 'related_field' => null,
            'is_unique' => 0, 'is_program' => 0, 'subfields' => null, 'dependency_fields' => null,
        ];
    }

    private function clearCache(): void
    {
        try {
            \App\Models\Settings::clear_cache();
        } catch (\Throwable $e) {
        }
    }
}
