<?php

namespace Modules\Gibdd\Entities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CrudService;
use Mail;
use App\Mail\FinesFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\ApnsConfig;



class Module
{
    public static function getSuccessPayments()
    {
        $payments = tenancy()->central(function () {
            return \DB::table('payments')->where(['status' => 'success'])->whereNotNull('number_doc')->pluck('number_doc')->toArray();
        });
        
        return $payments;
    }
    public static function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

    public static function addCameraToFines($fines)
    {
        $tenant = tenant('id');
        if(!$fines) {
            return;
        }
        $cameras = tenancy()->central(function () {
            $cameras = \DB::table('cameras')->select(['id', 'address', 'polygon', 'kurs'])->get();

            return $cameras;
        });
        
        $fines_array = [];

        foreach($fines as $fine) {
            $address = json_decode($fine->place ,true);
            if(!is_array($address) || !isset($address['coords'])) {
                continue;
            }
            $coords = $address['coords'];
            if(!is_array($coords) || count($coords) != 2 || !$coords[0]) {
                continue;
            }
            //print_r($coords);
            $min = 0;
            $c = null;
            foreach($cameras as $camera) {
                $address1 = json_decode($camera->address, true);
                $coordsTo = $address1['coords'];
                $distance = self::vincentyGreatCircleDistance($coords[0], $coords[1], $coordsTo[0], $coordsTo[1]);
                if(!$min || $min > $distance) {
                    $min = $distance;
                    $c = $camera;
                    $address['coords'] = [$coordsTo[0], $coordsTo[1]];
                }
                
            }
            // echo '<pre>';
            // print_r($address);
            // echo '</pre>';
            if($c) {
                //info('camera id '.$c->id);
                $polygon = json_decode($c->polygon);
                if(count($polygon) > 6) {
                    $polygon = array(
                        $polygon[0] != 'NULL' ? explode(':', $polygon[0]) : [null, null],
                        $polygon[1] != 'NULL' ? explode(':', $polygon[1]) : [null, null],
                        $polygon[2] != 'NULL' ? explode(':', $polygon[2]) : [null, null],
                        $polygon[4] != 'NULL' ? explode(':', $polygon[4]) : [null, null],
                        $polygon[5] != 'NULL' ? explode(':', $polygon[5]) : [null, null],
                        $polygon[6] != 'NULL' ? explode(':', $polygon[6]) : [null, null]
                    );
                    // echo '<pre>';
                    // print_r($polygon);
                    // echo '</pre>';
                    $address['camera_pos'] = $polygon;
                    $address['kurs'] = $c->kurs;
                    // echo 'new place<br>';
                    // echo '<pre>';
                    // print_r($address);
                    // echo '</pre>';
                    $fine->place = json_encode($address);
                    $fine->camera_id = $c->id;
                    $fine->saveQuietly();

                    $fine_array = $fine->getAttributes();
                    $fine_array['user_id'] = 1;
                    unset($fine_array['employee_id']);
                    unset($fine_array['car_id']);
                    unset($fine_array['company_id']);
                    unset($fine_array['deleted_at']);
                    unset($fine_array['address']);
                    // echo 'save place<br>';
                    // echo '<pre>';
                    // print_r($fine_array['place']);
                    // echo '</pre>';
                    $fine_array['place'] = json_decode($fine_array['place'], true);
                    //unset($fine_array['photo'])
                    $fine_array['photo'] = json_decode($fine_array['photo'], true);
                    $fine_array['icon'] = json_decode($fine_array['icon'], true);
                    $fines_array[] = $fine_array;
                }
                
            }
            //info('--------');
            
        }
        
        tenancy()->central(function () use ($fines_array, $tenant) {
            $crudService = new CrudService;

            $acc = \DB::table('accounts')->where('tenant_id', $tenant)->first();
            //$fines = $fines->toArray();
            foreach($fines_array as $k => $fine) {
                if(\DB::table('fines_gibdd')->where('inner_id', $fine['id'])->exists()) {
                    unset($fines_array[$k]);
                } else {
                    $fines_array[$k]['account_id'] = $acc->id;
                    $fines_array[$k]['inner_id'] = $fine['id'];
                    $fines_array[$k]['id'] = 0;
                    $fines_array[$k]['payment'] = $fines_array[$k]['payment'] ? json_decode($fines_array[$k]['payment'], true) : null;
                }
            }

            if(count($fines_array)) {
                $result = $crudService->batch('fines_gibdd', $fines_array);
            }
            
        });
        //echo $id.' - '.$camera->id.'<br>';
    }
    public static function getPriceKoef()
    {
        $tariff_prices = array(
            1 => 2.7,
            2 => 2.43,
            3 => 2.16,
            4 => 1.62
        );
        $t = \App\Models\Tariff::current();

        if($t)
            return $tariff_prices[$t['id']];
        else
            return 2.7;
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

    public static function findByNum($num)
    {
        $fine = \DB::table('fines_gibdd')->where('number_doc', $num)->first();
        if($fine) {
            return array(
                'code' => 200,
                'id' => $fine->id,
            );
        }
        $request = new Request;
        $request->merge(['num_post' => $num]);
        $data = self::checkByReq($request);

        if(!count($data))
            return array(
                'code' => 404,
                'error' => 'Штраф не найден',
            );
        foreach($data as $k => $record) {
            if(!$record['name'])
                continue;
            $fine = new \App\Models\GibddFine;
            $fine->payer_identifier = $record['payer_identifier'];
            $fine->wire_username = $record['wire_username'];
            if(isset($record['additional_payer_identifier']))
                $fine->additional_payer_identifier = $record['additional_payer_identifier'];
            $fine->saveQuietly();
            $history_text = 'Создана запись: '.$fine->id;
            $history = new \App\Models\History(['entity' => 'fines_gibdd', 'event' => 'OBJECT_CREATED', 'entity_id' => $fine->id, 'user_id' => 1, 'text' => $history_text]);
            $history->save();
            $fine->name = $record['name'];
            $data[$k]['id'] = $fine->id;
        }

        $local_module = \DB::table('modules')->where('slug', 'gibdd')->first();
        $data_m = json_decode($local_module->config, true);
        if(isset($data_m['fields'])) {
            $config_fields = collect($data_m['fields'])->keyBy('key')->toArray();
            if(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 1) {
                $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->pluck('id')->toArray();
            } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 2) {
                $user_id = $config_fields['user_id']['value']['value'][0];
                $distribution_users = array($user_id);
            } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 3) {
                $role_id = $config_fields['role_id']['value']['value'][0];
                $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->where('role_id', $role_id)->pluck('id')->toArray();
            }
        }
        $last_distribution = null;
        if(isset($data_m['last_distribution'])) {
            $last_distribution = $data_m['last_distribution'];
        }
        foreach($data as $record) {
            $record['user_id'] = null;
            unset($record['wire_username']);
            unset($record['payer_identifier']);
            if(isset($record['additional_payer_identifier'])) {
                if(strstr($record['additional_payer_identifier'], $sts_prefix)) {
                    $sts = str_replace($sts_prefix, '', $record['additional_payer_identifier']);
                    $arr = \DB::table('cars')->where('sts_number', $sts)->pluck('id');
                    if(count($arr)) {
                        $record['car_id'] = array_pop($arr);
                    } else {
                        $record['name'] = $record['name'].' СТС: '.$sts;
                    }
                }
                if(strstr($record['additional_payer_identifier'], $license_prefix)) {
                    $license = str_replace($license_prefix, '', $record['additional_payer_identifier']);
                    $arr = \DB::table('employees')->where('driver_license', $license)->pluck('id');
                    if(count($arr)) {
                        $record['employee_id'] = array_pop($arr);
                    } else {
                        $record['name'] = $record['name'].' Удостоверение: '.$license;
                    }
                }
                unset($record['additional_payer_identifier']);
            }
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
        //\App\Models\Settings::clear_cache();
        \App\Jobs\SettingsClearJob::dispatch();
        \App\Jobs\AddCameraJob::dispatch();

        // $fines = \App\Models\GibddFine::whereNull('camera_id')->get();
 
        // self::addCameraToFines($fines);

        if($fine) {
            return array(
                'code' => 200,
                'id' => $fine_id,
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
        
        if(isset($arr['Envelope']['Body']['AsyncResponse']['asyncStatus']) && $arr['Envelope']['Body']['AsyncResponse']['asyncStatus'] == 'INPROGRESS') {
            sleep(2);
            return self::getSmevFindCharges($async_id);
        };
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
        //info(json_encode($request_data));
        try {
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
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // $response = $e->getResponse();
            // $responseBodyAsString = $response->getBody()->getContents();
            // info('Exception');
            // info($responseBodyAsString);

            return;
        }
        
        
        
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
        $data = array();

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
        while($has_more) {
            try {
                $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($request_data)
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return 0;
            }
            
            
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
        // echo '<pre>';
        // print_r($new_fines);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($cars);
        // echo '</pre>';
        $fines_need_smev = array();
        foreach($new_fines as $bill_id => $fine) {
            if($fine['receivingMethod'] == 'N') {
                $fines_need_smev[] = $bill_id;
            }
        }

        if(count($fines_need_smev)) {
            $async_id = self::sendSmevFindCharges($fines_need_smev);
            sleep(3);
            if($async_id) {
                $fines_need_smev = self::getSmevFindCharges($async_id);
                foreach($fines_need_smev as $bill_id => $fine) {
                    $new_fines[$bill_id] = $fine;
                }
            }
        }
        //info($new_fines);

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
            if(!isset($fine['amountToPay']) || $fine['amountToPay'] <= 0)
                continue;
            $record = array();
            $payer_identifier = isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];//isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];

            //18810550240948636303

            if(!\App\Models\GibddFine::where('number_doc', $fine['supplierBillID'])->exists()) {
                info($fine['supplierBillID']);
                // $text = mb_strtolower($fine['koap_text']);
                // $firstChar = mb_substr($text, 0, 1);
                // $then = mb_substr($text, 1, null);
                // $name = mb_strtoupper($firstChar) . $then;
                
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
                //echo $payer_identifier.'<br>';
                if(isset($cars[$payer_identifier])) {
                    $current_car = $cars[$payer_identifier];
                    $date_check = date('d.m.Y', strtotime($current_car->date_check));
                    $current_car->date_check = \DB::raw('now()');
                    if($current_car->company_id) {
                        $company_ids[] = $current_car->company_id;
                    }
                    
                    $discount_sum = $fine['discountAmount'] ? $fine['discountAmount'] : $fine['totalAmount'];
                    if($discount_sum < 0)
                        continue;
                    if(isset($payment_attributes['DiscountDate']) && isset($payment_attributes['DiscountSize']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) >= date('Y-m-d')))
                        $discount_sum = $fine['totalAmount'] * $payment_attributes['DiscountSize'] / 100;
                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_car->company_id,
                        'car_id'=> $current_car->id,
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
                        'sum'=> $fine['totalAmount'],
                        'discount_sum'=> $discount_sum,
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $discount_sum,
                        'state' => 0
                    );
                    $record['payment'] = json_encode($payment);
                } elseif(isset($employees[$payer_identifier])) {
                    $current_employee = $employees[$payer_identifier];
                    $date_check = date('d.m.Y', strtotime($current_employee->date_check));
                    $current_employee->date_check = \DB::raw('now()');
                    if($current_employee->company_id) {
                        $company_ids[] = $current_employee->company_id;
                    }
                    $discount_sum = $fine['discountAmount'] ? $fine['discountAmount'] : $fine['totalAmount'];
                    if($discount_sum < 0)
                        continue;
                    if(isset($payment_attributes['DiscountDate']) && isset($payment_attributes['DiscountSize']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) >= date('Y-m-d')))
                        $discount_sum = $fine['totalAmount'] * $payment_attributes['DiscountSize'] / 100;
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
                        'sum'=> $fine['totalAmount'],
                        'discount_sum'=> $discount_sum,
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $discount_sum,
                        'state' => 0
                    );
                    $record['payment'] = json_encode($payment);
                } elseif(isset($companies[$payer_identifier]) || isset($fine['payerIdentifier']) && isset($companies[$fine['payerIdentifier']])) {

                    if(!isset($companies[$payer_identifier]))
                        $payer_identifier = $fine['payerIdentifier'];
                    $current_company = $companies[$payer_identifier];
                    //$date_check = date('d.m.Y', strtotime($current_company->date_check));
                    //$current_employee->date_check = \DB::raw('now()');
                    //if($current_employee->company_id) {
                        $company_ids[] = $current_company->id;
                    //}
                    $discount_sum = $fine['discountAmount'] ? $fine['discountAmount'] : $fine['totalAmount'];
                    if($discount_sum < 0)
                        continue;
                    if(isset($payment_attributes['DiscountDate']) && isset($payment_attributes['DiscountSize']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) >= date('Y-m-d')))
                        $discount_sum = $fine['totalAmount'] * $payment_attributes['DiscountSize'] / 100;
                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_company->id,
                        //'employee_id'=> $current_employee->id,
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
                        'sum'=> $fine['totalAmount'],
                        'discount_sum'=> $discount_sum,
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier,
                        'additional_payer_identifier' => isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : null// => '1240000000009944850346',
                    );
                    $payment = array(
                        'value' => $discount_sum,
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
                        $file = null;
                        if(strstr($fine['wireUserName'], 'АМПП')) {
                            $file = 'ampp.png';
                        } elseif(strstr($fine['wireUserName'], 'МАДИ')) {
                            $file = 'madi.png';
                        } elseif(strstr($fine['wireUserName'], 'УФССП')) {
                            $file = 'fspp.png';
                        } elseif(strstr($fine['wireUserName'], 'ОСФР')) {
                            $file = 'pfr.png';
                        } elseif(strstr($fine['wireUserName'], 'УФК') || strstr($fine['wireUserName'], 'МВД')) {
                            $file = 'gbdd.png';
                        }
                        

                        if($file) {
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
                        }

                        $data[] = $record;
                    }
                }
            } else {
                $f = \App\Models\GibddFine::where('number_doc', $fine['supplierBillID'])->first();
                $f->wire_username = $fine['wireUserName'];
                $f->saveQuietly();
                $unpayed_fines[] = $fine['supplierBillID'];
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

            foreach($companies as $id => $company) {
                if(!in_array($id, $company_ids)) {
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
            
            foreach($data as $k => $record) {
                if(!$record['name'])
                    continue;
                info('new fie');
                info($record);
                $fine = new \App\Models\GibddFine;
                $fine->payer_identifier = $record['payer_identifier'];
                $fine->wire_username = $record['wire_username'];
                if(isset($record['additional_payer_identifier']))
                    $fine->additional_payer_identifier = $record['additional_payer_identifier'];
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
            // if($data_m['fields'][3]['value'] == 1) {
            //     $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->pluck('id')->toArray();
            // } elseif($data_m['fields'][3]['value'] == 2) {
            //     $user_id = $data_m['fields'][4]['value']['value'][0];
            //     $distribution_users = array($user_id);
            // } elseif($data_m['fields'][3]['value'] == 3) {
            //     $role_id = $data_m['fields'][5]['value']['value'][0];
            //     $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->where('role_id', $role_id)->pluck('id')->toArray();
            // }
            if(isset($data_m['fields'])) {
                $config_fields = collect($data_m['fields'])->keyBy('key')->toArray();
                if(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 1) {
                    $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->pluck('id')->toArray();
                } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 2) {
                    $user_id = $config_fields['user_id']['value']['value'][0];
                    $distribution_users = array($user_id);
                } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 3) {
                    $role_id = $config_fields['role_id']['value']['value'][0];
                    $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->where('role_id', $role_id)->pluck('id')->toArray();
                }
            }
            $last_distribution = null;
            if(isset($data_m['last_distribution'])) {
                $last_distribution = $data_m['last_distribution'];
            }
            foreach($data as $record) {
                $record['user_id'] = null;
                unset($record['wire_username']);
                unset($record['payer_identifier']);
                //info('SAVE RECO');
                if(isset($record['additional_payer_identifier'])) {
                    //info('additions '.$record['additional_payer_identifier']);
                    if(strstr($record['additional_payer_identifier'], $sts_prefix)) {
                        //info('machina car');
                        $sts = str_replace($sts_prefix, '', $record['additional_payer_identifier']);
                        //info($sts);
                        $arr = \DB::table('cars')->where('sts_number', $sts)->pluck('id');
                        //info($arr);
                        if(count($arr)) {
                            info('car_id '.array_pop($arr));
                            $record['car_id'] = array_pop($arr);
                        } else {
                            $record['name'] = $record['name'].' СТС: '.$sts;
                        }
                    }
                    if(strstr($record['additional_payer_identifier'], $license_prefix)) {
                        $license = str_replace($license_prefix, '', $record['additional_payer_identifier']);
                        $arr = \DB::table('employees')->where('driver_license', $license)->pluck('id');
                        if(count($arr)) {
                            $record['employee_id'] = array_pop($arr);
                        } else {
                            $record['name'] = $record['name'].' Удостоверение: '.$license;
                        }
                    }
                    unset($record['additional_payer_identifier']);
                }
                if(!isset($distribution_users)) {
                    $distribution_users = [1];
                }
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
            foreach($data_m['fields'] as $module_field) {
                if($module_field['key'] == 'email' && $module_field['value']) {
                    Mail::to($module_field['value'])->send(new FinesFound(
                        account: "https://$tenant.compas.pro",
                        email: $module_field['value'],
                        fines: $data
                    ));
                }
            }
            //\App\Models\Settings::clear_cache();    
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
        foreach($fines as $fine) {
            $k = array_search($fine['number_doc'], $unpayed_fines);
            if($k === false) {
                // $payment = array(
                //     'value' => $fine['totalAmount'],
                //     'state' => 0
                // );
                // $record['payment'] = json_encode($payment);
                //получить постановления из смев
                //\DB::table('fines_gibdd')->where('id', $fine['id'])->whereNotNull('payment')->whereJsonContains('payment->state', 0)->update(['payment' => null]);
            }
        }

        // $fines = \App\Models\GibddFine::get();
 
        // self::addCameraToFines($fines);
        \App\Jobs\SettingsClearJob::dispatch();
        \App\Jobs\AddCameraJob::dispatch();
        \App\Jobs\CheckPayedFinesJob::dispatch();
        //self::checkPayedFines();

        $total = count($data);

        if($total) {
            $notification = [
                'title' => 'Новые штрафы - '.tenant('id'),
                'body' => 'Найдено новых штрафов - '.$total.'.',
            ];
        } else {
            $notification = [
                'title' => 'Новых штрафов нет - '.tenant('id'),
                'body' => 'Новых штрафов не обнаружено.',
            ];
        }

        $messaging = app('firebase.messaging');
        $topic = 'all';

        $factory = (new Factory)->withServiceAccount('/home/admin/web/compas.pro/public_html/firebase.json');
        $messaging = $factory->createMessaging();
        $message = CloudMessage::fromArray([
            'topic' => $topic,
            'notification'  => $notification,
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => $notification,
                        'badge' => 1,
                        'sound' => 'default',
                    ],
                ],
            ]
        ]);

        $tokens = \App\Models\User::whereNotNull('token')->pluck('token')->toArray();
        $deviceTokens = [];
        foreach($tokens as $token) {
            $deviceTokens = array_merge($deviceTokens, json_decode($token, true));
        };
        $deviceTokens = array_unique($deviceTokens);
        try {
            // info('$deviceTokens');
            // info($deviceTokens);
            $sendReport = $messaging->sendMulticast($message, $deviceTokens);
        } catch (MessagingException $e) {
        }

        return $total;

    }

    public static function findAutodor()
    {
        $settings = app('settings');
        $num_prefix = '1150000000RUS';
        
        $client = new \GuzzleHttp\Client();
        $tenant = tenant('id');
        $current_date = date('d.m.Y');
        $page = 1;
        $data = array();

        $objects_car = \App\Models\Car::whereNotNull('number')->get()->keyBy('number');
        $fines = \App\Models\Autodor::get()->keyBy('number_doc')->toArray();

        $cars = $num_array = [];
        foreach($objects_car as $num => $car) {
            $val = json_decode($num, true);
            if(is_array($val)) {
                $val = $val['value'];
            }
            if($val) {
                $val = $num_prefix.$val;
                //if(strlen($val) == 22) {
                    $cars[$val] = $car;
                    array_push($num_array, $val);
                //}
            }
        }

        $page = 1;
        $has_more = true;
        $new_fines = [];
        $payerIdentifiers = array_merge($num_array);
        echo '<pre>';
        print_r($payerIdentifiers);
        echo '</pre>';
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
        while($has_more) {
            try {
                $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($request_data)
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
              $responseBodyAsString = $response->getBody()->getContents();
              $res = json_decode($responseBodyAsString, true);
              echo '<pre>';
            print_r($res);
            echo '</pre>';
                echo 222;
                return 0;
            }
            
            
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            echo '<pre>';
            print_r($arr);
            echo '</pre>';
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
        // echo '<pre>';
        // print_r($new_fines);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($cars);
        // echo '</pre>';
        $fines_need_smev = array();
        foreach($new_fines as $bill_id => $fine) {
            if($fine['receivingMethod'] == 'N') {
                $fines_need_smev[] = $bill_id;
            }
        }

        if(count($fines_need_smev)) {
            $async_id = self::sendSmevFindCharges($fines_need_smev);
            sleep(3);
            if($async_id) {
                $fines_need_smev = self::getSmevFindCharges($async_id);
                foreach($fines_need_smev as $bill_id => $fine) {
                    $new_fines[$bill_id] = $fine;
                }
            }
        }

        $unpayed_fines = array();
        $company_ids = array();
        foreach($new_fines as $fine) {
            if(!isset($fine['amountToPay']) || $fine['amountToPay'] <= 0)
                continue;
            $record = array();
            $payer_identifier = isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];//isset($fine['additionalPayerIdentifier']) ? $fine['additionalPayerIdentifier'] : $fine['payerIdentifier'];

            //18810550240948636303

            if(!\App\Models\Autodor::where('number_doc', $fine['supplierBillID'])->exists()) {
                info($fine['supplierBillID']);
                // $text = mb_strtolower($fine['koap_text']);
                // $firstChar = mb_substr($text, 0, 1);
                // $then = mb_substr($text, 1, null);
                // $name = mb_strtoupper($firstChar) . $then;
                
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
                //echo $payer_identifier.'<br>';
                if(isset($cars[$payer_identifier])) {
                    $current_car = $cars[$payer_identifier];
                    $date_check = date('d.m.Y', strtotime($current_car->date_check));
                    $current_car->date_check = \DB::raw('now()');
                    if($current_car->company_id) {
                        $company_ids[] = $current_car->company_id;
                    }
                    
                    $discount_sum = $fine['discountAmount'] ? $fine['discountAmount'] : $fine['totalAmount'];
                    if($discount_sum < 0)
                        continue;
                    if(isset($payment_attributes['DiscountDate']) && isset($payment_attributes['DiscountSize']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) >= date('Y-m-d')))
                        $discount_sum = $fine['totalAmount'] * $payment_attributes['DiscountSize'] / 100;
                    $record = array(
                        'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                        'company_id'=> $current_car->company_id,
                        'car_id'=> $current_car->id,
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
                        'sum'=> $fine['totalAmount'],
                        'discount_sum'=> $discount_sum,
                        'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                        'name'=> $name,
                        'payer_identifier' => $payer_identifier
                    );
                    $payment = array(
                        'value' => $discount_sum,
                        'state' => 0
                    );
                    $record['payment'] = json_encode($payment);
                }
                if(count($record)) {
                    if(isset($record)) {
                        $file = null;
                        if(strstr($fine['wireUserName'], 'АМПП')) {
                            $file = 'ampp.png';
                        } elseif(strstr($fine['wireUserName'], 'МАДИ')) {
                            $file = 'madi.png';
                        } elseif(strstr($fine['wireUserName'], 'УФССП')) {
                            $file = 'fspp.png';
                        } elseif(strstr($fine['wireUserName'], 'ОСФР')) {
                            $file = 'pfr.png';
                        } elseif(strstr($fine['wireUserName'], 'УФК') || strstr($fine['wireUserName'], 'МВД')) {
                            $file = 'gbdd.png';
                        }
                        

                        if($file) {
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
                        }

                        $data[] = $record;
                    }
                }
            } else {
                $f = \App\Models\Autodor::where('number_doc', $fine['supplierBillID'])->first();
                $f->wire_username = $fine['wireUserName'];
                $f->saveQuietly();
                $unpayed_fines[] = $fine['supplierBillID'];
            }
            
        }
        $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('number')->get()->keyBy('id');
        $employees = \App\Models\Employee::whereNotNull('driver_license')->get()->keyBy('id');
        $companies = \App\Models\Company::whereNotNull('inn')->get()->keyBy('id');
        $field_car = \DB::table('data_rows')->find(2222);
        $field_driver = \DB::table('data_rows')->find(2431);
        $field_company = \DB::table('data_rows')->find(2491);


        
        if(isset($data) && count($data)) {
            $new_fines_cars = $new_fines_drivers = $new_fines_companies = array();

            foreach($companies as $id => $company) {
                if(!in_array($id, $company_ids)) {
                    $history_text = 'Новых начислений не найдено';
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
            
            foreach($data as $k => $record) {
                if(!$record['name'])
                    continue;
                info('new fie');
                info($record);
                $fine = new \App\Models\Autodor;
                $fine->payer_identifier = $record['payer_identifier'];
                $fine->wire_username = $record['wire_username'];
                if(isset($record['additional_payer_identifier']))
                    $fine->additional_payer_identifier = $record['additional_payer_identifier'];
                $fine->saveQuietly();
                $history_text = 'Создана запись: '.$fine->id;
                $history = new \App\Models\History(['entity' => 'autodor', 'event' => 'OBJECT_CREATED', 'entity_id' => $fine->id, 'user_id' => 1, 'text' => $history_text]);
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
                $old_fines = $car->autodor->pluck('id')->toArray();
                if(isset($new_fines_cars[$id])) {
                    $new = array();
                    $new_values = array();
                    foreach($new_fines_cars[$id] as $fine) {
                        $new[] = "<span data-slug='autodor' data-id='$fine->id'>$fine->name</span>";
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
                    $history_text = 'Новых начислений не найдено';
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
            if(count($company_ids)) {
                $companies = \App\Models\Company::whereIntegerInRaw('id', $company_ids)->get()->keyBy('id');
                foreach($companies as $id => $company) {
                    $old_fines = $company->autodor->pluck('id')->toArray();
                    if(isset($new_fines_companies[$id])) {
                        $new = array();
                        $new_values = array();
                        foreach($new_fines_companies[$id] as $fine) {
                            $new[] = "<span data-slug='autodor' data-id='$fine->id'>$fine->name</span>";
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
            // if($data_m['fields'][3]['value'] == 1) {
            //     $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->pluck('id')->toArray();
            // } elseif($data_m['fields'][3]['value'] == 2) {
            //     $user_id = $data_m['fields'][4]['value']['value'][0];
            //     $distribution_users = array($user_id);
            // } elseif($data_m['fields'][3]['value'] == 3) {
            //     $role_id = $data_m['fields'][5]['value']['value'][0];
            //     $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->where('role_id', $role_id)->pluck('id')->toArray();
            // }
            if(isset($data_m['fields'])) {
                $config_fields = collect($data_m['fields'])->keyBy('key')->toArray();
                if(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 1) {
                    $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->pluck('id')->toArray();
                } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 2) {
                    $user_id = $config_fields['user_id']['value']['value'][0];
                    $distribution_users = array($user_id);
                } elseif(isset($config_fields['fines_distribution']) && $config_fields['fines_distribution']['value'] == 3) {
                    $role_id = $config_fields['role_id']['value']['value'][0];
                    $distribution_users = \DB::table('users')->select('id')->whereNull('deleted_at')->where('role_id', $role_id)->pluck('id')->toArray();
                }
            }
            $last_distribution = null;
            if(isset($data_m['last_distribution'])) {
                $last_distribution = $data_m['last_distribution'];
            }
            foreach($data as $record) {
                $record['user_id'] = null;
                unset($record['wire_username']);
                unset($record['payer_identifier']);
                //info('SAVE RECO');
                if(isset($record['additional_payer_identifier'])) {
                    //info('additions '.$record['additional_payer_identifier']);
                    if(strstr($record['additional_payer_identifier'], $sts_prefix)) {
                        //info('machina car');
                        $sts = str_replace($sts_prefix, '', $record['additional_payer_identifier']);
                        //info($sts);
                        $arr = \DB::table('cars')->where('sts_number', $sts)->pluck('id');
                        //info($arr);
                        if(count($arr)) {
                            info('car_id '.array_pop($arr));
                            $record['car_id'] = array_pop($arr);
                        } else {
                            $record['name'] = $record['name'].' СТС: '.$sts;
                        }
                    }
                    if(strstr($record['additional_payer_identifier'], $license_prefix)) {
                        $license = str_replace($license_prefix, '', $record['additional_payer_identifier']);
                        $arr = \DB::table('employees')->where('driver_license', $license)->pluck('id');
                        if(count($arr)) {
                            $record['employee_id'] = array_pop($arr);
                        } else {
                            $record['name'] = $record['name'].' Удостоверение: '.$license;
                        }
                    }
                    unset($record['additional_payer_identifier']);
                }
                if(!isset($distribution_users)) {
                    $distribution_users = [1];
                }
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
                \App\Models\History::saveForObject('autodor', array($record));
                $fine_id = $record['id'];
                unset($record['id']);
                \DB::table('autodor')->where('id', $fine_id)->update($record);
                
                
                
            };
            foreach($data_m['fields'] as $module_field) {
                if($module_field['key'] == 'email' && $module_field['value']) {
                    Mail::to($module_field['value'])->send(new FinesFound(
                        account: "https://$tenant.compas.pro",
                        email: $module_field['value'],
                        fines: $data
                    ));
                }
            }
            //\App\Models\Settings::clear_cache();    
        } else {
            foreach($cars as $id => $car) {
                $history_text = 'Новых начислений не найдено';
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

            

            foreach($companies as $id => $company) {
                $history_text = 'Новых начислений не найдено';
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
        foreach($fines as $fine) {
            $k = array_search($fine['number_doc'], $unpayed_fines);
            if($k === false) {
            }
        }

        // $fines = \App\Models\GibddFine::get();
 
        // self::addCameraToFines($fines);
        \App\Jobs\SettingsClearJob::dispatch();
        //\App\Jobs\AddCameraJob::dispatch();
        \App\Jobs\CheckPayedAutodorJob::dispatch();
        //self::checkPayedFines();

        $total = count($data);

        if($total) {
            $notification = [
                'title' => 'Новые начисления Автодор - '.tenant('id'),
                'body' => 'Найдено новых начислений - '.$total.'.',
            ];
        } else {
            $notification = [
                'title' => 'Новых начислений Автодор нет - '.tenant('id'),
                'body' => 'Новых начислений не обнаружено.',
            ];
        }

        $messaging = app('firebase.messaging');
        $topic = 'all';

        $factory = (new Factory)->withServiceAccount('/home/admin/web/compas.pro/public_html/firebase.json');
        $messaging = $factory->createMessaging();
        $message = CloudMessage::fromArray([
            'topic' => $topic,
            'notification'  => $notification,
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => $notification,
                        'badge' => 1,
                        'sound' => 'default',
                    ],
                ],
            ]
        ]);

        $tokens = \App\Models\User::whereNotNull('token')->pluck('token')->toArray();
        $deviceTokens = [];
        foreach($tokens as $token) {
            $deviceTokens = array_merge($deviceTokens, json_decode($token, true));
        };
        $deviceTokens = array_unique($deviceTokens);
        try {
            // info('$deviceTokens');
            // info($deviceTokens);
            $sendReport = $messaging->sendMulticast($message, $deviceTokens);
        } catch (MessagingException $e) {
        }

        return $total;

    }

    public static function checkPayedFines()
    {
        $unpayed_fines = \App\Models\GibddFine::whereNotNull('payment')->whereJsonContains('payment->state', 0)->pluck('id', 'number_doc')->toArray();
        // echo '<pre>';
        // print_r(array_keys($unpayed_fines));
        // echo '</pre>';
        $async_id = self::sendSmevFindCharges(array_keys($unpayed_fines));
        sleep(5);
        if($async_id) {
            //echo $async_id.'<br>';
            $fines_need_smev = self::getSmevFindCharges($async_id);
            // echo '<pre>';
            // print_r($fines_need_smev);
            // echo '</pre>';
            foreach($unpayed_fines as $bill_id => $fine_id) {
                if(isset($fines_need_smev[$bill_id])) {
                    //echo 'amount '.$fines_need_smev[$bill_id]['amountToPay'].'<br>';
                    if($fines_need_smev[$bill_id]['amountToPay']) {
                        //echo 'drop '.$bill_id.'<br>';
                        unset($unpayed_fines[$bill_id]);
                    }
                    
                }
            }
            
            foreach($unpayed_fines as $fine) {
               \DB::table('fines_gibdd')->where('id', $fine)->whereNotNull('payment')->whereJsonContains('payment->state', 0)->update(['payment' => null]);
            }
        }
        
    }

    public static function checkPayedAutodor()
    {
        $unpayed_fines = \App\Models\Autodor::whereNotNull('payment')->whereJsonContains('payment->state', 0)->pluck('id', 'number_doc')->toArray();

        $async_id = self::sendSmevFindCharges(array_keys($unpayed_fines));
        sleep(5);
        if($async_id) {
            //echo $async_id.'<br>';
            $fines_need_smev = self::getSmevFindCharges($async_id);
            // echo '<pre>';
            // print_r($fines_need_smev);
            // echo '</pre>';
            foreach($unpayed_fines as $bill_id => $fine_id) {
                if(isset($fines_need_smev[$bill_id])) {
                    //echo 'amount '.$fines_need_smev[$bill_id]['amountToPay'].'<br>';
                    if($fines_need_smev[$bill_id]['amountToPay']) {
                        //echo 'drop '.$bill_id.'<br>';
                        unset($unpayed_fines[$bill_id]);
                    }
                    
                }
            }
            
            foreach($unpayed_fines as $fine) {
               \DB::table('autodor')->where('id', $fine)->whereNotNull('payment')->whereJsonContains('payment->state', 0)->update(['payment' => null]);
            }
        }
        
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
            $val = $license_prefix.$request->driver_license;
            array_push($payerIdentifiers, $val);
        }
        if($request->inn && $request->kpp) {
            $val = $inn_prefix.$request->inn.$request->kpp;
            array_push($payerIdentifiers, $val);
        }
        

        if($request->num_post) {
            // $request_data = [
            //     "Envelope" => [
            //         "Header" => [
            //             "Security" => [
            //                 "UsernameToken" => [
            //                     "Username" => 'api@compas.pro',//env('MONETA_USER'),
            //                     "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
            //                 ]
            //             ]
            //         ],
            //         "Body" => [
            //             "GetChargeRequest" => [
            //                 "supplierBillID" => $request->num_post
            //             ]
            //         ]
            //     ]
            // ];
            // info('postan');
            // try {
            //     $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
            //         'headers' => [
            //             'Content-Type' => 'application/json',
            //         ],
            //         'body' => json_encode($request_data)
            //     ]);
            // } catch (\GuzzleHttp\Exception\ClientException $e) {
            //     return [];
            // }
            
            // $body = $response->getBody()->getContents();
            // $arr = json_decode($body, true);
            // // if(!isset($arr['Envelope']['Body']['GetChargeResponse']))
            // //     return;
            // if(isset($arr['Envelope']['Body']['GetChargeResponse'])) {
            //     $new_fines[] = $arr['Envelope']['Body']['GetChargeResponse'];
            // }

            $async_id = self::sendSmevFindCharges([$request->num_post]);

            sleep(5);
            if($async_id) {
                //echo $async_id.'<br>';
                $fines_need_smev = self::getSmevFindCharges($async_id);
                // echo '<pre>';
                // print_r($fines_need_smev);
                // echo '</pre>';
                if(isset($fines_need_smev[$request->num_post])) {
                    $new_fines[] = $fines_need_smev[$request->num_post];
                }
            } else {
                return [
                    'error' => array(
                        'message' => 'Неверный формат данных',
                        'code' => 400
                    )
                ];
            }
            if(!count($new_fines)){
                return [];
            }
        } else {
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
                try {
                    $response = $client->request('POST', 'https://service.moneta.ru:51443/services', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => json_encode($request_data)
                    ]);
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    return [
                        'error' => array(
                            'message' => 'Неверный формат данных',
                            'code' => 400
                        )
                    ];
                }
                
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
        }
        
        
        $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();
        $fines_need_smev = array();
        foreach($new_fines as $bill_id => $fine) {
            if(isset($fine['receivingMethod']) && $fine['receivingMethod'] == 'N') {
                $fines_need_smev[] = $bill_id;
            }
        }

        if(count($fines_need_smev)) {
            $async_id = self::sendSmevFindCharges($fines_need_smev);
            sleep(3);
            if($async_id) {
                $fines_need_smev = self::getSmevFindCharges($async_id);
                foreach($fines_need_smev as $bill_id => $fine) {
                    $new_fines[$bill_id] = $fine;
                }
            } else {
                return [
                    'error' => array(
                        'message' => 'Неверный формат данных',
                        'code' => 400
                    )
                ];
            }
        }

        $success_payments = self::getSuccessPayments();
        foreach($new_fines as $fine) {
            if(!isset($fine['amountToPay']) || $fine['amountToPay'] <= 0)
                continue;
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
            $discount_sum = $fine['discountAmount'] ? $fine['discountAmount'] : $fine['totalAmount'];
            if($discount_sum < 0)
                continue;
            if(isset($payment_attributes['DiscountDate']) && isset($payment_attributes['DiscountSize']) && (date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) >= date('Y-m-d')))
                $discount_sum = $fine['totalAmount'] * $payment_attributes['DiscountSize'] / 100;
            $record = array(
                'date'=> isset($charge_attributes['OffenseDate']) ? date('Y-m-d', strtotime($charge_attributes['OffenseDate'])) : null,
                'number_doc'=> $fine['supplierBillID'],
                'date_doc'=> date('Y-m-d', strtotime($fine['billDate'])),
                'place'=> $address,
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
                'sum'=> $fine['totalAmount'],
                'discount_sum'=> $discount_sum,
                'sale_finish'=> isset($payment_attributes['DiscountDate']) ? date('Y-m-d', strtotime($payment_attributes['DiscountDate'])) : null,
                'name'=> $name,
                'payer_identifier' => $payer_identifier
            );
            $file = null;
            if(strstr($fine['wireUserName'], 'АМПП')) {
                $file = 'ampp.png';
            } elseif(strstr($fine['wireUserName'], 'МАДИ')) {
                $file = 'madi.png';
            } elseif(strstr($fine['wireUserName'], 'УФССП')) {
                $file = 'fspp.png';
            } elseif(strstr($fine['wireUserName'], 'ОСФР')) {
                $file = 'pfr.png';
            } elseif(strstr($fine['wireUserName'], 'УФК') || strstr($fine['wireUserName'], 'МВД')) {
                $file = 'gbdd.png';
            }
            if($file) {
                $icons = array();
                $icons[] = array(
                    'id' => 0,
                    'name' => $file,
                    'url' => 'https://compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                    'file' => 'https://compas.pro/public/assets/modules/Gibdd/icons/'.$file,
                    'extension' => 'png',
                    'sort' => 0,
                );
                $record['icon'] = $icons;
            }
            $total = $discount_sum;
            $payment = array(
                'value' => ceil($total / (100 - 2.7) * 100),//self::getPriceKoef()),
                'state' => in_array($record['number_doc'], $success_payments) ? 1 : 0
            );
            $record['payment'] = $payment;
            
            if(count($record)) {
                if(isset($record) && !in_array($record['number_doc'], $success_payments)) {
                    $data[] = $record;
                }
            }
           
        }

        // info('checkByReq');
        // info($data);

        return $data;
    }


    public static function paymentRequest($payment, $fine, $comission)
    {
        $client = new \GuzzleHttp\Client();
        return false;
        if($fine->sale_finish >= date('Y-m-d'))
            $sum = $fine->discount_sum;
        else
            $sum = $fine->sum;
        $comission = $payment->amount - $sum;
        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ],
                    //"PayloadNamespace" => "http://www.moneta.ru/schemas/messages-chargesubscription.xsd"
                ],
                "Body" => [
                    "AsyncRequest" => [
                        "PaymentRequest" => [
                            "payer" => "70116321",
                            "payee" => "9172",
                            "amount" => $sum,
                            "clientTransaction" => $payment->transaction_id,
                            //"parentId" => $payment->transaction_id,
                            "operationInfo" => [
                                "attribute" => [
                                    [
                                        "key" => "PARENTID",
                                        "value" => $payment->operation_id
                                    ],
                                    [
                                        "key" => "WIREPAYER",
                                        "value" => "Потемкин Денис Сергеевич"
                                    ],
                                    // [
                                    //     "key" => "WIREDOCINDEX",
                                    //     "value" => $fine->number_doc
                                    // ],
                                    [
                                        "key" => "CUSTOMFIELD:105",
                                        "value" => $fine->number_doc
                                    ],
                                    [
                                        "key" => "CUSTOMFIELD:EMAIL",
                                        "value" => "reg@compas.pro"
                                    ],
                                    [
                                        "key" => "WIREUSERINN",
                                        "value" => $fine->inn
                                    ],
                                    [
                                        "key" => "WIREKPP",
                                        "value" => $fine->kpp
                                    ],
                                    [
                                        "key" => "WIREBANKACCOUNT",
                                        "value" => $fine->invoice
                                    ],
                                    [
                                        "key" => "WIREBANKNAME",
                                        "value" => $fine->bank
                                    ],
                                    [
                                        "key" => "WIREBANKBIK",
                                        "value" => $fine->bik
                                    ],
                                    [
                                        "key" => "WIREPAYMENTPURPOSE",
                                        "value" => $fine->name_of_payment
                                    ],
                                    [
                                        "key" => "WIREUSERNAME",
                                        "value" => $fine->wire_username
                                    ],
                                    [
                                        "key" => "WIREKBK",
                                        "value" => $fine->kbk
                                    ],
                                    [
                                        "key" => "WIREOKTMO",
                                        "value" => $fine->oktmo
                                    ],
                                    [
                                        "key" => "WIREPAYERIDENTIFIER",
                                        "value" => $fine->payer_identifier
                                    ],
                                    [
                                        "key" => "SOURCETARIFFMULTIPLIER",
                                        "value" => round($comission,2)
                                    ]
                                ]
                            ],
                            "isPayerAmount" => false,
                            "paymentPassword" => "12345",
                            "version" => "VERSION_2"
                        ]
                    ]
                ]
            ]
        ];
        //die();
        info(json_encode($request_data, JSON_UNESCAPED_UNICODE));
        // die();

        $response = $client->request('POST', 'https://service.moneta.ru:56443/services', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_data)
        ]);
        
        $body = $response->getBody()->getContents();
        $arr = json_decode($body, true);
        if(isset($arr['Envelope']['Body']['AsyncResponse']['asyncId'])) {
            $async_id = $arr['Envelope']['Body']['AsyncResponse']['asyncId'];
            tenancy()->central(function () use ($payment, $async_id) {
                \DB::table('payments')->where('id', $payment->id)->update(['async_id' => $async_id]);
            });
        }
        
        return $arr;
        // if(!isset($arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']))
        //     return [];

        // $new_fines = $arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']['charge'];

        // $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();

        // return $new_fines;
    }

    public static function paymentRequestPhoto(\App\Models\GibddFine $fine)
    {
        $client = new \GuzzleHttp\Client();
        $attribute = [
            [
                "key" => "CUSTOMFIELD:BILL_ID",
                "value" => $fine->number_doc
            ],
            [
                "key" => "SUBPROVIDERID",
                "value" => "1"
            ]
        ];
        $provider_id = 9176;
        if(strstr($fine->icon, 'ampp.png') || strstr($fine->icon, 'madi.png')) {
            $provider_id = 9175;
        }


        if($fine->car) {
            $number = json_decode($fine->car->number, true);
            if(is_array($number))
                $number = $number['value'];
            else
                $number = $fine->car->number;
            $attribute[] = [
                "key" => "CUSTOMFIELD:GRZ",
                "value" => $number
            ];
        }
        
        $request_data = [
            "Envelope" => [
                "Header" => [
                    "Security" => [
                        "UsernameToken" => [
                            "Username" => 'api@compas.pro',//env('MONETA_USER'),
                            "Password" => 'mophaH-kupfan-9cygqi'//env('MONETA_PASSWORD')
                        ]
                    ],
                    //"PayloadNamespace" => "http://www.moneta.ru/schemas/messages.xsd"
                ],
                "Body" => [
                    "PaymentRequest" => [
                        "payer" => "42619024",
                        "payee" => $provider_id,
                        "amount" => "1.5",
                        "isPayerAmount" => false,
                        "description" => "Получение фото штрафа",
                        "operationInfo" => [
                            "attribute" => $attribute
                        ],
                        "isPayerAmount" => false,
                        "paymentPassword" => "12345",
                        "version" => "VERSION_2"
                    ]
                ]
            ]
        ];
        //die();
        //echo json_encode($request_data);
        //die();
        $response = $client->request('POST', 'https://service.moneta.ru/services', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_data)
        ]);
        
        $body = $response->getBody()->getContents();
        $arr = json_decode($body, true);
        echo '<pre>';
        print_r($arr);
        echo '</pre>';
        if(isset($arr['Envelope']['Body']['PaymentResponse']['id'])) {
            $id = $arr['Envelope']['Body']['PaymentResponse']['id'];
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
                        "GetOperationDetailsByIdRequest"=> $id
                    ]
                ]
            ];

            $response = $client->request('POST', 'https://service.moneta.ru/services', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($request_data)
            ]);
            
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            // echo '<pre>';
            // print_r($arr);
            // echo '</pre>';
            if(isset($arr['Envelope']['Body']['GetOperationDetailsByIdResponse']['operation']['attribute'])) {
                $attributes = $arr['Envelope']['Body']['GetOperationDetailsByIdResponse']['operation']['attribute'];
                $photos = [];
                $i = 0;
                foreach($attributes as $a) {
                    if(strstr($a['key'], 'customfield:photo_url')) {
                        $tenant = tenant('id');
                        $url = $a['value'];
                        // echo $url.'<br>';
                        // $encoded = urlencode( base64_encode($url) );
                        // $url = base64_decode( urldecode( $encoded ) );
                        // print_r($url);
                        $disk = \Storage::disk('public');
                        $file = $fine->id.'_'.$i.'_'.time().'.jpg';
                        $disk->put('files/'.$file, file_get_contents($url));
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
                    }
                }
                if(count($photos)) {
                    $fine->photo = json_encode($photos);
                    $fine->saveQuietly();
                }

                echo '<pre>';
                print_r($photos);
                echo '</pre>';
            } 
        } else {
            return ['error' => 400, 'message' => 'Ошибка получения фото'];
        }
        // if(!isset($arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']))
        //     return [];

        // $new_fines = $arr['Envelope']['Body']['AsyncResponse']['SmevFindChargesResponse']['charges']['charge'];

        // $new_fines = collect($new_fines)->keyBy('supplierBillID')->toArray();

        return $arr;
    }

}

