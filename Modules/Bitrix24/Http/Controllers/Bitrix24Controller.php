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
        // Лог на самом входе: любая попытка вызова видна в storage/logs/bitrix24.log,
        // даже если конфиг не заполнен или id сделки не передан.
        Log::channel('bitrix24')->info('deal-hook: hit', [
            'method' => $request->method(),
            'host'   => $request->getHost(),
            'input'  => $request->all(),
        ]);

        $config = Config::first();
        if (!$config || !$config->webhook) {
            Log::channel('bitrix24')->warning('deal-hook: webhook is not configured (bitrix24_config пуст)');
            return response('Bitrix24 webhook is not configured', 200);
        }
        $base = $config->webhook;

        // Bitrix24 шлёт либо ?id=, либо POST data[FIELDS][ID] (исходящий вебхук по событию)
        $dealId = $request->input('id');
        if (!$dealId) {
            $dealId = data_get($request->input('data'), 'FIELDS.ID');
        }
        if (!$dealId) {
            Log::channel('bitrix24')->info('deal-hook: no deal id', ['input' => $request->all()]);
            return response('no deal id', 200);
        }

        $resp = Http::post($base . 'crm.deal.get', ['id' => $dealId])->collect();
        $deal = $resp['result'] ?? null;
        if (!$deal) {
            Log::channel('bitrix24')->info('deal-hook: deal not found', ['deal_id' => $dealId, 'resp' => $resp->toArray()]);
            return response('deal not found', 200);
        }

        $params = is_object($config) ? ($config->getParams() ?: []) : [];
        $targetStage = $params['stage_id'] ?? self::TARGET_STAGE_ID;

        Log::channel('bitrix24')->info('deal-hook: received', [
            'deal_id'      => $dealId,
            'stage_id'     => $deal['STAGE_ID'],
            'target_stage' => $targetStage,
            'method'       => $request->method(),
        ]);

        $crmLink = 'https://crm6.ru/crm/deal/details/' . $dealId . '/';
        $existing = \App\Models\Task::where(function ($q) use ($crmLink, $dealId) {
                $q->where('crm_link', $crmLink)
                  ->orWhere('name->external_link', $crmLink)
                  ->orWhere('name', 'REGEXP', 'Сделка №' . $dealId . '([^0-9]|$)');
            })
            ->whereNull('deleted_at')
            ->first();

        // Создаём задачу только когда сделка дошла до нужной стадии (как в старом коде).
        if ($deal['STAGE_ID'] != $targetStage) {
            Log::channel('bitrix24')->info('deal-hook: stage skipped', ['deal_id' => $dealId, 'stage' => $deal['STAGE_ID'], 'target' => $targetStage]);
            return response('stage skipped: ' . $deal['STAGE_ID'], 200);
        }
        if ($existing && $existing->route_id) {
            $activeRoute = \App\Models\Route::find($existing->route_id);
            if ($activeRoute) {
                Log::channel('bitrix24')->info('deal-hook: task already on a route', ['deal_id' => $dealId, 'task_id' => $existing->id, 'route_id' => $existing->route_id]);
                return response('task already on a route', 200);
            }
            Log::channel('bitrix24')->info('deal-hook: route deleted, unassigning', ['deal_id' => $dealId, 'task_id' => $existing->id, 'route_id' => $existing->route_id]);
            $existing->route_id = null;
        }

        Log::channel('bitrix24')->info('deal-hook: matched task', [
            'deal_id'          => $dealId,
            'task_id'          => $existing->id ?? null,
            'delivery_field'   => $deal['UF_CRM_1738582841'] ?? null,
            'task_delivery_old' => $existing->delivery_date ?? null,
        ]);

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
        try {
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
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('deal-hook: invoice fetch failed', ['deal_id' => $dealId, 'error' => $e->getMessage()]);
        }

        // --- Сборка задачи ---
        $task = $existing ?: new \App\Models\Task();
        $isNew = !$existing;

        $clientName = $deal['UF_CRM_1642670804'] ?? ($contact['NAME'] ?? null);
        // Поле «Название» в задачах логистики — текстовое поле-внешняя-ссылка
        // (хранится JSON-ом {value, external_link}). Текст — «Сделка №ID»,
        // ссылка ведёт на карточку сделки в CRM.
        $task->name = json_encode([
            'value'         => (string) $dealId,
            'external_link' => 'https://crm6.ru/crm/deal/details/' . $dealId . '/',
        ], JSON_UNESCAPED_UNICODE);
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
        $task->crm_link = $crmLink;

        $b24FieldsResp = Http::post($base . 'crm.deal.fields', [])->collect();
        $b24Fields = $b24FieldsResp['result'] ?? [];

        $rawUnloading = $deal['UF_CRM_1762411084'] ?? null;
        $carReqs = [];
        foreach ((is_array($rawUnloading) ? $rawUnloading : [$rawUnloading]) as $val) {
            if ($val === '' || $val === null) {
                continue;
            }
            $label = $this->b24EnumLabel($b24Fields, 'UF_CRM_1762411084', $val);
            $local = $this->localOptionValueByLabel('logistic_tasks', 'car_requirements', $label);
            if ($local !== null) {
                $carReqs[] = (int) $local;
            }
        }
        $task->car_requirements = $carReqs ? json_encode($carReqs) : null;

        $carTypeColumn = \Illuminate\Support\Facades\Schema::hasColumn('logistic_tasks', 'car_type');
        $rawCarType = $deal['UF_CRM_1625083610453'] ?? null;
        $ctLabel = ($rawCarType === '' || $rawCarType === null) ? null : $this->b24EnumLabel($b24Fields, 'UF_CRM_1625083610453', $rawCarType);
        $localCt = $ctLabel === null ? null : $this->localOptionValueByLabel('logistic_tasks', 'car_type', $ctLabel);
        if ($carTypeColumn) {
            $task->car_type = $localCt !== null ? (int) $localCt : null;
        }
        Log::channel('bitrix24')->info('deal-hook: car_type', [
            'column_exists' => $carTypeColumn,
            'raw'           => $rawCarType,
            'b24_label'     => $ctLabel,
            'local_value'   => $localCt,
        ]);

        // Оплата: счёт (если есть номера) или наличные
        if (count($invoices) > 0) {
            $task->payment_type = 1;
            $task->payment = 'Счет: ' . implode(',', $invoices);
        } else {
            $task->payment_type = 2;
            $task->payment = 'Нал: ' . (int) ($deal['OPPORTUNITY'] ?? 0) . ' р.';
        }

        // Ответственный: сопоставляем ответственного сделки (ASSIGNED_BY_ID) с
        // пользователем сервиса по полю «Сотрудник Bitrix24» (b24_responsible).
        // Опции этого поля актуализируем из сотрудников Bitrix24.
        if (!empty($deal['ASSIGNED_BY_ID'])) {
            try {
                $usersTypeId = \DB::table('data_types')->where('slug', 'users')->value('id');
                $this->actualizeBitrix24Employees($base, $usersTypeId);

                $matched = null;
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'b24_responsible')) {
                    $matched = \App\Models\User::where('b24_responsible', (string) $deal['ASSIGNED_BY_ID'])->first();
                }

                if ($matched) {
                    $task->user_id = $matched->id;
                } else {
                    // Фолбэк: по email менеджера (как раньше).
                    $uResp = Http::post($base . 'user.get', ['id' => $deal['ASSIGNED_BY_ID']])->collect();
                    $manager = $uResp['result'][0] ?? null;
                    if ($manager && !empty($manager['EMAIL'])) {
                        $user = \App\Models\User::where('email', $manager['EMAIL'])->first();
                        if ($user) {
                            $task->user_id = $user->id;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('deal-hook: manager match failed', ['deal_id' => $dealId, 'error' => $e->getMessage()]);
            }
        }

        // Статус точки для новой задачи
        if ($isNew) {
            $task->point_status = $params['point_status'] ?? self::NEW_POINT_STATUS;

            $defaultRows = \DB::table('data_rows')
                ->whereIn('data_type_id', function ($q) {
                    $q->select('id')->from('data_types')->where('slug', 'logistic_tasks');
                })
                ->where('set_default', 1)
                ->whereNotNull('default_value')
                ->where('default_value', '!=', '')
                ->whereNotIn('type', ['relation', 'file', 'text_group'])
                ->get(['field', 'default_value']);
            foreach ($defaultRows as $dr) {
                if ($task->{$dr->field} === null || $task->{$dr->field} === '') {
                    $task->{$dr->field} = $dr->default_value;
                }
            }
        }

        // История изменений — как при обычной работе с объектом через UI
        // (CrudService::batch). Вебхук не авторизован, поэтому записи уйдут
        // от пользователя id=1 (системный) — так же ведут себя остальные
        // не-авторизованные пути записи истории.
        if ($isNew) {
            $task->save();
            try {
                \App\Models\History::createObject('logistic_tasks', $task);
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->warning('deal-hook: history create failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
            }
        } else {
            $dirty = $task->getDirty();
            Log::channel('bitrix24')->info('deal-hook: before save', ['task_id' => $task->id, 'dirty' => array_keys($dirty), 'delivery_new' => $task->delivery_date]);
            if (count($dirty)) {
                try {
                    // saveForObject сравнивает row с текущими значениями в БД,
                    // поэтому вызывается ДО save().
                    \App\Models\History::saveForObject('logistic_tasks', [array_merge(['id' => $task->id], $dirty)]);
                } catch (\Throwable $e) {
                    Log::channel('bitrix24')->warning('deal-hook: history update failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
                }
            }
            try {
                $task->save();
                Log::channel('bitrix24')->info('deal-hook: saved', ['task_id' => $task->id, 'delivery_saved' => $task->fresh()->delivery_date]);
            } catch (\Throwable $e) {
                Log::channel('bitrix24')->error('deal-hook: save failed', ['task_id' => $task->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status'     => 'ok',
            'deal_id'    => $dealId,
            'task_id'    => $task->id,
            'created'    => $isNew,
        ], 200);
    }

    /**
     * Актуализирует опции поля «Сотрудник Bitrix24» (users.b24_responsible)
     * списком активных сотрудников Bitrix24 (значение = ID, метка = Имя Фамилия).
     * Троттлинг — не чаще раза в 10 минут (через settings).
     */
    private function actualizeBitrix24Employees($base, $usersTypeId)
    {
        if (!$usersTypeId || !$base) {
            return;
        }

        $throttle = \DB::table('settings')->where('type', 'b24_employees_synced')->first();
        if ($throttle && ($last = (int) $throttle->value) && (time() - $last) < 600) {
            return;
        }

        try {
            $options = [];
            $start = 0;
            $guard = 0;
            do {
                $resp = Http::post($base . 'user.get', ['start' => $start, 'ACTIVE' => true])->collect();
                $batch = $resp['result'] ?? [];
                if (!is_array($batch)) {
                    break;
                }
                foreach ($batch as $u) {
                    $id = $u['ID'] ?? null;
                    if ($id === null || $id === '') {
                        continue;
                    }
                    $name = trim(($u['NAME'] ?? '') . ' ' . ($u['LAST_NAME'] ?? ''));
                    if ($name === '') {
                        $name = 'ID ' . $id;
                    }
                    $options[] = ['value' => (string) $id, 'label' => $name];
                }
                $start = $resp['next'] ?? null;
                $guard++;
            } while ($start !== null && count($batch) > 0 && $guard < 100);

            if (count($options)) {
                $field = \DB::table('data_rows')
                    ->where('data_type_id', $usersTypeId)
                    ->where('field', 'b24_responsible')
                    ->first();
                if ($field) {
                    \DB::table('data_rows')->where('id', $field->id)->update([
                        'details' => json_encode(['options' => $options], JSON_UNESCAPED_UNICODE),
                    ]);
                    try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
                }
            }
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->warning('deal-hook: b24 employees sync failed', ['error' => $e->getMessage()]);
        }

        \DB::table('settings')->updateOrInsert(
            ['type' => 'b24_employees_synced', 'entity' => null, 'user_id' => null],
            ['key' => 'b24_employees_synced', 'value' => (string) time()]
        );
    }

    private function b24EnumLabel($b24Fields, $ufCode, $enumId)
    {
        if ($enumId === '' || $enumId === null) {
            return null;
        }
        $items = $b24Fields[$ufCode]['items'] ?? null;
        if (!is_array($items)) {
            return null;
        }
        foreach ($items as $item) {
            if ((string) ($item['ID'] ?? '') === (string) $enumId) {
                return $item['VALUE'] ?? null;
            }
        }
        return null;
    }

    private function localOptionValueByLabel($entitySlug, $fieldKey, $label)
    {
        if ($label === null || trim((string) $label) === '') {
            return null;
        }

        $row = \DB::table('data_rows')
            ->join('data_types', 'data_rows.data_type_id', '=', 'data_types.id')
            ->where('data_types.slug', $entitySlug)
            ->where('data_rows.field', $fieldKey)
            ->select('data_rows.details')
            ->first();

        if (!$row || !$row->details) {
            return null;
        }

        $details = json_decode($row->details, true);
        if (!is_array($details) || empty($details['options'])) {
            return null;
        }

        $needle = mb_strtolower(trim((string) $label));
        foreach ($details['options'] as $key => $option) {
            if (is_array($option)) {
                $optLabel = $option['label'] ?? null;
                if (is_array($optLabel)) {
                    $optLabel = $optLabel['text'] ?? null;
                }
                $optValue = $option['value'] ?? $key;
            } else {
                $optLabel = $option;
                $optValue = $key;
            }
            if ($optLabel !== null && mb_strtolower(trim((string) $optLabel)) === $needle) {
                return $optValue;
            }
        }
        return null;
    }
}
