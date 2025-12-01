<?php

namespace Modules\Gibdd\Entities;
use Illuminate\Http\Request;



class Module
{
    public static function getPriceKoef()
    {
        $tariff_prices = array(
            1 => 1.03,
            2 => 1.027,
            3 => 1.024,
            4 => 1.018
        );

        $t = \App\Models\Tariff::current();

        return $tariff_prices[$t['id']];
    }
    public static function login()
    {
        
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('POST', 'https://api.driver-helper.ru/v1.0/user/login', [
                'headers' => [
                    //'Authorization' => 'Bearer LJxEWSiSbJxlWcLpwwpUaInQkkVpAihaLGsQgbmAlmVkoYbXajitKJftgXgsimUT',
                    'Accept' => 'application/json',
                    //'Content-Type' => 'application/json',
                ],
                'form_params' => [
                    'username' => 'reg@opt6.ru',
                    'password' => 'ciSnin-6zykdu-mykkyt'
                    //'name' => '123',
                    //'sum' => 300
                    //'uin' => '18810550231018083263'
                ]
            ]);
            $res = json_decode($response->getBody(), true);

            return $res;
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return array('error' => 406);
        }
    }
    public static function exportCars($tenant)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $tenant->run(function () use($token) {
                $client = new \GuzzleHttp\Client();
                $tenant = tenant('id');
                
                $cars_monitoring = array();
                $page = 1;
                $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')/*->whereNull('carsmonitoring_id')*/->get()->keyBy('number');
                $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/cars/?token='.urlencode($token), [
                        'headers' => [
                            'Authorization' => $token,
                        ]
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);
                if(isset($arr['items'])) {
                    foreach($arr['items'] as $item) {

                        $cars_monitoring[] = $item;
                    }
                    while(isset($arr['next']) && $arr['next'] > $page) {
                        $page = $arr['next'];
                        $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/cars/?page='.$page, [
                            'headers' => [
                                'Authorization' => $token,
                            ]
                        ]);
                        $body = $response->getBody()->getContents();
                        $arr = json_decode($body, true);
                        if(isset($arr['items'])) {
                            foreach($arr['items'] as $item) {
                                $cars_monitoring[] = $item;
                            }
                        }
                    }
                }

                foreach($cars_monitoring as $item) {
                    $regnum = mb_strtoupper($item['regnum']);
                    
                    if(isset($cars[$regnum])) {
                        $cars[$regnum]->carsmonitoring_id = $item['id'];
                        $cars[$regnum]->saveQuietly();
                    }
                }
            });
        }
    }

    public static function exportDrivers($tenant)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $tenant->run(function () use($token) {
                $client = new \GuzzleHttp\Client();
                $tenant = tenant('id');
                
                $drivers_monitoring = array();
                $page = 1;
                $drivers = \App\Models\Employee::whereNotNull('driver_license')->get()->keyBy('driver_license');
                $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/drivers/?token='.urlencode($token), [
                        'headers' => [
                            'Authorization' => $token,
                        ]
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);
                if(isset($arr['items'])) {
                    foreach($arr['items'] as $item) {

                        $drivers_monitoring[] = $item;
                    }
                    while(isset($arr['next']) && $arr['next'] > $page) {
                        $page = $arr['next'];
                        $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/drivers/?page='.$page, [
                            'headers' => [
                                'Authorization' => $token,
                            ]
                        ]);
                        $body = $response->getBody()->getContents();
                        $arr = json_decode($body, true);
                        if(isset($arr['items'])) {
                            foreach($arr['items'] as $item) {
                                $drivers_monitoring[] = $item;
                            }
                        }
                    }
                }

                foreach($drivers_monitoring as $item) {
                    $licensenum = mb_strtoupper($item['licensenum']);
                    
                    if(isset($drivers[$licensenum])) {
                        $drivers[$licensenum]->carsmonitoring_id = $item['id'];
                        $drivers[$licensenum]->saveQuietly();
                    }
                }
            });
        }
    }

    public static function importCars($tenant)
    {
        $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->whereNull('carsmonitoring_id')->get();
        foreach($cars as $car) {
            // if($car->id == 440)
            //     continue;
            if($car->sts_number && $car->number) {
                $res = self::addCar([
                    'stsnum' => $car->sts_number,
                    'regnum' => $car->number,
                    'id' => $car->id
                ]);
                if(isset($res['id'])) {
                    $car->carsmonitoring_id = $res['id'];
                    $car->saveQuietly();
                }
            }
        }
    }

    public static function importDrivers($tenant)
    {
        $drivers = \App\Models\Employee::whereNotNull('driver_license')->whereNull('carsmonitoring_id')->get();
        foreach($drivers as $driver) {
            if($driver->driver_license) {
                $res = self::addDriver([
                    'licensenum' => $driver->driver_license,
                    'title' => $driver->name,
                    'id' => $driver->id
                ]);
                print_r($res);
                if(isset($res['id'])) {
                    $driver->carsmonitoring_id = $res['id'];
                    $driver->saveQuietly();
                }
            }
        }
    }

    public static function deleteCar($id)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->request('DELETE', 'https://api.driver-helper.ru/v1.0/car/'.$id, [
                        'headers' => [
                            'Authorization' => $token,
                        ],
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);

                return $arr;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return array('error' => 404);
            }
        }
    }

    public static function deleteDriver($id)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->request('DELETE', 'https://api.driver-helper.ru/v1.0/driver/'.$id, [
                        'headers' => [
                            'Authorization' => $token,
                        ],
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);
                info('deletecar');
                info($arr);

                return $arr;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return array('error' => 404);
            }
        }
    }

    public static function addCar($data)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $sts_num = $data['stsnum'];
            if(!is_array($sts_num) && is_array(json_decode($sts_num, true))) {
                $sts_num = json_decode($sts_num, true)['value'];
            } elseif(is_array($sts_num)) {
                $sts_num = $sts_num['value'];
            }
            $reg_num = $data['regnum'];
            if(!is_array($reg_num) && is_array(json_decode($reg_num, true))) {
                $reg_num = json_decode($reg_num, true)['value'];
            } elseif(is_array($reg_num)) {
                $reg_num = $reg_num['value'];
            }
            //return;
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->request('POST', 'https://api.driver-helper.ru/v1.0/car', [
                        'headers' => [
                            'Authorization' => $token,
                        ],
                        'form_params' => [
                            'stsnum' => (string)$sts_num,
                            'regnum' => (string)$reg_num,
                            'descr' => (string)$data['id']
                        ]
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);

                return $arr;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return array('error' => 404);
            }
        }
    }

    public static function addDriver($data)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->request('POST', 'https://api.driver-helper.ru/v1.0/driver', [
                        'headers' => [
                            'Authorization' => $token,
                        ],
                        'form_params' => [
                            'licensenum' => $data['licensenum'],
                            'title' => $data['title'],
                            'descr' => $data['id']
                        ]
                    ]);
                    
                $body = $response->getBody()->getContents();
                $arr = json_decode($body, true);

                return $arr;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return array('error' => 404);
            }
        }
    }

    public static function updateFines()
    {
        $res = self::login();
        $settings = app('settings');
        if(isset($res['token'])) {
            $token = $res['token'];
            
            $client = new \GuzzleHttp\Client();
            $tenant = tenant('id');
            $current_date = date('d.m.Y');
            $page = 1;
            $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->whereNotNull('carsmonitoring_id')->get()->keyBy('carsmonitoring_id');
            $employees = \App\Models\Employee::whereNotNull('carsmonitoring_id')->get()->keyBy('carsmonitoring_id');
            $fines = \App\Models\GibddFine::get()->keyBy('number_doc')->toArray();

            $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fines/?page=1', [
                    'headers' => [
                        'Authorization' => $token,
                    ]
                ]);
                
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            $new_fines = array();
            if(isset($arr['items'])) {
                foreach($arr['items'] as $item) {
                    $new_fines[] = $item;
                    
                }
                while(isset($arr['next']) && $arr['next'] > $page) {
                    $page = $arr['next'];
                    $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fines/?page='.$page, [
                        'headers' => [
                            'Authorization' => $token,
                        ]
                    ]);
                    $body = $response->getBody()->getContents();
                    $arr = json_decode($body, true);
                    //$page = $arr['next'];
                    if(isset($arr['items'])) {
                        foreach($arr['items'] as $item) {
                            $new_fines[] = $item;
                        }

                    }
                }
            }
            // foreach($cars as $car) {
            //$date_check = date('d.m.Y', strtotime($car->date_check));
            //     $data = array();
            //     $car->date_check = \DB::raw('now()');
            //     $car->saveQuietly();
            //     //info($arr);
                $unpayed_fines = array();
                $company_ids = array();
                foreach($new_fines as $fine) {
                    $record = array();
                    if(!\App\Models\GibddFine::where('number_doc', $fine['post_num'])->exists()) {
                        $text = mb_strtolower($fine['koap_text']);
                        $firstChar = mb_substr($text, 0, 1);
                        $then = mb_substr($text, 1, null);
                        $name = mb_strtoupper($firstChar) . $then;
                        if($fine['doc_type'] == 'sts' && isset($cars[$fine['car']['id']])) {
                            $date_check = date('d.m.Y', strtotime($cars[$fine['car']['id']]->date_check));
                            $cars[$fine['car']['id']]->date_check = \DB::raw('now()');
                            if($cars[$fine['car']['id']]->company_id) {
                                $company_ids[] = $cars[$fine['car']['id']]->company_id;
                            }
                            $record = array(
                                'date'=> date('Y-m-d', strtotime($fine['fine_date'])),
                                'company_id'=> $cars[$fine['car']['id']]->company_id,
                                //'employee_id'=> '[value-9]',
                                'car_id'=> $cars[$fine['car']['id']]->id,
                                'number_doc'=> $fine['post_num'],
                                'date_doc'=> date('Y-m-d', strtotime($fine['post_date'])),
                                'place'=> $fine['address'],
                                'article'=> $fine['koap_code'],
                                'discharged_by'=> $fine['soiname'],
                                //'name_of_payment'=> $fine['KoAPtext'],
                                'name_of_payment'=> $fine['wirepaymentpurpose'],
                                'kbk'=> $fine['wirekbk'],
                                'inn'=> $fine['wireuserinn'],
                                'kpp'=> $fine['wirekpp'],
                                'bank'=> $fine['wirebankname'],
                                'invoice'=> $fine['wirebankaccount'],
                                'corr_invoice'=> $fine['wirebankks'],
                                'bik'=> $fine['wirebankbik'],
                                'oktmo'=> $fine['wireoktmo'],
                                'sum'=> $fine['total_suma'],
                                'discount_sum'=> $fine['suma'],
                                'sale_finish'=> isset($fine['discount_date']) && $fine['discount_date'] ? date('Y-m-d', strtotime($fine['discount_date'])) : null,
                                'name'=> $name,//$fine['VehicleModel'],
                                //'division_id' => $fine['Division_id'],
                                //'status' => $fine['paid'] ? 745 : 746,
                                'payer_identifier' => $payer_identifier
                            );
                            $payment = array(
                                'value' => $record['sale_finish'] > date('Y-m-d') ? $fine['suma'] : $fine['total_suma'],
                                'state' => 0
                            );
                            if($fine['paid']) {
                                $payment['state'] = 1;
                            }
                            $record['payment'] = json_encode($payment);
                        } elseif($fine['doc_type'] == 'driver_licence' && isset($employees[$fine['driver']['id']])) {
                            $date_check = date('d.m.Y', strtotime($employees[$fine['driver']['id']]->date_check));
                            $employees[$fine['driver']['id']]->date_check = \DB::raw('now()');
                            if($employees[$fine['driver']['id']]->company_id) {
                                $company_ids[] = $employees[$fine['driver']['id']]->company_id;
                            }
                            $record = array(
                                'date'=> date('Y-m-d', strtotime($fine['fine_date'])),
                                'company_id'=> $employees[$fine['driver']['id']]->company_id,
                                'employee_id'=> $employees[$fine['driver']['id']]->id,
                                //'car_id'=> $cars[$fine['car']['id']]->id,
                                'number_doc'=> $fine['post_num'],
                                'date_doc'=> date('Y-m-d', strtotime($fine['post_date'])),
                                'place'=> $fine['address'],
                                'article'=> $fine['koap_code'],
                                'discharged_by'=> $fine['soiname'],
                                //'name_of_payment'=> $fine['KoAPtext'],
                                'name_of_payment'=> $fine['wirepaymentpurpose'],
                                'kbk'=> $fine['wirekbk'],
                                'inn'=> $fine['wireuserinn'],
                                'kpp'=> $fine['wirekpp'],
                                'bank'=> $fine['wirebankname'],
                                'invoice'=> $fine['wirebankaccount'],
                                'corr_invoice'=> $fine['wirebankks'],
                                'bik'=> $fine['wirebankbik'],
                                'oktmo'=> $fine['wireoktmo'],
                                'sum'=> $fine['total_suma'],
                                'discount_sum'=> $fine['suma'],
                                'sale_finish'=> isset($fine['discount_date']) && $fine['discount_date'] ? date('Y-m-d', strtotime($fine['discount_date'])) : null,
                                'name'=> $name,//$fine['VehicleModel'],
                                //'division_id' => $fine['Division_id'],
                                //'status' => $fine['paid'] ? 745 : 746
                                'payer_identifier' => $payer_identifier
                            );
                            $payment = array(
                                'value' => $record['sale_finish'] > date('Y-m-d') ? $fine['suma'] : $fine['total_suma'],
                                'state' => 0
                            );
                            if($fine['paid']) {
                                $payment['state'] = 1;
                            }
                            $record['payment'] = json_encode($payment);
                        }
                        if(count($record)) {
                            $photos = array();
                            $photo_response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fine/'.$fine['id'].'/photo/base64', [
                                'headers' => [
                                    'Authorization' => $token,
                                ]
                            ]);
                            
                            $body_photo = $photo_response->getBody()->getContents();
                            $arr_photo = json_decode($body_photo, true);
                            $i = 0;
                            if(isset($arr_photo['status']) && $arr_photo['status'] == 'photo_exists') {
                                foreach($arr_photo['photos'] as $i => $photo) {
                                    $disk = \Storage::disk('public');
                                    $file = $fine['id'].'_'.$i.'_'.time().'.jpg';
                                    $disk->put('files/'.$file, base64_decode($photo));
                                    $ext = 'jpg';
                                    $document = new \App\Models\File();
                                    $document->name = $file;
                                    $document->path = 'files/'.$file;
                                    $document->save();
                                    $thumbnail = \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path)->heighten(200)->url();
                                    $uid = time();
                                    $photos[] = array(
                                        'id' => $document->id,
                                        'name' => $file,
                                        'url' => $thumbnail,
                                        'file' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                                        'extension' => $ext,
                                        'sort' => $i,
                                        'uid' => $uid,
                                    );
                                    $i++;
                                    //$media = $document->addMediaFromUrl('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$file)->toMediaCollection();
                                }
                            }
                                     
                            if(count($photos))
                                $record['photo'] = json_encode($photos);

                            if(isset($record)) {
                                $file = 'gbdd.png';
                                if(strstr($fine['wireusername'], 'АМПП')) {
                                    $file = 'ampp.png';
                                } elseif(strstr($fine['wireusername'], 'МАДИ')) {
                                    $file = 'madi.png';
                                }
                                $icons = array();
                                $icons[] = array(
                                    'id' => 0,
                                    'name' => $file,
                                    'url' => 'https://'.$tenant.'.compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                                    'file' => 'https://'.$tenant.'.compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                                    'extension' => 'png',
                                    'sort' => 0,
                                );
                                $record['icon'] = json_encode($icons);

                                $data[] = $record;
                            }
                        }
                    } else {
                        $unpayed_fines[] =  $fine['post_num'];
                    }
                    
                }
                $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->whereNotNull('carsmonitoring_id')->get()->keyBy('id');
                $employees = \App\Models\Employee::whereNotNull('carsmonitoring_id')->get()->keyBy('id');
                $field_car = \DB::table('data_rows')->find(2222);
                $field_driver = \DB::table('data_rows')->find(2431);
                $field_company = \DB::table('data_rows')->find(2491);
                
                if(isset($data) && count($data)) {
                    $new_fines_cars = $new_fines_drivers = $new_fines_companies = array();
                    
                    foreach($data as $k => $record) {
                        if(!$record['name'])
                            continue;
                        $fine = new \App\Models\GibddFine;
                        
                        $fine->saveQuietly();
                        $history_text = 'Создана запись: '.$fine->id;
                        $history = new \App\Models\History(['entity' => 'fines_gibdd', 'event' => 'OBJECT_CREATED', 'entity_id' => $fine->id, 'user_id' => 1, 'text' => $history_text]);
                        $history->save();
                        $fine->name = $record['name'];
                        if(isset($record['car_id']))
                            $new_fines_cars[$record['car_id']][] = $fine;
                        if(isset($record['employee_id']))
                            $new_fines_drivers[$record['employee_id']][] = $fine;
                        if(isset($record['company_id']) && $record['company_id'])
                            $new_fines_companies[$record['company_id']][] = $fine;
                        $data[$k]['id'] = $fine->id;
                    }
                    $settings = \App\Models\Settings::get(true);
                    
                    foreach($cars as $id => $car) {
                        $old_fines = $car->fines_gibdd->pluck('id')->toArray();
                        if(isset($new_fines_cars[$id])) {
                            $new = array();
                            $new_values = array();
                            foreach($new_fines_cars[$id] as $fine) {
                                $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                                $new_values[] = $fine->id;
                            }

                            $history_text = $field_car->title.', добавлена связь: '.implode(', ', $new);
                            $history_data = array(
                                'entity' => 'cars', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_car->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $new_values),
                                'event' => 'RELATION_ADDED',
                                'color' => '#23704B',
                                'is_relation' => 1,
                                'module' => 'gibdd'
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                            $history = $history->replicate();
                            $history->module = null;
                            $history->saveQuietly();

                            $old_value = \App\Models\Field::getHumanValue($field_car, $old_fines);
                            $new_value = array_merge($old_value['arr_res'], $new);
                            $history_text = $field_car->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                            $history_data = array(
                                'entity' => 'cars', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_car->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $new_value),
                                'event' => 'FIELD_UPDATED',
                                'is_relation' => 1
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                            // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                            // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                        } else {
                            $history_text = 'Новых штрафов не найдено';
                            $history_data = array(
                                'entity' => 'cars', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_car->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $old_fines),
                                'event' => 'CHECKING_FINES',
                                'color' => '#23704B',
                                'is_relation' => 1,
                                'module' => 'gibdd'
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                        }
                    }
                    foreach($employees as $id => $driver) {
                        $old_fines = $driver->fines_gibdd->pluck('id')->toArray();
                        if(isset($new_fines_drivers[$id])) {
                            $new = array();
                            $new_values = array();
                            foreach($new_fines_drivers[$id] as $fine) {
                                $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                                $new_values[] = $fine->id;
                            }

                            $history_text = $field_driver->title.', добавлена связь: '.implode(', ', $new);
                            $history_data = array(
                                'entity' => 'employees', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_driver->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $new_values),
                                'event' => 'RELATION_ADDED',
                                'color' => '#23704B',
                                'is_relation' => 1,
                                'module' => 'gibdd'
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                            $history = $history->replicate();
                            $history->module = null;
                            $history->saveQuietly();

                            $old_value = \App\Models\Field::getHumanValue($field_driver, $old_fines);
                            $new_value = array_merge($old_value['arr_res'], $new);
                            $history_text = $field_driver->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                            $history_data = array(
                                'entity' => 'employees', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_driver->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $new_values),
                                'event' => 'FIELD_UPDATED',
                                'is_relation' => 1
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                            // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                            // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                        } else {
                            $history_text = 'Новых штрафов не найдено';
                            $history_data = array(
                                'entity' => 'employees', 
                                'entity_id' => $id, 
                                'user_id' => 1,
                                'text' => $history_text,
                                'field' => $field_driver->field,
                                'old_value' => implode(', ', $old_fines),
                                'new_value' => implode(', ', $old_fines),
                                'event' => 'CHECKING_FINES',
                                'color' => '#23704B',
                                'is_relation' => 1,
                                'module' => 'gibdd'
                            );
                            $history = new \App\Models\History($history_data);

                            $history->saveQuietly();
                        }
                    }
                    if(count($company_ids)) {
                        $companies = \App\Models\Company::whereIntegerInRaw('id', $company_ids)->get()->keyBy('id');
                        foreach($companies as $id => $company) {
                            $old_fines = $company->fines_gibdd->pluck('id')->toArray();
                            if(isset($new_fines_companies[$id])) {
                                $new = array();
                                $new_values = array();
                                foreach($new_fines_companies[$id] as $fine) {
                                    $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                                    $new_values[] = $fine->id;
                                }

                                $history_text = $field_company->title.', добавлена связь: '.implode(', ', $new);
                                $history_data = array(
                                    'entity' => 'companies', 
                                    'entity_id' => $id, 
                                    'user_id' => 1,
                                    'text' => $history_text,
                                    'field' => $field_company->field,
                                    'old_value' => implode(', ', $old_fines),
                                    'new_value' => implode(', ', $new_values),
                                    'event' => 'RELATION_ADDED',
                                    'color' => '#23704B',
                                    'is_relation' => 1,
                                    'module' => 'gibdd'
                                );
                                $history = new \App\Models\History($history_data);

                                $history->saveQuietly();
                                $history = $history->replicate();
                                $history->module = null;
                                $history->saveQuietly();
                                $old_value = \App\Models\Field::getHumanValue($field_company, $old_fines);
                                $new_value = array_merge($old_value['arr_res'], $new);
                                $history_text = $field_company->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                                $history_data = array(
                                    'entity' => 'companies', 
                                    'entity_id' => $id, 
                                    'user_id' => 1,
                                    'text' => $history_text,
                                    'field' => $field_company->field,
                                    'old_value' => implode(', ', $old_fines),
                                    'new_value' => implode(', ', $new_values),
                                    'event' => 'FIELD_UPDATED',
                                    'is_relation' => 1
                                );
                                $history = new \App\Models\History($history_data);

                                $history->saveQuietly();
                                // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                                // \App\Models\History::saveForObject('cars', [['id' => $id, 'fine_id' => $new_fines]]);
                            } else {
                                // $history_text = 'Новых штрафов не найдено';
                                // $history_data = array(
                                //     'entity' => 'employees', 
                                //     'entity_id' => $id, 
                                //     'user_id' => 1,
                                //     'text' => $history_text,
                                //     'field' => $field_driver->field,
                                //     'old_value' => implode(', ', $old_fines),
                                //     'new_value' => implode(', ', $old_fines),
                                //     'event' => 'MODULE_EVENT',
                                //     'color' => '#23704B',
                                //     'is_relation' => 1
                                // );
                                // $history = new \App\Models\History($history_data);

                                // $history->saveQuietly();
                            }
                        }
                    }
                    $local_module = \DB::table('modules')->where('slug', 'gibdd')->first();
                    $data_m = json_decode($local_module->config, true);
                    if($data_m['fields'][3]['value'] == 1) {
                        $distribution_users = \DB::table('users')->select('id')->pluck('id')->toArray();
                    } elseif($data_m['fields'][3]['value'] == 2) {
                        $user_id = $data_m['fields'][4]['value']['value'][0];
                        $distribution_users = array($user_id);
                    } elseif($data_m['fields'][3]['value'] == 3) {
                        $role_id = $data_m['fields'][5]['value']['value'][0];
                        $distribution_users = \DB::table('users')->select('id')->where('role_id', $role_id)->pluck('id')->toArray();
                    }
                    $last_distribution = null;
                    if(isset($data_m['last_distribution'])) {
                        $last_distribution = $data_m['last_distribution'];
                    }
                    foreach($data as $record) {

                        //$record['id'] = $fine->id;
                        $record['user_id'] = null;
                        if((!$last_distribution || !in_array($last_distribution, $distribution_users)) && isset($distribution_users[0])) {
                            $record['user_id'] = $distribution_users[0];
                        } else {
                            foreach($distribution_users as $k => $id) {
                                if($id == $last_distribution && isset($distribution_users[$k+1])) {
                                    $record['user_id'] = $distribution_users[$k+1];
                                } elseif($id == $last_distribution) {
                                    $record['user_id'] = $distribution_users[0];
                                }
                            }
                        }
                        if(!$record['user_id'])
                            $record['user_id'] = 1;
                        $last_distribution = $data_m['last_distribution'] = $record['user_id'];
                        \DB::table('modules')->where('slug', 'gibdd')->update(['config' => json_encode($data_m)]);
                        \App\Models\History::saveForObject('fines_gibdd', array($record));
                        $fine_id = $record['id'];
                        unset($record['id']);
                        \DB::table('fines_gibdd')->where('id', $fine_id)->update($record);
                        //$fine->update($record);
                    };
                    \App\Models\Settings::clear_cache();
                    
                    
                    //$fine->saveHistory($row)
                    //\DB::table('fines_gibdd')->insert($data);
                } else {
                    foreach($cars as $id => $car) {
                        $history_text = 'Новых штрафов не найдено';
                        $history_data = array(
                            'entity' => 'cars', 
                            'entity_id' => $id, 
                            'user_id' => 1,
                            'text' => $history_text,
                            'field' => $field_car->field,
                            'old_value' => '',
                            'new_value' => '',
                            'event' => 'CHECKING_FINES',
                            'color' => '#23704B',
                            'is_relation' => 1,
                            'module' => 'gibdd'
                        );
                        $history = new \App\Models\History($history_data);

                        $history->saveQuietly();
                    }

                    foreach($employees as $id => $car) {
                        $history_text = 'Новых штрафов не найдено';
                        $history_data = array(
                            'entity' => 'employees', 
                            'entity_id' => $id, 
                            'user_id' => 1,
                            'text' => $history_text,
                            'field' => $field_driver->field,
                            'old_value' => '',
                            'new_value' => '',
                            'event' => 'CHECKING_FINES',
                            'color' => '#23704B',
                            'is_relation' => 1,
                            'module' => 'gibdd'
                        );
                        $history = new \App\Models\History($history_data);

                        $history->saveQuietly();
                    }
                }

            //}
            foreach($fines as $fine) {
                $k = array_search($fine['number_doc'], $unpayed_fines);
                if($k === false) {
                    //\DB::table('fines_gibdd')->where('id', $fine['id'])->update(['status' => 745]);
                }
            }
            
        }
    }

    public static function findByNum($num)
    {
        $fine = \DB::table('fines_gibdd')->where('number_doc', $num)->first();
        if($fine) {
            return array(
                'code' => 200,
                'id' => $fine->id,
            );
        }
        self::findFines();
        $fine = \DB::table('fines_gibdd')->where('number_doc', $num)->first();
        if($fine) {
            return array(
                'code' => 200,
                'id' => $fine->id,
            );
        } else {
            return array(
                'code' => 404,
                'error' => 'Штраф не найден',
            );
        }
        
    }

    public static function getSmevFindCharges($async_id)
    {
        info('getSmevFindCharges');
        $client = new \GuzzleHttp\Client();
        $new_fines = array();

        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ],
                    "PayloadNamespace" => "http://www.moneta.ru/schemas/messages-chargesubscription.xsd"
                ],
                "Body" => [
                    "AsyncRequest" => [
                        "asyncId" => $async_id
                    ]
                ]
            ]
        ];
        
        $response = $client->request('POST', 'https://service.moneta.ru:56443/services', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_data)
        ]);
        
        $body = $response->getBody()->getContents();
        $arr = json_decode($body, true);
        if(!isset($arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']))
            return [];

        $new_fines = $arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']['charge'];

        $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();

        return $new_fines;
    }

    public static function sendSmevFindCharges(array $fines)
    {
        info('sendSmevFindCharges');
        $client = new \GuzzleHttp\Client();

        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ],
                    "PayloadNamespace" => "http://www.moneta.ru/schemas/messages-chargesubscription.xsd"
                ],
                "Body" => [
                    "AsyncRequest" => [
                        "SmevFindChargesRequest" => [
                            "supplierBillIDs" => [
                                "supplierBillID" => $fines
                            ],
                            "isTax" => "false"
                        ]
                    ]
                ]
            ]
        ];
        info(json_encode($request_data));
        $response = $client->request('POST', 'https://service.moneta.ru:56443/services', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_data)
        ]);
        
        $body = $response->getBody()->getContents();
        $arr = json_decode($body, true);
        if(!isset($arr['Envelope']['Body']['AsyncResponse']['asyncId']))
            return;

        return $arr['Envelope']['Body']['AsyncResponse']['asyncId'];
    }
    public static function findFines()
    {
        $settings = app('settings');
        $sts_prefix = '124000000000';
        $license_prefix = '122000000000';
        $inn_prefix = '200';
        
        $client = new \GuzzleHttp\Client();
        $tenant = tenant('id');
        $current_date = date('d.m.Y');
        $page = 1;

        $objects_car = \App\Models\Car::whereNotNull('sts_number')->get()->keyBy('sts_number');
        $objects_employee = \App\Models\Employee::whereNotNull('driver_license')->get()->keyBy('driver_license');
        $objects_company = \App\Models\Company::whereNotNull('inn')->whereNotNull('kpp')->get()->keyBy('inn');
        $fines = \App\Models\GibddFine::get()->keyBy('number_doc')->toArray();

        $cars = $employees = $companies = $sts_array = $license_array = $inn_array = [];
        foreach($objects_car as $sts => $car) {
            $val = json_decode($sts, true);
            if(is_array($val)) {
                $val = $val['value'];
            }
            $val = $sts_prefix.$val;
            if(strlen($val) == 22) {
                $cars[$val] = $car;
                array_push($sts_array, $val);
            }
        }
        foreach($objects_employee as $license => $employee) {
            $val = json_decode($license, true);
            if(is_array($val)) {
                $val = $val['value'];
            }
            $val = $license_prefix.$val;
            if(strlen($val) == 22 && is_numeric($val)) {
                $employees[$val] = $employee;
                array_push($license_array, $val);
            }
        }
        foreach($objects_company as $inn => $company) {
            $val = json_decode($inn, true);
            if(is_array($val)) {
                $val = $val['value'];
            }
            $val = $inn_prefix.$val;
            $kpp = json_decode($company->kpp, true);
            if(is_array($kpp)) {
                $kpp = $kpp['value'];
            }
            $val = $val.$kpp;
            if(strlen($val) == 22 && is_numeric($val)) {
                $companies[$val] = $company;
                array_push($inn_array, $val);
            }
        }
        $page = 1;
        $has_more = true;
        $new_fines = [];
        $payerIdentifiers = array_merge($sts_array, $license_array, $inn_array);
        // echo '<pre>';
        // print_r($payerIdentifiers);
        // echo '</pre>';
        // die();
        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ]
                ],
                "Body" => [
                    "FindChargesRequest" => [
                        "payerIdentifiers" => [
                            "payerIdentifier" => $payerIdentifiers
                        ],
                        //"dateFrom": "2022-12-11",
                        //"dateTo": "2024-09-02",
                        "pager" => [
                            "pageNumber" => $page,
                            "pageSize" => 100
                        ]
                    ]
                ]
            ]
        ];
        //info(json_encode($request_data));
        while($has_more) {
            $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($request_data)
            ]);
            
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            if(!isset($arr['Envelope']['Body']['FindChargesResponse']['charges']))
                return;
            //info($arr['Envelope']['Body']['FindChargesResponse']['charges']);
            if(isset($arr['Envelope']['Body']['FindChargesResponse']['charges']['charge'])) {
                $new_fines = array_merge($new_fines, $arr['Envelope']['Body']['FindChargesResponse']['charges']['charge']);
            }
            $has_more = $arr['Envelope']['Body']['FindChargesResponse']['hasMore'];
            $page++;
            $request_data['Envelope']['Body']['FindChargesRequest']['pager']['pageNumber'] = $page;

        }
        
        $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();
        echo '<pre>';
        print_r($new_fines);
        echo '</pre>';
        echo '<pre>';
        print_r($cars);
        echo '</pre>';
        $fines_need_smev = array();
        foreach($new_fines as $bill_id => $fine) {
            if($fine['receivingMethod'] == 'N') {
                $fines_need_smev[] = $bill_id;
            }
        }

        if(count($fines_need_smev)) {
            $async_id = self::sendSmevFindCharges($fines_need_smev);
            sleep(3);
            $fines_need_smev = self::getSmevFindCharges($async_id);
            foreach($fines_need_smev as $bill_id => $fine) {
                $new_fines[$bill_id] = $fine;
            }
        }

        

        // echo '<pre>';
        // print_r($fines_need_smev);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($new_fines);
        // echo '</pre>';
        //die();
        //18810577240823894840
        

        $unpayed_fines = array();
        $company_ids = array();
        foreach($new_fines as $fine) {
            $record = array();
            if(!\App\Models\GibddFine::where('number_doc', $fine['supplierBillID'])->exists()) {
                // $text = mb_strtolower($fine['koap_text']);
                // $firstChar = mb_substr($text, 0, 1);
                // $then = mb_substr($text, 1, null);
                // $name = mb_strtoupper($firstChar) . $then;
                $payer_identifier = isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];
                $payment_attributes = array();
                if(isset($fine['paymentAdditionalData']['attribute']) && is_array($fine['paymentAdditionalData']['attribute'])) {
                    foreach($fine['paymentAdditionalData']['attribute'] as $attribute) {
                        $payment_attributes[$attribute['key']] = $attribute['value'];
                    }
                }
                $charge_attributes = array();
                if(isset($fine['chargeAdditionalData']['attribute']) && is_array($fine['chargeAdditionalData']['attribute'])) {
                    foreach($fine['chargeAdditionalData']['attribute'] as $attribute) {
                        $charge_attributes[$attribute['key']] = $attribute['value'];
                    }
                }
                $name = isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : $fine['payeeName'];
                $coords = isset($charge_attributes['offenseCoordinates']) && $charge_attributes['offenseCoordinates'] != 'NULL' ? explode(',', $charge_attributes['offenseCoordinates']) : [null,null];
                $address = isset($charge_attributes['OffensePlace']) ? ['text' => $charge_attributes['OffensePlace'], 'coords' => $coords] : null;
                echo $payer_identifier.'<br>';
                if(isset($cars[$payer_identifier])) {
                    $current_car = $cars[$payer_identifier];
                    $date_check = date('d.m.Y', strtotime($current_car->date_check));
                    $current_car->date_check = \DB::raw('now()');
                    if($current_car->company_id) {
                        $company_ids[] = $current_car->company_id;
                    }
                    // payerIdentifier - идентификатор плательщика
                    // payerName - плательщик
                    // wirePaymentPurpose - назначение платежа
                    // totalAmount - сумма начисления
                    // amountToPay - остаток суммы подлежащей оплате, указанной в начислении. При переплате начисления принимает отрицательное значение; при 
                    // полной оплате — значение «0».
                    // discountAmount - рассчитанная сумма к оплате со скидкой, если скидка актуальна на данный момент
                    // acknowledgmentStatus - статус присвоенный начислению
                    // wireKBK - КБК, указанный в начислении
                    // wireOKTMO - Код ОКТМО, указанный в распоряжении о переводе денежных средств
                    // payeeName - наименование организации
                    // wireUserINN - ИНН органа ФК, на счёт которого должны поступать средства плательщика
                    // wireUserName - наименование получателя платежа, cокращreceivingMethod енное наименование ТОФК (территориальное отделение 
                    // федерального казначейства)
                    // wireKPP - КПП организации
                    // wireBankAccount - номер банковского счета
                    // wireBankName - наименование структурного подразделения кредитной организации или подразделения Банка России, в котором открыт счет.
                    // wireBankBik - БИК структурного подразделения кредитной организации или подразделения Банка России, в котором открыт счет. 
                    // chargeAdditionalData -  дополнительные атрибуты начисления
                    // linkedChargesIdentifiers - идентификаторы начислений, на основании которых выставлено данное начисление
                    // reverseLinkedChargesIdentifiers - идентификаторы начислений, которые ссылаются на данное начисление
                    // paymentAdditionalData - дополнительные поля начисления для оплаты
                    // dateTime - время последней актуализации данных
                    // receivingMethod - метод получения данных (уведомление, экспорт)


                    //     [payerIdentifier] => 1240000000009959406145
                    //     [paymentAdditionalData] => Array
                    //         (
                    //             [attribute] => Array
                    //                 (
                    //                     [0] => Array
                    //                         (
                    //                             [value] => 2024-09-24
                    //                             [key] => DiscountDate
                    //                         )

                    //                     [1] => Array
                    //                         (
                    //                             [value] => 50
                    //                             [key] => DiscountSize
                    //                         )

                    //                 )

                    //         )
                    //     [chargeAdditionalData] => Array
                    //         (
                    //             [attribute] => Array
                    //                 (
                    //                     [0] => Array
                    //                         (
                    //                             [value] => 1146511
                    //                             [key] => DepartmentCode
                    //                         )

                    //                     [1] => Array
                    //                         (
                    //                             [value] => 55.522603,38.119464
                    //                             [key] => offenseCoordinates
                    //                         )

                    //                     [2] => Array
                    //                         (
                    //                             [value] => 2024-08-26 21:59:12
                    //                             [key] => OffenseDate
                    //                         )

                    //                     [3] => Array
                    //                         (
                    //                             [value] => А/Д М-5 «УРАЛ», 42КМ+795М, ИЗ МОСКВЫ, МОСКОВСКАЯ ОБЛ.
                    //                             [key] => OffensePlace
                    //                         )

                    //                     [4] => Array
                    //                         (
                    //                             [value] => 12.09.2 - Превышение скорости движения ТС от 20 до 40 км/ч
                    //                             [key] => LegalAct
                    //                         )

                    //                     [5] => Array
                    //                         (
                    //                             [value] => ЦАФАП Госавтоинспекции ГУ МВД России по Московской области
                    //                             [key] => DepartmentName
                    //                         )

                    //                 )

                    //         )

                    //     [totalAmount] => 500
                    //     [wireOKTMO] => 46755000
                    //     [acknowledgmentStatus] => 1
                    //     [changeStatus] => 1
                    //     [wireBankBik] => 004525987
                    //     [wireDocIndex] => 18810550240935370444
                    

                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_car->company_id,
                        'car_id'=> $current_car->id,
                        'number_doc'=> $fine['supplierBillID'],
                        'date_doc'=> date('Y-m-d', strtotime($fine['billDate'])),
                        'place'=> json_encode($address),
                        'article'=> isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : null,
                        'discharged_by'=> isset($charge_attributes['DepartmentName']) ? $charge_attributes['DepartmentName'] : $fine['wireUserName'],
                        //'name_of_payment'=> $fine['KoAPtext'],
                        'wire_username' => $fine['wireUserName'],
                        'name_of_payment'=> $fine['wirePaymentPurpose'],
                        'kbk'=> $fine['wireKBK'],
                        'inn'=> $fine['wireUserINN'],
                        'kpp'=> $fine['wireKPP'],
                        'bank'=> $fine['wireBankName'],
                        'invoice'=> $fine['wireBankAccount'],
                        'corr_invoice'=> $fine['wireBankKs'],
                        'bik'=> $fine['wireBankBik'],
                        'oktmo'=> $fine['wireOKTMO'],
                        'sum'=> isset($payment_attributes['DiscountDate']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) > date('Y-m-d'))
                            ? $fine['totalAmount'] * 100/$payment_attributes['DiscountSize']
                            : $fine['totalAmount'],
                        'discount_sum'=> $fine['totalAmount'],
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,//$fine['VehicleModel'],
                        //'division_id' => $fine['Division_id'],
                        //'status' => $fine['paid'] ? 745 : 746,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $fine['totalAmount'],
                        'state' => 0
                    );
                    // if($fine['paid']) {
                    //     $payment['state'] = 1;
                    // }
                    $record['payment'] = json_encode($payment);
                } elseif(isset($employees[$payer_identifier])) {
                    $current_employee = $employees[$payer_identifier];
                    $date_check = date('d.m.Y', strtotime($current_employee->date_check));
                    $current_employee->date_check = \DB::raw('now()');
                    if($current_employee->company_id) {
                        $company_ids[] = $current_employee->company_id;
                    }
                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_employee->company_id,
                        'employee_id'=> $current_employee->id,
                        'number_doc'=> $fine['supplierBillID'],
                        'date_doc'=> date('Y-m-d', strtotime($fine['billDate'])),
                        'place'=> json_encode($address),
                        'article'=> isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : null,
                        'discharged_by'=> isset($charge_attributes['DepartmentName']) ? $charge_attributes['DepartmentName'] : $fine['wireUserName'],
                        'wire_username' => $fine['wireUserName'],
                        'name_of_payment'=> $fine['wirePaymentPurpose'],
                        'kbk'=> $fine['wireKBK'],
                        'inn'=> $fine['wireUserINN'],
                        'kpp'=> $fine['wireKPP'],
                        'bank'=> $fine['wireBankName'],
                        'invoice'=> $fine['wireBankAccount'],
                        'corr_invoice'=> $fine['wireBankKs'],
                        'bik'=> $fine['wireBankBik'],
                        'oktmo'=> $fine['wireOKTMO'],
                        'sum'=> isset($payment_attributes['DiscountDate']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) > date('Y-m-d'))
                            ? $fine['totalAmount'] * 100/$payment_attributes['DiscountSize']
                            : $fine['totalAmount'],
                        'discount_sum'=> $fine['totalAmount'],
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $fine['totalAmount'],
                        'state' => 0
                    );
                    $record['payment'] = json_encode($payment);
                } elseif(isset($companies[$payer_identifier])) {
                    $current_company = $companies[$payer_identifier];
                    //$date_check = date('d.m.Y', strtotime($current_company->date_check));
                    //$current_employee->date_check = \DB::raw('now()');
                    //if($current_employee->company_id) {
                        $company_ids[] = $current_company->id;
                    //}
                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_company->id,
                        //'employee_id'=> $current_employee->id,
                        'number_doc'=> $fine['supplierBillID'],
                        'date_doc'=> date('Y-m-d', strtotime($fine['billDate'])),
                        'place'=> isset($charge_attributes['OffensePlace']) ? $charge_attributes['OffensePlace'] : null,
                        'article'=> isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : null,
                        'discharged_by'=> isset($charge_attributes['DepartmentName']) ? $charge_attributes['DepartmentName'] : $fine['wireUserName'],
                        'wire_username' => $fine['wireUserName'],
                        'name_of_payment'=> $fine['wirePaymentPurpose'],
                        'kbk'=> $fine['wireKBK'],
                        'inn'=> $fine['wireUserINN'],
                        'kpp'=> $fine['wireKPP'],
                        'bank'=> $fine['wireBankName'],
                        'invoice'=> $fine['wireBankAccount'],
                        'corr_invoice'=> $fine['wireBankKs'],
                        'bik'=> $fine['wireBankBik'],
                        'oktmo'=> $fine['wireOKTMO'],
                        'sum'=> isset($payment_attributes['DiscountDate']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) > date('Y-m-d'))
                            ? $fine['totalAmount'] * 100/$payment_attributes['DiscountSize']
                            : $fine['totalAmount'],
                        'discount_sum'=> $fine['totalAmount'],
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $fine['totalAmount'],
                        'state' => 0
                    );
                    $record['payment'] = json_encode($payment);
                }
                if(count($record)) {
                    // $photos = array();
                    // $photo_response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fine/'.$fine['id'].'/photo/base64', [
                    //     'headers' => [
                    //         'Authorization' => $token,
                    //     ]
                    // ]);
                    
                    // $body_photo = $photo_response->getBody()->getContents();
                    // $arr_photo = json_decode($body_photo, true);
                    // $i = 0;
                    // if(isset($arr_photo['status']) && $arr_photo['status'] == 'photo_exists') {
                    //     foreach($arr_photo['photos'] as $i => $photo) {
                    //         $disk = \Storage::disk('public');
                    //         $file = $fine['id'].'_'.$i.'_'.time().'.jpg';
                    //         $disk->put('files/'.$file, base64_decode($photo));
                    //         $ext = 'jpg';
                    //         $document = new \App\Models\File();
                    //         $document->name = $file;
                    //         $document->path = 'files/'.$file;
                    //         $document->save();
                    //         $thumbnail = \Thumbnail::src('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path)->heighten(200)->url();
                    //         $uid = time();
                    //         $photos[] = array(
                    //             'id' => $document->id,
                    //             'name' => $file,
                    //             'url' => $thumbnail,
                    //             'file' => 'https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path,
                    //             'extension' => $ext,
                    //             'sort' => $i,
                    //             'uid' => $uid,
                    //         );
                    //         $i++;
                    //     }
                    // }
                             
                    // if(count($photos))
                    //     $record['photo'] = json_encode($photos);

                    if(isset($record)) {
                        $file = 'gbdd.png';
                        if(strstr($fine['wireUserName'], 'АМПП')) {
                            $file = 'ampp.png';
                        } elseif(strstr($fine['wireUserName'], 'МАДИ')) {
                            $file = 'madi.png';
                        }
                        $icons = array();
                        $icons[] = array(
                            'id' => 0,
                            'name' => $file,
                            'url' => 'https://'.$tenant.'.compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                            'file' => 'https://'.$tenant.'.compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                            'extension' => 'png',
                            'sort' => 0,
                        );
                        $record['icon'] = json_encode($icons);

                        $data[] = $record;
                    }
                }
            } else {
                $unpayed_fines[] =  $fine['supplierBillID'];
            }
            
        }
        $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->get()->keyBy('id');
        $employees = \App\Models\Employee::whereNotNull('driver_license')->get()->keyBy('id');
        $companies = \App\Models\Company::whereNotNull('inn')->get()->keyBy('id');
        $field_car = \DB::table('data_rows')->find(2222);
        $field_driver = \DB::table('data_rows')->find(2431);
        $field_company = \DB::table('data_rows')->find(2491);
        
        if(isset($data) && count($data)) {
            $new_fines_cars = $new_fines_drivers = $new_fines_companies = array();
            
            foreach($data as $k => $record) {
                if(!$record['name'])
                    continue;
                $fine = new \App\Models\GibddFine;
                
                $fine->saveQuietly();
                $history_text = 'Создана запись: '.$fine->id;
                $history = new \App\Models\History(['entity' => 'fines_gibdd', 'event' => 'OBJECT_CREATED', 'entity_id' => $fine->id, 'user_id' => 1, 'text' => $history_text]);
                $history->save();
                $fine->name = $record['name'];
                if(isset($record['car_id']))
                    $new_fines_cars[$record['car_id']][] = $fine;
                if(isset($record['employee_id']))
                    $new_fines_drivers[$record['employee_id']][] = $fine;
                if(isset($record['company_id']) && $record['company_id'])
                    $new_fines_companies[$record['company_id']][] = $fine;
                $data[$k]['id'] = $fine->id;
            }
            $settings = \App\Models\Settings::get(true);
            
            foreach($cars as $id => $car) {
                $old_fines = $car->fines_gibdd->pluck('id')->toArray();
                if(isset($new_fines_cars[$id])) {
                    $new = array();
                    $new_values = array();
                    foreach($new_fines_cars[$id] as $fine) {
                        $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                        $new_values[] = $fine->id;
                    }

                    $history_text = $field_car->title.', добавлена связь: '.implode(', ', $new);
                    $history_data = array(
                        'entity' => 'cars', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_car->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $new_values),
                        'event' => 'RELATION_ADDED',
                        'color' => '#23704B',
                        'is_relation' => 1,
                        'module' => 'gibdd'
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();
                    $history = $history->replicate();
                    $history->module = null;
                    $history->saveQuietly();

                    $old_value = \App\Models\Field::getHumanValue($field_car, $old_fines);
                    $new_value = array_merge($old_value['arr_res'], $new);
                    $history_text = $field_car->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                    $history_data = array(
                        'entity' => 'cars', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_car->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $new_value),
                        'event' => 'FIELD_UPDATED',
                        'is_relation' => 1
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();
                } else {
                    $history_text = 'Новых штрафов не найдено';
                    $history_data = array(
                        'entity' => 'cars', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_car->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $old_fines),
                        'event' => 'CHECKING_FINES',
                        'color' => '#23704B',
                        'is_relation' => 1,
                        'module' => 'gibdd'
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();
                    $history = $history->replicate();
                    $history->module = null;
                    $history->saveQuietly();
                }
            }
            foreach($employees as $id => $driver) {
                $old_fines = $driver->fines_gibdd->pluck('id')->toArray();
                if(isset($new_fines_drivers[$id])) {
                    $new = array();
                    $new_values = array();
                    foreach($new_fines_drivers[$id] as $fine) {
                        $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                        $new_values[] = $fine->id;
                    }

                    $history_text = $field_driver->title.', добавлена связь: '.implode(', ', $new);
                    $history_data = array(
                        'entity' => 'employees', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_driver->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $new_values),
                        'event' => 'RELATION_ADDED',
                        'color' => '#23704B',
                        'is_relation' => 1,
                        'module' => 'gibdd'
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();
                    $history = $history->replicate();
                    $history->module = null;
                    $history->saveQuietly();

                    $old_value = \App\Models\Field::getHumanValue($field_driver, $old_fines);
                    $new_value = array_merge($old_value['arr_res'], $new);
                    $history_text = $field_driver->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                    $history_data = array(
                        'entity' => 'employees', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_driver->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $new_values),
                        'event' => 'FIELD_UPDATED',
                        'is_relation' => 1
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();
                } else {
                    $history_text = 'Новых штрафов не найдено';
                    $history_data = array(
                        'entity' => 'employees', 
                        'entity_id' => $id, 
                        'user_id' => 1,
                        'text' => $history_text,
                        'field' => $field_driver->field,
                        'old_value' => implode(', ', $old_fines),
                        'new_value' => implode(', ', $old_fines),
                        'event' => 'CHECKING_FINES',
                        'color' => '#23704B',
                        'is_relation' => 1,
                        'module' => 'gibdd'
                    );
                    $history = new \App\Models\History($history_data);

                    $history->saveQuietly();

                    $history = $history->replicate();
                    $history->module = null;
                    $history->saveQuietly();
                }
            }
            if(count($company_ids)) {
                $companies = \App\Models\Company::whereIntegerInRaw('id', $company_ids)->get()->keyBy('id');
                foreach($companies as $id => $company) {
                    $old_fines = $company->fines_gibdd->pluck('id')->toArray();
                    if(isset($new_fines_companies[$id])) {
                        $new = array();
                        $new_values = array();
                        foreach($new_fines_companies[$id] as $fine) {
                            $new[] = "<span data-slug='fines_gibdd' data-id='$fine->id'>$fine->name</span>";
                            $new_values[] = $fine->id;
                        }

                        $history_text = $field_company->title.', добавлена связь: '.implode(', ', $new);
                        $history_data = array(
                            'entity' => 'companies', 
                            'entity_id' => $id, 
                            'user_id' => 1,
                            'text' => $history_text,
                            'field' => $field_company->field,
                            'old_value' => implode(', ', $old_fines),
                            'new_value' => implode(', ', $new_values),
                            'event' => 'RELATION_ADDED',
                            'color' => '#23704B',
                            'is_relation' => 1,
                            'module' => 'gibdd'
                        );
                        $history = new \App\Models\History($history_data);

                        $history->saveQuietly();
                        $history = $history->replicate();
                        $history->module = null;
                        $history->saveQuietly();
                        $old_value = \App\Models\Field::getHumanValue($field_company, $old_fines);
                        $new_value = array_merge($old_value['arr_res'], $new);
                        $history_text = $field_company->title.': '.$old_value['res'].' -> '.implode(', ', $new_value);
                        $history_data = array(
                            'entity' => 'companies', 
                            'entity_id' => $id, 
                            'user_id' => 1,
                            'text' => $history_text,
                            'field' => $field_company->field,
                            'old_value' => implode(', ', $old_fines),
                            'new_value' => implode(', ', $new_values),
                            'event' => 'FIELD_UPDATED',
                            'is_relation' => 1
                        );
                        $history = new \App\Models\History($history_data);

                        $history->saveQuietly();
                    }
                }
            }
            $local_module = \DB::table('modules')->where('slug', 'gibdd')->first();
            $data_m = json_decode($local_module->config, true);
            if($data_m['fields'][3]['value'] == 1) {
                $distribution_users = \DB::table('users')->select('id')->pluck('id')->toArray();
            } elseif($data_m['fields'][3]['value'] == 2) {
                $user_id = $data_m['fields'][4]['value']['value'][0];
                $distribution_users = array($user_id);
            } elseif($data_m['fields'][3]['value'] == 3) {
                $role_id = $data_m['fields'][5]['value']['value'][0];
                $distribution_users = \DB::table('users')->select('id')->where('role_id', $role_id)->pluck('id')->toArray();
            }
            $last_distribution = null;
            if(isset($data_m['last_distribution'])) {
                $last_distribution = $data_m['last_distribution'];
            }
            foreach($data as $record) {
                $record['user_id'] = null;
                unset($record['wire_username']);
                unset($record['payer_identifier']);
                if((!$last_distribution || !in_array($last_distribution, $distribution_users)) && isset($distribution_users[0])) {
                    $record['user_id'] = $distribution_users[0];
                } else {
                    foreach($distribution_users as $k => $id) {
                        if($id == $last_distribution && isset($distribution_users[$k+1])) {
                            $record['user_id'] = $distribution_users[$k+1];
                        } elseif($id == $last_distribution) {
                            $record['user_id'] = $distribution_users[0];
                        }
                    }
                }
                if(!$record['user_id'])
                    $record['user_id'] = 1;
                $last_distribution = $data_m['last_distribution'] = $record['user_id'];
                \DB::table('modules')->where('slug', 'gibdd')->update(['config' => json_encode($data_m)]);
                \App\Models\History::saveForObject('fines_gibdd', array($record));
                $fine_id = $record['id'];
                unset($record['id']);
                \DB::table('fines_gibdd')->where('id', $fine_id)->update($record);
            };
            \App\Models\Settings::clear_cache();
            
        } else {
            foreach($cars as $id => $car) {
                $history_text = 'Новых штрафов не найдено';
                $history_data = array(
                    'entity' => 'cars', 
                    'entity_id' => $id, 
                    'user_id' => 1,
                    'text' => $history_text,
                    'field' => $field_car->field,
                    'old_value' => '',
                    'new_value' => '',
                    'event' => 'CHECKING_FINES',
                    'color' => '#23704B',
                    'is_relation' => 1,
                    'module' => 'gibdd'
                );
                $history = new \App\Models\History($history_data);

                $history->saveQuietly();
                $history = $history->replicate();
                $history->module = null;
                $history->saveQuietly();
            }

            foreach($employees as $id => $car) {
                $history_text = 'Новых штрафов не найдено';
                $history_data = array(
                    'entity' => 'employees', 
                    'entity_id' => $id, 
                    'user_id' => 1,
                    'text' => $history_text,
                    'field' => $field_driver->field,
                    'old_value' => '',
                    'new_value' => '',
                    'event' => 'CHECKING_FINES',
                    'color' => '#23704B',
                    'is_relation' => 1,
                    'module' => 'gibdd'
                );
                $history = new \App\Models\History($history_data);

                $history->saveQuietly();
            }

            foreach($companies as $id => $company) {
                $history_text = 'Новых штрафов не найдено';
                $history_data = array(
                    'entity' => 'companies', 
                    'entity_id' => $id, 
                    'user_id' => 1,
                    'text' => $history_text,
                    'field' => $field_company->field,
                    'old_value' => '',
                    'new_value' => '',
                    'event' => 'CHECKING_FINES',
                    'color' => '#23704B',
                    'is_relation' => 1,
                    'module' => 'gibdd'
                );
                $history = new \App\Models\History($history_data);

                $history->saveQuietly();
                $history = $history->replicate();
                $history->module = null;
                $history->saveQuietly();
            }
        }

        // foreach($fines as $fine) {
        //     $k = array_search($fine['number_doc'], $unpayed_fines);
        //     if($k === false) {
        //         //\DB::table('fines_gibdd')->where('id', $fine['id'])->update(['status' => 745]);
        //     }
        // }
    }
    public static function checkByReq(Request $request)
    {
        $sts_prefix = '124000000000';
        $license_prefix = '122000000000';
        $inn_prefix = '200';
        
        $client = new \GuzzleHttp\Client();
        $page = 1;
        $has_more = true;
        $data = $payerIdentifiers = $new_fines = [];

        if($request->sts_number) {
            $val = $sts_prefix.$request->sts_number;
            array_push($payerIdentifiers, $val);
        }
        if($request->driver_license) {
            $val = $license_prefix.$val;
            array_push($payerIdentifiers, $val);
        }
        if($request->inn && $request->kpp) {
            $val = $inn_prefix.$request->inn.$request->kpp;
            array_push($payerIdentifiers, $val);
        }
        

        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ]
                ],
                "Body" => [
                    "FindChargesRequest" => [
                        "payerIdentifiers" => [
                            "payerIdentifier" => $payerIdentifiers
                        ],
                        "pager" => [
                            "pageNumber" => $page,
                            "pageSize" => 100
                        ]
                    ]
                ]
            ]
        ];

        while($has_more) {
            $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($request_data)
            ]);
            
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            if(!isset($arr['Envelope']['Body']['FindChargesResponse']['charges']))
                return;
            if(isset($arr['Envelope']['Body']['FindChargesResponse']['charges']['charge'])) {
                $new_fines = array_merge($new_fines, $arr['Envelope']['Body']['FindChargesResponse']['charges']['charge']);
            }
            $has_more = $arr['Envelope']['Body']['FindChargesResponse']['hasMore'];
            $page++;
            $request_data['Envelope']['Body']['FindChargesRequest']['pager']['pageNumber'] = $page;

        }
        
        $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();
        $fines_need_smev = array();
        foreach($new_fines as $bill_id => $fine) {
            if($fine['receivingMethod'] == 'N') {
                $fines_need_smev[] = $bill_id;
            }
        }

        if(count($fines_need_smev)) {
            $async_id = self::sendSmevFindCharges($fines_need_smev);
            sleep(3);
            $fines_need_smev = self::getSmevFindCharges($async_id);
            foreach($fines_need_smev as $bill_id => $fine) {
                $new_fines[$bill_id] = $fine;
            }
        }

        foreach($new_fines as $fine) {
            $record = array();
            $payer_identifier = isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];
            $payment_attributes = array();
            if(isset($fine['paymentAdditionalData']['attribute']) && is_array($fine['paymentAdditionalData']['attribute'])) {
                foreach($fine['paymentAdditionalData']['attribute'] as $attribute) {
                    $payment_attributes[$attribute['key']] = $attribute['value'];
                }
            }
            $charge_attributes = array();
            if(isset($fine['chargeAdditionalData']['attribute']) && is_array($fine['chargeAdditionalData']['attribute'])) {
                foreach($fine['chargeAdditionalData']['attribute'] as $attribute) {
                    $charge_attributes[$attribute['key']] = $attribute['value'];
                }
            }
            $name = isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : $fine['payeeName'];
            $coords = isset($charge_attributes['offenseCoordinates']) && $charge_attributes['offenseCoordinates'] != 'NULL' ? explode(',', $charge_attributes['offenseCoordinates']) : [null,null];
            $address = isset($charge_attributes['OffensePlace']) ? ['text' => $charge_attributes['OffensePlace'], 'coords' => $coords] : null;             
            $record = array(
                'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                'number_doc'=> $fine['supplierBillID'],
                'date_doc'=> date('Y-m-d', strtotime($fine['billDate'])),
                'place'=> json_encode($address),
                'article'=> isset($charge_attributes['LegalAct']) ? $charge_attributes['LegalAct'] : null,
                'discharged_by'=> isset($charge_attributes['DepartmentName']) ? $charge_attributes['DepartmentName'] : $fine['wireUserName'],
                'wire_username' => $fine['wireUserName'],
                'name_of_payment'=> $fine['wirePaymentPurpose'],
                'kbk'=> $fine['wireKBK'],
                'inn'=> $fine['wireUserINN'],
                'kpp'=> $fine['wireKPP'],
                'bank'=> $fine['wireBankName'],
                'invoice'=> $fine['wireBankAccount'],
                'corr_invoice'=> $fine['wireBankKs'],
                'bik'=> $fine['wireBankBik'],
                'oktmo'=> $fine['wireOKTMO'],
                'sum'=> isset($payment_attributes['DiscountDate']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) > date('Y-m-d'))
                    ? $fine['totalAmount'] * 100/$payment_attributes['DiscountSize']
                    : $fine['totalAmount'],
                'discount_sum'=> $fine['totalAmount'],
                'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                'name'=> $name,
                'payer_identifier' => $payer_identifier
            );
            $payment = array(
                'value' => $fine['totalAmount'],
                'state' => 0
            );
            $record['payment'] = json_encode($payment);
            
            if(count($record)) {
                if(isset($record)) {
                    $data[] = $record;
                }
            }
            
        }
    
        return $data;
    }


    public static function paymentRequest(\App\Models\GibddFine $fine, $sum)
    {
        $client = new \GuzzleHttp\Client();

        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ],
                    "PayloadNamespace" => "http://www.moneta.ru/schemas/messages-chargesubscription.xsd"
                ],
                "Body" => [
                    "PaymentRequest" => [
                        "payer" => "70116321",
                        "payee" => "9172",
                        "amount" => $sum*0.1,
                        "operationInfo" => [
                            "attribute" => [
                                // [
                                //     "key" => "CUSTOMFIELD:PHONE",
                                //     "value" => "8 (999) 999-99-99"
                                // ],
                                // [
                                //     "key" => "WIREPAYER",
                                //     "value" => "Потемкин Денис Сергеевич"
                                // ],
                                [
                                    "key" => "WIREUSERINN",
                                    "value" => "1215192632"
                                ],
                                // [
                                //     "key" => "WIREKPP",
                                //     "value" => "770245001"
                                // ],
                                [
                                    "key" => "WIREBANKACCOUNT",
                                    "value" => '47422810408022539593'
                                ],
                                [
                                    "key" => "WIREBANKNAME",
                                    "value" => 'НКО "МОНЕТА" (ООО)'
                                ],
                                [
                                    "key" => "WIREBANKBIK",
                                    "value" => '048860734',
                                ],
                                [
                                    "key" => "WIREPAYMENTPURPOSE",
                                    "value" => 'Перевод денежных средств по Договору №007/2024-СРП от 27.08.2024. Для зачисления на счет 42619024. НДС не облагается.'
                                ],
                                [
                                    "key" => "WIREUSERNAME",
                                    "value" => 'НКО "МОНЕТА" (ООО)'
                                ],
                                // [
                                //     "key" => "WIREKBK",
                                //     "value" => $fine->kbk
                                // ],
                                // [
                                //     "key" => "WIREOKTMO",
                                //     "value" => $fine->oktmo
                                // ],
                                // [
                                //     "key" => "WIREPAYERIDENTIFIER",
                                //     "value" => $fine->payer_identifier
                                // ],
                                [
                                    "key" => "SOURCETARIFFMULTIPLIER",
                                    "value" => "50"
                                ]
                            ]
                        ],
                        "isPayerAmount" => false,
                        "paymentPassword" => "12345",
                        "version" => "VERSION_1"
                    ]
                ]
            ]
        ];
        //die();
        
        $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_data)
        ]);
        
        $body = $response->getBody()->getContents();
        $arr = json_decode($body, true);
        info('moneta request');
        info($arr);
        // if(!isset($arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']))
        //     return [];

        // $new_fines = $arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']['charge'];

        // $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();

        // return $new_fines;
    }

}

