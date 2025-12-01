<?php

namespace Modules\Osago\Entities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CrudService;
use Mail;
use App\Models\Employee;
use App\Models\Osago\OsagoPolis;


enum KbmType: string
{
    case Driver = 'driver';
    case Owner = 'owner';
}

class Module
{
    public static function getToken()
    {
      $token = 'nucwoc-9kixje-jupvIj';
      $link = 'https://api.inssmart.ru/v1/main/accounts/token'; //Формируем URL для запроса
      $headers = [
          'Content-Type: application/json',
          'Authorization: Bearer ' . $token
      ];

      $client = new \GuzzleHttp\Client();
      try {
        $response = $client->request('POST', $link, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'email' => 'reg@compas.pro',
                'password' => 'nucwoc-9kixje-jupvIj'
            ]
        ]);

        $res = json_decode($response->getBody()->getContents(), true);
        if(isset($res['accessToken'])) {
          $token = $res['accessToken'];
          //echo $res['accessToken'].'<br>';
          tenancy()->central(function () use ($token){
            \DB::table('settings')->where('key', 'osago_token')->update(['value' => $token]);
          });

          return $token;
        }
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);
          // echo '<pre>';
          // print_r($e->getMessage());
          // echo '</pre>';
          //echo $responseBodyAsString;
      }
    }

    public static function getPolis(OsagoPolis $polis)
    {
      $client = new \GuzzleHttp\Client();
      try {
        $token = tenancy()->central(function () {
          return \DB::table('settings')->where('key', 'osago_token')->first()->value;
        });
        $car = $polis->car;
        if($car) {
          if($car->sts_number) {
            $doc_type = 2;
            $doc_number = $car->getStringValue('sts_number');
            $doc_date = $car->getStringValue('sts_date');
          } elseif($car->pts_number) {
            $doc_type = 1;
            $doc_number = $car->getStringValue('pts_number');
            $doc_date = $car->getStringValue('pts_date');
          } elseif($car->epts_number) {
            $doc_type = 3;
            $doc_number = $car->getStringValue('epts_number');
            $doc_date = $car->getStringValue('epts_date');
          }
          $drivers = [];
          if($car->employees) {
            foreach ($car->employees as $driver) {
              $driver_license = $driver->getStringValue('driver_license');
              $drivers[] = [
                'factor' => $driver->kbm ? $driver->kbm : self::getKbm($driver, KbmType::Driver),
                'firstName' => $driver->getStringValue('first_name'),
                'lastName' => $driver->getStringValue('last_name'),
                'birthDate' => $driver->getStringValue('birthdate'),
                'experienceDate' => '2017-04-16T00:00:00.000Z',//$driver->experience,
                'driverLicenseNumber' => substr($driver_license, 4, 6),
                'driverLicenseSeries' => substr($driver_license, 0, 4),
                'driverLicenseForeign' => false
              ];
            }
          }
          //echo $car->model_id;
          $params = [
            'validFrom' => date(DATE_ISO8601, strtotime($polis->date_start)),
            'validTo' => date(DATE_ISO8601, strtotime($polis->date_end)),
            'period' => 12,
            'purpose' => $polis->purpose,
            'multidrive' => $car->employees->count() > 1 ? true : false,
            'vehicleCarModel' => $car->model->osago_code,
            'vehicleCarMarkName' => $car->mark->osago_code,
            'vehicleCarCategory' => $car->model->osago_category,
            'vehicleDocumentType' => $doc_type,
            'vehicleDocumentNumber' => $doc_number,
            'vehicleDocumentDate' => $doc_date,
            'vehicleUseTrailer' => $car->has_trailer ? true : false,
            'vehicleVinNumber' => $car->vin_number,
            'vehicleNoRegNumber' => $car->number ? false : true,
            'vehicleRegNumber' => $car->getStringValue('number'),
            'vehicleYear' => $car->year,
            'vehicleMaxMass' => $car->max_weight,
            'vehicleMass' => $car->weight,
            'vehicleSeats' => $car->count_seats,
            'vehiclePower' => $car->power,
            'insurantPhone' => $polis->getStringValue('policyholder_phone'),
            'insurantEmail' => $polis->getStringValue('policyholder_email'),
            'insurantLegalAddress' => $polis->getStringValue('policyholder_address'),
            'insurantPassportNumber' => $polis->getStringValue('policyholder_passport_number'),
            'insurantPassportSeries' => $polis->getStringValue('policyholder_passport_series'),
            'insurantPassportIssue' => $polis->getStringValue('policyholder_passport_date'),
            'insurantFirstName' => $polis->getStringValue('policyholder_name'),
            'insurantLastName' => $polis->getStringValue('policyholder_last_name'),
            'insurantPatronymic' => $polis->getStringValue('policyholder_patronymic'),
            'insurantBirthDate' => $polis->getStringValue('policyholder_birthday'),
            'ownerIsInsurant' => true,
           // 'drivers' => $drivers
          ];
          if(!$params['multidrive'])
            $params['drivers'] = $drivers;
          // echo '<pre>';
          // print_r($params);
          // echo '</pre>';
          //die();
          $link = 'https://api.inssmart.ru/v1/product-osago/contracts';
          $response = $client->request('POST', $link, [
              'headers' => [
                  'Authorization' => 'Bearer '.$token,
                  'Accept' => 'application/json',
              ],
              'form_params' => $params
          ]);

          $res = json_decode($response->getBody()->getContents(), true);
          // echo '<pre>';
          // print_r($res);
          // echo '</pre>';
          info($res);
          info('Exception0');
          if(isset($res['id'])) {
            $polis->external_id = $res['id'];
            $polis->status = $res['status'];
            $polis->draft_state = $res['draftState'];

            $polis->payment = json_encode([
              'state' => 0,
              'value' => 0,
              'type' => 'choose'
            ]);
            $polis->saveQuietly();
          };
        }
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);
          // echo '<pre>';
          // print_r($e->getMessage());
          // echo '</pre>';
          info($res);
          info('Exception');
          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            self::getPolis($polis);
          } else {
            $polis->external_id = null;

            $polis->payment = null;
            $polis->saveQuietly();
          }
          //echo $responseBodyAsString;
      }

    }

    public static function findOffers(OsagoPolis $polis, $offers = [])
    {
      $client = new \GuzzleHttp\Client();
      try {
        $token = tenancy()->central(function () {
          return \DB::table('settings')->where('key', 'osago_token')->first()->value;
        });
       
        $link = "https://api.inssmart.ru/v1/product-osago/contracts/{$polis->external_id}/offers";
        $params = [];
        if(count($offers)) {
          $params = [
            'countOfMinutes' => 1,
            'offers' => $offers
          ];
        }
        try {
          $response = $client->request('POST', $link, [
              'headers' => [
                  'Authorization' => 'Bearer '.$token,
                  'Accept' => 'application/json',
              ],
              'form_params' => $params
          ]);
        } catch(\GuzzleHttp\Exception\ServerException $e){

        }

        //$res = json_decode($response->getBody()->getContents(), true);

      } catch (\GuzzleHttp\Exception\ClientException $e) {
          // $response = $e->getResponse();
          // $responseBodyAsString = $response->getBody()->getContents();
          // $res = json_decode($responseBodyAsString, true);

          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            self::findOffers($polis);
          }
      }
      
    }

    public static function getOffers(OsagoPolis $polis)
    {
      $client = new \GuzzleHttp\Client();
      try {
        $token = tenancy()->central(function () {
          return \DB::table('settings')->where('key', 'osago_token')->first()->value;
        });
       
        $link = "https://api.inssmart.ru/v1/product-osago/contracts/{$polis->external_id}/offers";
        $response = $client->request('GET', $link, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ]
            
        ]);

        $res = json_decode($response->getBody()->getContents(), true);

        
        // echo '<pre>';
        // print_r($res);
        // echo '</pre>';
        if(isset($res['offers'])) {
          $offers = $company_ids = [];
          $companies = \App\Models\Osago\OsagoCompany::get()->keyBy('external_id')->toArray();

          foreach($res['offers'] as $offer) {
            if($offer['canBuy']) {
              $offers[] = [
                'id' => $offer['id'],
                'company_id' => $offer['code'],
                'name' => isset($companies[$offer['code']]) ? $companies[$offer['code']]['name'] : 'Неизвестная компания',
                'photo' => isset($companies[$offer['code']]) ? json_decode($companies[$offer['code']]['photo'], true) : null,
                'price' => $offer['price'],
              ];
            }
          }
          return $offers;
        }
        
        return [];
        if(isset($res['state']) && $res['state'] == 3) {
          // $offers = [];
          // foreach($res['offers'] as $key => $offer) {
          //   $offers[] = ['id' => $offer['id']];
          // }
          // return self::findOffers($polis, $offers);
        }
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);
          // echo '<pre>';
          // print_r($e->getMessage());
          // echo '</pre>';
          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            return self::getOffers($polis);
          } else {
            return [];
          }
      }
      
    }

    public static function getPaymentLink(OsagoPolis $polis, array $offer)
    {
      info('payment link');
      info("https://api.inssmart.ru/v1/product-osago/contracts/{$polis->external_id}/payments");


      $payment_link = $polis->payment ? json_decode($polis->payment, true) : null;

      if(isset($payment_link['state']) && $payment_link['state'] == 1) {
        return [
          'message' => 'Полис оплачен'
        ];
      }

      if(isset($payment_link['expiration']) && date(DATE_ISO8601, time()) < $payment_link['expiration'] && isset($offer['id']) && $offer['id'] == $payment_link['offer_id']) {
        return $payment_link;
      }

      $client = new \GuzzleHttp\Client();
      try {
        $token = tenancy()->central(function () {
          return \DB::table('settings')->where('key', 'osago_token')->first()->value;
        });
       
        $link = "https://api.inssmart.ru/v1/product-osago/contracts/{$polis->external_id}/payments";
        $response = $client->request('POST', $link, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
            'form_params' => [
              'company' => $offer['company_id'] ?? null,
              'offerId' => $offer['id'] ?? ''
           //successUrl Адрес для перехода при успешной оплате
           //failUrl  Адрес для перехода при ошибке оплаты
            ]
            
        ]);
        $res = json_decode($response->getBody()->getContents(), true);
        info('link');
        info($res);
        if(isset($res['success']) && $res['success'] && !isset($res['recalculate'])) {
          $companies = \App\Models\Osago\OsagoCompany::get()->keyBy('external_id')->toArray();
          $polis->payment = json_encode([
            'state' => 0,
            'type' => 'paymentChoose',
            'value' => $offer['price'],
            'link' => $res['paymentLink']['link'],
            'expiration' => $res['paymentLink']['expiration'],
            'offer_id' => $offer['id']
          ]);
          $crudService = new CrudService;
          $data = [];
          $data[] = [
            'id' => $polis->id,
            'company_id' => isset($companies[$offer['company_id']]) ? $companies[$offer['company_id']]['id'] : null
          ];
          $result = $crudService->batch('osago_polises', $data);
          $polis->saveQuietly();

          return [
            'state' => 0,
            'type' => 'paymentChoose',
            'value' => $offer['price'],
            'link' => $res['paymentLink']['link'],
            'expiration' => $res['paymentLink']['expiration'],
            'offer_id' => $offer['id']
          ];
        }
        // array (
        //   'success' => true,
        //   'recalculate' => false,
        //   'paymentLink' => 
        //   array (
        //     'link' => 'https://pay.alfabank.ru/payment/merchants/alfa_insur2/payment_ru.html?mdOrder=2afc3cbc-915c-7b2d-b25a-813c00009efa',
        //     'expiration' => '2024-12-13T23:59:00+03:00',
        //     'needSmsCode' => false,
        //   ),
        // )  
        info('RRRR');
        info($res);
        return $res;
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);


          info('error');
          info($res);
          // echo '<pre>';
          // print_r($e->getMessage());
          // echo '</pre>';
          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            return self::getPaymentLink($polis, $company_id, $offer_Id);
          }
          return $res;
      }
      
    }



    public static function getCompanies()
    {
      $client = new \GuzzleHttp\Client();
      
      try {
        $token = tenancy()->central(function () {
          return \DB::table('settings')->where('key', 'osago_token')->first()->value;
        });
       
        $link = "https://api.inssmart.ru/v1/main/contractCompanies?type=2000";
        $response = $client->request('GET', $link, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ]
            
        ]);

        $res = json_decode($response->getBody()->getContents(), true);

        


        if(isset($res['items'])) {
          $crudService = new CrudService;
          $new_companies = [];
          foreach($res['items'] as $item) {
            if(\DB::table('osago_companies')->where('external_id', $item['id'])->doesntExist()) {
              $new_companies[] = [
                'id' => 0,
                'external_id' => $item['id'],
                'code' => $item['alias'],
                'name' => $item['name']
              ];
            }
          }
          if(count($new_companies)) {
            $result = $crudService->batch('osago_companies', $new_companies);
          }
        }
        
        // if(isset($res['offers']))
        //   return $res;
        // if(isset($res['state']) && $res['state'] == 3) {
        //   // $offers = [];
        //   // foreach($res['offers'] as $key => $offer) {
        //   //   $offers[] = ['id' => $offer['id']];
        //   // }
        //   // return self::findOffers($polis, $offers);
        // }
      } catch (\GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);
          
          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            return self::getCompanies();
          }
      }
    }


    public static function getKbm(Employee $employee, KbmType $type)
    {
      $client = new \GuzzleHttp\Client();
      
      if($type->value == 'driver') {
        $driver_license = json_decode($employee->driver_license, true);
        if(isset($driver_license['value']))
          $driver_license = $driver_license['value'];
        else
          $driver_license = $employee->driver_license;
        $item = [
          'firstName' => $employee->first_name,
          'lastName' => $employee->last_name,
          'birthDate' => $employee->birthdate,
          'driverLicenseNumber' => substr($driver_license, 4, 6),
          'driverLicenseSeries' => substr($driver_license, 0, 4),
        ];
      } else {
        $item = [
          'ownerLastName' => $employee->last_name,
          'ownerFirstName' => $employee->first_name,
          'ownerBirthDate' => $employee->birthdate,
          'ownerPassportSeries' => $employee->passport_series,
          'ownerPassportNumber' => $employee->passport_number,
        ];
      }
      $link = 'https://api.inssmart.ru/v1/product-osago/KBMInfo';
      $token = tenancy()->central(function () {
        return \DB::table('settings')->where('key', 'osago_token')->first()->value;
      });

      try {
        $response = $client->request('POST', $link, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ],
            'form_params' => [
                $type->value => $item
            ]
        ]);

        $res = json_decode($response->getBody()->getContents(), true);

        if(isset($res['factor'])) {
          $crudService = new CrudService;
          $result = $crudService->batch('employees', [['id' => $employee->id, 'kbm' => $res['factor']]]);
          return $res['factor'];
        };
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          $response = $e->getResponse();
          $responseBodyAsString = $response->getBody()->getContents();
          $res = json_decode($responseBodyAsString, true);
          if(isset($res['message']) && $res['message'] == 'Пользователь не авторизован') {
            $token = self::getToken();
            return self::getKbm($employee, $type->value);
          }
      }
    }
}

