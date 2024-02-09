<?php

namespace Modules\Gibdd\Entities;

class Module
{
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
                    'username' => 'dp@opt6.ru',
                    'password' => 'A273574959a'
                    //'name' => '123',
                    //'sum' => 300
                    //'uin' => '18810550231018083263'
                ]
            ]);
            //echo $response->getBody();
            $res = json_decode($response->getBody(), true);

            return $res;
            //print_r($res);
            //echo $response->getBody();
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return array('error' => 406);
            // $response = $e->getResponse();
            // echo $response->getStatusCode();
            // die();
            // $responseBodyAsString = $response->getBody()->getContents();
            // echo $responseBodyAsString;
        }
    }
    public static function syncCars($tenant)
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
                        //echo $item['regnum'].'<br>';
                        // if(isset($cars[$item['regnum']])) {
                        //     $cars[$item['regnum']]->carsmonitoring_id = $item['id'];
                        //     $cars[$item['regnum']]->saveQuietly();
                        // }
                    }
                    while(isset($arr['next']) && $arr['next'] > $page) {
                        $page = $arr['next'];
                        $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/cars/?page='.$page, [
                            'headers' => [
                                'Authorization' => $token,
                            ]
                        ]);
                        //echo 'page '.$page.'<br>';
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
                        echo $regnum.' '.$item['id'].'<br>';
                        $cars[$regnum]->carsmonitoring_id = $item['id'];
                        $cars[$regnum]->saveQuietly();
                    }
                }
            });
        }
    }

    public static function addCar($data)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $client = new \GuzzleHttp\Client();
            try {
                $response = $client->request('POST', 'https://api.driver-helper.ru/v1.0/car/', [
                        'headers' => [
                            'Authorization' => $token,
                        ],
                        'form_params' => [
                            'stsnum' => $data['stsnum'],
                            'regnum' => $data['regnum']
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
                            'title' => $data['title']
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

    public static function updateFines($tenant)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $token = $res['token'];
            $tenant->run(function () use ($token) {
                $client = new \GuzzleHttp\Client();
                $tenant = tenant('id');
                $current_date = date('d.m.Y');
                $page = 1;
                $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->whereNotNull('carsmonitoring_id')->get()->keyBy('carsmonitoring_id');
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
                    // echo '<pre>';
                    // print_r($arr);
                    // echo '</pre>';
                    // echo 'next '.$arr['next'].'<br>';
                    while(isset($arr['next']) && $arr['next'] > $page) {
                        $page = $arr['next'];
                        //echo 'page '.$page.'<br>';
                        $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fines/?page='.$page, [
                            'headers' => [
                                'Authorization' => $token,
                            ]
                        ]);
                        //echo 'page '.$page.'<br>';
                        $body = $response->getBody()->getContents();
                        $arr = json_decode($body, true);
                        //$page = $arr['next'];
                        if(isset($arr['items'])) {
                            foreach($arr['items'] as $item) {
                                // echo '<pre>';
                                // print_r($item);
                                // echo '</pre>';
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
                    // echo '<pre>';
                    // print_r($new_fines);
                    // echo '</pre>';
                    foreach($new_fines as $fine) {
                        //info('FINE '.$fine['NumPost']);
                        //echo 'rez1<br>';
                        
                        // echo $fine['car']['regnum'].'<br>';
                        // echo '<pre>';
                        // print_r($fine);
                        // echo '</pre>';
                        // if(!\App\Models\GibddFine::where('number_doc', $fine['post_num'])->exists()) {
                        //     echo 'create<br>';
                        //     echo $fine['car']['id'].'<br>';
                        //     echo '<pre>';
                        //     print_r($cars);
                        //     echo '</pre>';
                        // }
                        //$record = null;
                        if(!\App\Models\GibddFine::where('number_doc', $fine['post_num'])->exists()) {
                            $text = mb_strtolower($fine['koap_text']);
                            $firstChar = mb_substr($text, 0, 1);
                            $then = mb_substr($text, 1, null);
                            $name = mb_strtoupper($firstChar) . $then;
                            if($fine['doc_type'] == 'sts' && isset($cars[$fine['car']['id']])) {
                                $date_check = date('d.m.Y', strtotime($cars[$fine['car']['id']]->date_check));
                                $cars[$fine['car']['id']]->date_check = \DB::raw('now()');
                                echo 'record car<br>';
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
                                    'sale_finish'=> date('Y-m-d', strtotime($fine['discount_date'])),
                                    'name'=> $name,//$fine['VehicleModel'],
                                    //'division_id' => $fine['Division_id'],
                                    'status' => $fine['paid'] ? 745 : 746
                                );
                                echo '<pre>';
                                print_r($record);
                                echo '</pre>';
                            }
                            if($fine['doc_type'] == 'driver_licence' && isset($employees[$fine['driver']['id']])) {
                                $date_check = date('d.m.Y', strtotime($employees[$fine['driver']['id']]->date_check));
                                $employees[$fine['driver']['id']]->date_check = \DB::raw('now()');
                                
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
                                    'sale_finish'=> date('Y-m-d', strtotime($fine['discount_date'])),
                                    'name'=> $name,//$fine['VehicleModel'],
                                    //'division_id' => $fine['Division_id'],
                                    'status' => $fine['paid'] ? 745 : 746
                                );
                                
                            }
                            $photos = array();
                            $photo_response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fine/'.$fine['id'].'/photo/base64', [
                                'headers' => [
                                    'Authorization' => $token,
                                ]
                            ]);
                            
                            $body_photo = $photo_response->getBody()->getContents();
                            $arr_photo = json_decode($body_photo, true);
                            $i = 0;
                            // echo '<pre>';
                            // print_r($arr_photo);
                            // echo '</pre>';
                            if(isset($arr_photo['status']) && $arr_photo['status'] == 'photo_exists') {
                                foreach($arr_photo['photos'] as $i => $photo) {
                                    //echo $photo.'<br>';
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
                            
                            echo '<pre>';
                            print_r($record);
                            echo '</pre>';
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
                                echo '<pre>';
                                print_r($record);
                                echo '</pre>';
                                $data[] = $record;
                            }
                        }
                        
                    }
                    if(isset($data) && count($data)) {
                        foreach($data as $record) {
                            $fine = \App\Models\GibddFine::create($record);//new \App\Models\GibddFine;
                            //$fine->saveQuietly();
                            $fine->saveHistory($record);
                            echo 'HISTORY';
                            echo '<pre>';
                            print_r($record);
                            echo '</pre>';
                            $fine->update($record);

                            
                            
                        }
                        //$fine->saveHistory($row)
                        //\DB::table('fines_gibdd')->insert($data);
                    }
                    // echo '<pre>';
                    // print_r($arr);
                    // echo '</pre>';
                    //echo $response->getBody();
                //}
                foreach($fines as $fine) {

                    \DB::table('fines_gibdd')->where('id', $fine['id'])->update(['status' => 745]);
                }
            });
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
        $res = self::login();
        $tenant = tenant('id');
        if(isset($res['token'])) {
            $token = $res['token'];
            
            $client = new \GuzzleHttp\Client();
            $tenant = tenant('id');
            $page = 1;
            $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->whereNotNull('carsmonitoring_id')->get()->keyBy('carsmonitoring_id');

            $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fines/?page=1', [
                    'headers' => [
                        'Authorization' => $token,
                    ]
                ]);
                
            $body = $response->getBody()->getContents();
            $arr = json_decode($body, true);
            $fine = null;
            if(isset($arr['items'])) {
                foreach($arr['items'] as $item) {
                    if($item['post_num'] == $num)
                        $fine = $item;
                }
                while(isset($arr['next']) && $arr['next'] > $page && !$searchd_fine) {
                    $page = $arr['next'];
                    $response = $client->request('GET', 'https://api.driver-helper.ru/v1.0/fines/?page='.$page, [
                        'headers' => [
                            'Authorization' => $token,
                        ]
                    ]);
                    $body = $response->getBody()->getContents();
                    $arr = json_decode($body, true);
                    if(isset($arr['items'])) {
                        foreach($arr['items'] as $item) {
                            if($item['post_num'] == $num)
                                $fine = $item;
                        }

                    }
                }
            }
            if(!$fine)
                return array(
                    'code' => 404,
                    'error' => 'Штраф не найден',
                );
            if(!\App\Models\GibddFine::where('number_doc', $fine['post_num'])->exists()) {
                $text = mb_strtolower($fine['koap_text']);
                $firstChar = mb_substr($text, 0, 1);
                $then = mb_substr($text, 1, null);
                $name = mb_strtoupper($firstChar) . $then;
                if($fine['doc_type'] == 'sts' && isset($cars[$fine['car']['id']])) {
                    $date_check = date('d.m.Y', strtotime($cars[$fine['car']['id']]->date_check));
                    $cars[$fine['car']['id']]->date_check = \DB::raw('now()');
                    echo 'record car<br>';
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
                        'sale_finish'=> date('Y-m-d', strtotime($fine['discount_date'])),
                        'name'=> $name,//$fine['VehicleModel'],
                        //'division_id' => $fine['Division_id'],
                        'status' => $fine['paid'] ? 745 : 746
                    );
                    echo '<pre>';
                    print_r($record);
                    echo '</pre>';
                }
                if($fine['doc_type'] == 'driver_licence' && isset($employees[$fine['driver']['id']])) {
                    $date_check = date('d.m.Y', strtotime($employees[$fine['driver']['id']]->date_check));
                    $employees[$fine['driver']['id']]->date_check = \DB::raw('now()');
                    
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
                        'sale_finish'=> date('Y-m-d', strtotime($fine['discount_date'])),
                        'name'=> $name,//$fine['VehicleModel'],
                        //'division_id' => $fine['Division_id'],
                        'status' => $fine['paid'] ? 745 : 746
                    );
                    
                }
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
                        //echo $photo.'<br>';
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
                }
            }
            if(isset($record)) {
                $fine = \App\Models\GibddFine::create($record);
                $fine->saveHistory($record);
                $fine->update($record);
                return array(
                    'code' => 200,
                    'id' => $fine->id,
                );
            }
        }
    }

    public static function handle($tenant)
    {
        $res = self::login();
        if(isset($res['token'])) {
            $tenant->run(function () {
                $client = new \GuzzleHttp\Client();
                $tenant = tenant('id');
                $current_date = date('d.m.Y');
                
                $cars = \App\Models\Car::whereNotNull('number')->whereNotNull('sts_number')->get();
                $fines = \App\Models\GibddFine::get()->keyBy('number_doc')->toArray();
                foreach($cars as $car) {
                    // if($car->id != 306)
                    //     continue;
                    $date_check = date('d.m.Y', strtotime($car->date_check));
                    // if($date_check == $current_date) {
                    //     foreach($fines as $k => $fine) {
                    //         if($fine['car_id'] == $car->id)
                    //             unset($fines[$k]);
                    //     }
                    //     echo $car->id.': '.$car->sts_number.' '.$car->number.'<br>';
                    //     echo 'проверяли';
                    //     continue;
                    // }
                    $data = array();
                    $response = $client->request('GET', 'https://api-cloud.ru/api/gibdd.php?type=fines&regNumber='.$car->number.'&stsNumber='.str_replace(' ', '', $car->sts_number).'&requisites=1&token=394e1bf0122748f03a2df35b11ece21b', [
                    ]);
                    
                    $body = $response->getBody()->getContents();
                    $arr = json_decode($body, true);
                    $car->date_check = \DB::raw('now()');
                    $car->saveQuietly();
                    //info($arr);
                    if(isset($arr['status']) && $arr['status'] == 200 && $arr['num'] > 0) {
                        foreach($arr['rez'] as $fine) {
                            //info('FINE '.$fine['NumPost']);
                            //echo 'rez1<br>';
                            if(!\App\Models\GibddFine::where('number_doc', $fine['NumPost'])->exists()) {
                                echo 'rez2<br>';
                                $text = mb_strtolower($fine['KoAPtext']);
                                $firstChar = mb_substr($text, 0, 1);
                                $then = mb_substr($text, 1, null);
                                $name = mb_strtoupper($firstChar) . $then;
                                $record = array(
                                    //'address'=> '[value-5]',
                                    //'created_at' => $car->date_check,
                                    //'updated_at' => $car->date_check,
                                    'date'=> $fine['DateDecis'],
                                    'company_id'=> $car->company_id,
                                    //'employee_id'=> '[value-9]',
                                    'car_id'=> $car->id,
                                    'number_doc'=> $fine['NumPost'],
                                    'date_doc'=> date('Y-m-d', strtotime($fine['DatePost'])),
                                    //'place'=> '[value-13]',
                                    'article'=> $fine['KoAPcode'],
                                    'discharged_by'=> $fine['division_name'],
                                    //'name_of_payment'=> $fine['KoAPtext'],
                                    'name_of_payment'=> $fine['division_requisites']['payment_receiver'],
                                    //'kbk'=> $fine['division_requisites']['kbk'],
                                    'inn'=> $fine['division_requisites']['inn'],
                                    'kpp'=> $fine['division_requisites']['kpp'],
                                    'bank'=> $fine['division_requisites']['bank'],
                                    'invoice'=> $fine['division_requisites']['single_bank_rs'],
                                    'corr_invoice'=> $fine['division_requisites']['kazn_rs'],
                                    'bik'=> $fine['division_requisites']['bik_tofk'],
                                    'oktmo'=> $fine['division_requisites']['oktmo'],
                                    //'status'=> '[value-25]',
                                    'sum'=> $fine['Summa'],
                                    'discount_sum'=> $fine['Discount'] ? $fine['Summa']/2 : $fine['Summa'],
                                    'sale_finish'=> $fine['DateDiscount'],
                                    'name'=> $name,//$fine['VehicleModel'],
                                    'division_id' => $fine['Division_id'],
                                    'status' => 746
                                );
                                if($fine['photo']) {
                                    $photos = array();
                                    echo 'rez3<br>';
                                    $photo_response = $client->request('GET', 'https://api-cloud.ru/api/gibdd.php?type=finesPhoto&regNumber='.$car->number.'&postNum='.$fine['NumPost'].'&division='.$fine['Division_id'].'&photoToken='.$fine['phototoken'].'&token=394e1bf0122748f03a2df35b11ece21b', [
                                    ]);
                                    $body_photo = $photo_response->getBody()->getContents();
                                    $arr_photo = json_decode($body_photo, true);
                                    $i = 0;
                                    if(isset($arr_photo['rez'])) {
                                        foreach($arr_photo['rez'] as $photo) {
                                            $disk = \Storage::disk('public');
                                            $file = basename($photo['urlPhoto']);
                                            $ext = pathinfo($photo['urlPhoto'], PATHINFO_EXTENSION);
                                            $img = \Image::make(file_get_contents($photo['urlPhoto']));
                                            $img->orientate();
                                            $img->resize(1024, null, function($constraint){ 
                                                $constraint->upsize();
                                                $constraint->aspectRatio();
                                            });
                                            $img->save($disk->path('files/'.$file), 90);
                                            $document = new \App\Models\File();
                                            $document->name = $file;
                                            $document->path = 'files/'.$file;
                                            $document->save();
                                            $media = $document->addMediaFromUrl('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path)->toMediaCollection();
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
                                         
                                    //die();
                                }
                                if(count($photos))
                                    $record['photo'] = json_encode($photos);
                                $data[] = $record;
                            } elseif(array_key_exists($fine['NumPost'], $fines)) {
                                if($fine['photo'] && (!isset($fines[$fine['NumPost']]['photo'])) || !$fines[$fine['NumPost']]['photo'] && isset($fines[$fine['NumPost']]['division_id']) && $fines[$fine['NumPost']]['division_id']) {
                                    $photos = array();
                                    echo 'rez3<br>';
                                    $photo_response = $client->request('GET', 'https://api-cloud.ru/api/gibdd.php?type=finesPhoto&regNumber='.$car->number.'&postNum='.$fine['NumPost'].'&division='.$fines[$fine['NumPost']]['Division_id'].'&photoToken='.$fine['phototoken'].'&token=394e1bf0122748f03a2df35b11ece21b', [
                                    ]);
                                    $body_photo = $photo_response->getBody()->getContents();
                                    $arr_photo = json_decode($body_photo, true);
                                    $i = 0;
                                    if(isset($arr_photo['rez'])) {
                                        foreach($arr_photo['rez'] as $photo) {
                                            $disk = \Storage::disk('public');
                                            $file = basename($photo['urlPhoto']);
                                            $ext = pathinfo($photo['urlPhoto'], PATHINFO_EXTENSION);
                                            $img = \Image::make(file_get_contents($photo['urlPhoto']));
                                            $img->orientate();
                                            $img->resize(1024, null, function($constraint){ 
                                                $constraint->upsize();
                                                $constraint->aspectRatio();
                                            });
                                            $img->save($disk->path('files/'.$file), 90);
                                            $document = new \App\Models\File();
                                            $document->name = $file;
                                            $document->path = 'files/'.$file;
                                            $document->save();
                                            $media = $document->addMediaFromUrl('https://'.$tenant.'.compas.pro/storage/tenant'.$tenant.'/app/public/'.$document->path)->toMediaCollection();
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
                                         
                                    //die();
                                }
                                if(isset($photos) && count($photos)) {
                                    \DB::table('fines')->where('id', $fines[$fine['NumPost']]['id'])->update(['photo' => json_encode($photos)]);
                                    //$record['photo'] = json_encode($photos);
                                }
                                unset($fines[$fine['NumPost']]);
                            }
                        }
                    }
                    if(count($data)) {
                        foreach($data as $record) {
                            $fine = new \App\Models\GibddFine;
                            $fine->saveQuietly();
                            $fine->saveHistory($record);
                            $fine->update($record);

                            
                            echo 'HISTORY';
                            echo '<pre>';
                            print_r($record);
                            echo '</pre>';
                        }
                        //$fine->saveHistory($row)
                        //\DB::table('fines_gibdd')->insert($data);
                    }
                    echo '<pre>';
                    print_r($arr);
                    echo '</pre>';
                    //echo $response->getBody();
                }
                foreach($fines as $fine) {

                    \DB::table('fines_gibdd')->where('id', $fine['id'])->update(['status' => 745]);
                }
            });
        }
    }
}

