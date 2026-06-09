<?php

namespace Modules\Bitrix24\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Bitrix24\Entities\Config;
use \App\Models\Order;

class Bitrix24Controller extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $config = Config::first();
        $b24_fields = null;
        $order_fields = Order::getFields();

        if($config) {
            if($config->webhook) {
                $response = Http::post($config->webhook.'crm.deal.fields', [])->collect();
                if(isset($response['result'])) {
                    $b24_fields = $response['result'];
                }
            }
        } else {
            $config = new Config;
            $config->save();
        }

        $params = $config->getParams();
        //dd($b24_fields['ASSIGNED_BY_ID']);
        return view('bitrix24::index', compact('config', 'params', 'order_fields', 'b24_fields'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('bitrix24::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('bitrix24::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('bitrix24::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function update(Request $request)
    {
        // firstOrNew: в новом тенанте строки конфига может ещё не быть —
        // без этого сохранение вебхука падало с "null".
        $config = Config::first() ?: new Config();
        $config->webhook = $request->webhook;
        $config->save();

        //dd($request->config);
        $config->setParams($request->config);

        return redirect('/bitrix24');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function getConfig()
    {
        $config = Config::first() ?: new Config();
        return response()->json([
            'webhook'    => $config->webhook ?? '',
            'configured' => !empty($config->webhook),
        ]);
    }

    public function setConfig(Request $request)
    {
        $config = Config::first() ?: new Config();
        $config->webhook = $request->webhook ?? '';
        $config->save();
        return response()->json([
            'webhook'    => $config->webhook,
            'configured' => !empty($config->webhook),
        ]);
    }

    public function sync(Request $request)
    {
        $config = Config::first();
        $order_fields = Order::getFields();
        $params = $config->getParams();

        //$time = strtotime('03/10/2023');
        //$date = date('Y-m-d',$time);
        $date = date('Y-m-d');


        $response = Http::post($config->webhook.'crm.deal.fields', [])->collect();
        if(isset($response['result'])) {
            $b24_fields = $response['result'];
        };

        $response = Http::post($config->webhook.'crm.deal.list', [
            'filter' => [
                '>=DATE_CREATE' => $date
            ],
            'select' => ['*', 'UF_*']
            
        ])->collect();

        if(isset($response['result'])) {
            $deals = $response['result'];

            foreach ($deals as $deal) {
                $data = Order::where('number', $deal['ID'])->first();
                if(!$data)
                    $data = new Order;
                $response_products = Http::post($config->webhook.'crm.deal.productrows.get', [
                    'id' => $deal['ID'],
                ])->collect();
                $delivery_price = 0;
                $all_weight = 0;
                $products = array();
                if(isset($response_products['result'])) {
                    $arItems = array();
                    foreach($response_products['result'] as $product) {
                        if ($product['PRODUCT_ID'] != 113 && $product['PRODUCT_ID'] != 111 && $product['PRODUCT_ID']) {
                            $weight = 0;
                            $prod = \Modules\Products\Entities\Product::where('id_b24', $product['PRODUCT_ID'])->first();
                            if(!$prod) {
                                $prod = new \Modules\Products\Entities\Product();
                                $prod->id_b24 =  $product['PRODUCT_ID'];
                                $prod->name = $product['PRODUCT_NAME'];
                                if (isset($product['PROPERTY_134']))
                                    $prod->weight = $product['PROPERTY_134']['value'];
                                if (isset($product['PROPERTY_132']))
                                    $prod->link = $product['PROPERTY_132']['value'];
                                if(isset($product['PROPERTY_139']))
                                    $prod->barcode = $product['PROPERTY_139']['value'];
                                if(isset($product['PROPERTY_131']))
                                    $prod->website_id = $product['PROPERTY_131']['value'];

                                $prod->save();
                            }
                            $all_weight = $all_weight + $prod->weight*$product['QUANTITY'];
                            
                            if(!array_key_exists($prod['name'], $arItems))
                                $arItems[$prod['name']] = $product['QUANTITY'];
                            else
                                $arItems[$prod['name'].'.'] = $product['QUANTITY'];
                            $products[] = array(
                                'name' => $prod['name'],
                                'price' => $product['PRICE'],
                                'count' => $product['QUANTITY'],
                                'weight' => $prod->weight,
                                'sum' => $product['PRICE']*$product['QUANTITY']
                            );
                        } else {
                            $delivery_price = $delivery_price + (float)$product['PRICE']*$product['QUANTITY'];
                        }
                        
                    }
                }
                $data->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                //print_r($data->products);
                foreach($params['params'] as $b24_code => $code) {
                    $values = null;
                    if(isset($params['values'][$code])) {
                        $values = $params['values'][$code];
                    }
                    if($values && isset($values[$deal[$b24_code]])) {
                        $data->{$code} = $values[$deal[$b24_code]];
                        echo '<b>'.$code.':</b>'.$values[$deal[$b24_code]].'<br>';
                    } else {
                        $data->{$code} = $deal[$b24_code];
                        echo '<b>'.$code.':</b>'.$deal[$b24_code].'<br>';
                    }
                }
                $data->save();
                echo '<hr>';
            }
        }

        return false;

    }

    /**
     * Вебхук Bitrix24: создаёт/обновляет задачу логистики (logistic_tasks)
     * из сделки CRM. Адрес для подключения в Битрикс24 (исходящий вебхук/робот):
     *   https://logistopt6.compas.pro/bitrix24/deal-hook?id=#{deal.ID}
     * Тенант определяется по домену (InitializeTenancyByDomain в web.php).
     * Данные сделки тянем через входящий вебхук, сохранённый в настройках модуля
     * (Config::first()->webhook).
     *
     * Портировано со старого обработчика opt6/crm6.ru. Соответствия полей —
     * см. ниже по коду; номер сделки пишется в name (по согласованию), дедуп
     * задачи — по name.
     */
    // Стадия сделки, на которой создаём задачу логистики (старый код: STAGE_ID == 17).
    const TARGET_STAGE_ID = 17;
    // Статус точки для новой задачи (point_status). В тенанте logistopt6 статусы
    // имеют свои id (1/4/5...) — при необходимости поменяйте здесь или в настройках.
    const NEW_POINT_STATUS = 1;
    // Товары-исключения (доставка и т.п.) — их цена идёт в delivery_price.
    const SKIP_PRODUCT_IDS = [111, 113];

    public function dealHook(Request $request)
    {
        $config = Config::first();
        if (!$config || !$config->webhook) {
            return response('Bitrix24 webhook is not configured', 200);
        }
        $base = $config->webhook;

        // Bitrix24 шлёт либо ?id=, либо POST data[FIELDS][ID] (исходящий вебхук по событию)
        $dealId = $request->input('id');
        if (!$dealId) {
            $dealId = data_get($request->input('data'), 'FIELDS.ID');
        }
        if (!$dealId) {
            Log::channel('daily')->info('deal-hook: no deal id', ['input' => $request->all()]);
            return response('no deal id', 200);
        }

        $resp = Http::post($base . 'crm.deal.get', ['id' => $dealId])->collect();
        $deal = $resp['result'] ?? null;
        if (!$deal) {
            Log::channel('daily')->info('deal-hook: deal not found', ['deal_id' => $dealId, 'resp' => $resp->toArray()]);
            return response('deal not found', 200);
        }

        $params = is_object($config) ? ($config->getParams() ?: []) : [];
        $targetStage = $params['stage_id'] ?? self::TARGET_STAGE_ID;

        Log::channel('daily')->info('deal-hook: received', [
            'deal_id'      => $dealId,
            'stage_id'     => $deal['STAGE_ID'],
            'target_stage' => $targetStage,
            'method'       => $request->method(),
        ]);

        // Дедуп по номеру сделки в названии. Граница ([^0-9]|$) — чтобы №112 не
        // совпадал с №1120.
        $existing = \App\Models\Task::where('name', 'REGEXP', '^Сделка №' . $dealId . '([^0-9]|$)')
            ->whereNull('deleted_at')
            ->first();

        // Создаём задачу только когда сделка дошла до нужной стадии (как в старом коде).
        if ($deal['STAGE_ID'] != $targetStage) {
            Log::channel('daily')->info('deal-hook: stage skipped', ['deal_id' => $dealId, 'stage' => $deal['STAGE_ID'], 'target' => $targetStage]);
            return response('stage skipped: ' . $deal['STAGE_ID'], 200);
        }
        // Если задача уже привязана к маршруту — не перезаписываем (старый код: die()).
        if ($existing && $existing->route_id) {
            return response('task already on a route', 200);
        }

        // --- Товары сделки ---
        $delivery_price = 0;
        $all_weight = 0;
        $products = [];
        $arItems = [];
        $prodResp = Http::post($base . 'crm.deal.productrows.get', ['id' => $deal['ID']])->collect();
        if (isset($prodResp['result']) && is_array($prodResp['result'])) {
            foreach ($prodResp['result'] as $product) {
                $pid = $product['PRODUCT_ID'] ?? null;
                if ($pid && !in_array($pid, self::SKIP_PRODUCT_IDS)) {
                    $prod = \Modules\Products\Entities\Product::where('id_b24', $pid)->first();
                    if (!$prod) {
                        // crm.product.list даёт свойства PROPERTY_* (вес и пр.)
                        $plist = Http::post($base . 'crm.product.list', [
                            'order'  => ['ID' => 'ASC'],
                            'filter' => ['ID' => $pid],
                            'select' => ['*', 'PROPERTY_*'],
                        ])->collect();
                        $prod = new \Modules\Products\Entities\Product();
                        $prod->id_b24 = $pid;
                        $prod->name = $product['PRODUCT_NAME'] ?? ('Товар #' . $pid);
                        if (isset($plist['result'][0]['PROPERTY_134']['value'])) {
                            // в тенанте у products есть только name/id_b24/weight
                            $prod->weight = $plist['result'][0]['PROPERTY_134']['value'];
                        }
                        $prod->save();
                    }

                    $qty = $product['QUANTITY'] ?? 0;
                    $all_weight += ((float) $prod->weight) * $qty;

                    $itemKey = $prod->name;
                    if (array_key_exists($itemKey, $arItems)) {
                        $itemKey .= '.';
                    }
                    $arItems[$itemKey] = $qty;

                    $products[] = [
                        'name'   => $prod->name,
                        'price'  => $product['PRICE'] ?? 0,
                        'count'  => $qty,
                        'weight' => $prod->weight,
                        'sum'    => ($product['PRICE'] ?? 0) * $qty,
                    ];
                } else {
                    $delivery_price += (float) ($product['PRICE'] ?? 0) * ($product['QUANTITY'] ?? 0);
                }
            }
        }

        // --- Контакт (телефон/имя) ---
        $contact = null;
        if (!empty($deal['CONTACT_ID'])) {
            $cResp = Http::post($base . 'crm.contact.get', ['id' => $deal['CONTACT_ID']])->collect();
            $contact = $cResp['result'] ?? null;
        }

        // --- Счета (оплата) ---
        $invoices = [];
        $invResp = Http::post($base . 'crm.invoice.list', [
            'filter' => ['UF_DEAL_ID' => $deal['ID']],
        ])->collect();
        if (isset($invResp['result']) && is_array($invResp['result'])) {
            foreach ($invResp['result'] as $invoice) {
                if (($invoice['UF_DEAL_ID'] ?? null) == $deal['ID']) {
                    $invoices[] = $invoice['ACCOUNT_NUMBER'] ?? $invoice['ID'];
                }
            }
        }

        // --- Сборка задачи ---
        $task = $existing ?: new \App\Models\Task();
        $isNew = !$existing;

        $clientName = $deal['UF_CRM_1642670804'] ?? ($contact['NAME'] ?? null);
        $task->name = 'Сделка №' . $dealId;
        $task->contact = $clientName;

        // Адрес + координаты (lat,lng в UF_CRM_1741758491) -> JSON {text, coords}
        $addrText = $deal['UF_CRM_1528885851543'] ?? '';
        $coords = [];
        if (!empty($deal['UF_CRM_1741758491'])) {
            $parts = explode(',', $deal['UF_CRM_1741758491']);
            if (count($parts) >= 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                $coords = [trim($parts[0]), trim($parts[1])];
            }
        }
        $task->address = json_encode(['text' => $addrText, 'coords' => $coords], JSON_UNESCAPED_UNICODE);

        if ($products) {
            $task->products = json_encode($products, JSON_UNESCAPED_UNICODE);
        }
        $task->weight = $all_weight;
        $task->time = $deal['UF_CRM_1632832553'] ?? null;

        // Дата доставки — из UF_CRM_1738582841. Битрикс отдаёт DateTime в UTC
        // (например «2026-06-08T21:00:00+00:00» = 09.06.2026 00:00 по МСК).
        // Конвертируем в таймзону приложения перед сохранением.
        if (!empty($deal['UF_CRM_1738582841'])) {
            try {
                $dt = new \DateTime($deal['UF_CRM_1738582841']);
                $dt->setTimezone(new \DateTimeZone(config('app.timezone', 'Europe/Moscow')));
                $task->delivery_date = $dt->format('Y-m-d');
            } catch (\Exception $e) {
                if ($isNew && empty($task->delivery_date)) {
                    $task->delivery_date = date('Y-m-d');
                }
            }
        } elseif ($isNew && empty($task->delivery_date)) {
            $task->delivery_date = date('Y-m-d');
        }

        // Телефон: спец-поле сделки, иначе из контакта
        if (!empty($deal['UF_CRM_1623418181538'])) {
            $task->phone = $deal['UF_CRM_1623418181538'];
        } elseif ($contact && ($contact['HAS_PHONE'] ?? null) == 'Y') {
            $task->phone = $contact['PHONE'][0]['VALUE'] ?? null;
        }

        // delivery_price: из товаров-доставки, иначе спец-поле, иначе 0
        if ($delivery_price > 0) {
            $task->delivery_price = $delivery_price;
        } elseif (!empty($deal['UF_CRM_1633508830'])) {
            $task->delivery_price = $deal['UF_CRM_1633508830'];
        } else {
            $task->delivery_price = 0;
        }

        // --- Доп. поля (новые колонки) ---
        $task->comment = $deal['UF_CRM_5EAFC3D4C5F76'] ?? null;
        $task->pallets_count = $deal['UF_CRM_1696596978695'] ?? null;
        $task->crm_link = 'https://crm6.ru/crm/deal/details/' . $dealId . '/';

        // Разгрузка (UF_CRM_1762411084 -> массив названий)
        if (!empty($deal['UF_CRM_1762411084']) && is_array($deal['UF_CRM_1762411084'])) {
            $unloadingMap = [
                2867 => 'Гидролифт',
                2868 => 'Манипулятор',
                2869 => 'Ручная',
                2870 => 'Открытая',
                2871 => 'Водитель РФ',
            ];
            $unloading = [];
            foreach ($deal['UF_CRM_1762411084'] as $val) {
                if (isset($unloadingMap[$val])) {
                    $unloading[] = $unloadingMap[$val];
                }
            }
            $task->unloading = $unloading ? json_encode($unloading, JSON_UNESCAPED_UNICODE) : null;
        } else {
            $task->unloading = null;
        }

        // Тип машины/доставки (UF_CRM_1625083610453 -> код)
        if (!empty($deal['UF_CRM_1625083610453'])) {
            $typeMap = ['2712' => 3, '2713' => 4, '2714' => 5, '2715' => 6, '2716' => 7];
            $task->type = $typeMap[$deal['UF_CRM_1625083610453']] ?? 0;
        }

        // Оплата: счёт (если есть номера) или наличные
        if (count($invoices) > 0) {
            $task->payment_type = 1;
            $task->payment = 'Счет: ' . implode(',', $invoices);
        } else {
            $task->payment_type = 2;
            $task->payment = 'Нал: ' . (int) ($deal['OPPORTUNITY'] ?? 0) . ' р.';
        }

        // Менеджер -> локальный пользователь по email (crm_id в тенанте нет)
        if (!empty($deal['ASSIGNED_BY_ID'])) {
            $uResp = Http::post($base . 'user.get', ['id' => $deal['ASSIGNED_BY_ID']])->collect();
            $manager = $uResp['result'][0] ?? null;
            if ($manager && !empty($manager['EMAIL'])) {
                $user = \App\Models\User::where('email', $manager['EMAIL'])->first();
                if ($user) {
                    $task->user_id = $user->id;
                }
            }
        }

        // Статус точки для новой задачи
        if ($isNew) {
            $task->point_status = $params['point_status'] ?? self::NEW_POINT_STATUS;
        }

        $task->save();

        return response()->json([
            'status'     => 'ok',
            'deal_id'    => $dealId,
            'task_id'    => $task->id,
            'created'    => $isNew,
        ], 200);
    }


}
