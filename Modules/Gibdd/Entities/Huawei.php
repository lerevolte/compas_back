<?php

namespace Modules\Gibdd\Entities;
use Illuminate\Http\Request;

class Huawei
{
    public static function getToken()
    {
        $token = tenancy()->central(function () {
            $client = new \GuzzleHttp\Client();
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            try {
                $response = $client->request('POST', 'https://oauth-login.cloud.huawei.com/oauth2/v3/token', [
                    'headers' => $headers,
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => '112813673',
                        'client_secret' => '81fd2487e163d763be73488ee0c673508ecb4a34a2f0e22543174e9661f119f6'
                    ]
                ]);
                $response = json_decode($response->getBody(), true);
                if(isset($response['access_token'])) {
                    \DB::table('settings')->where('key', 'huawei_token')->update(['value' => $response['access_token']]);

                    return $response['access_token'];
                } else {
                    return;
                }
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $res = json_decode($responseBodyAsString, true);

                return;
            }
            
        });
        
        return $token;
    }

    public static function sendNotification($huawei_token = '')
    {
        if(!$huawei_token)
            $huawei_token = tenancy()->central(function () {
                $token = \DB::table('settings')->where('key', 'huawei_token')->first()->value;

                return $token;
            });

        $client = new \GuzzleHttp\Client();
        echo $huawei_token.'<br>';
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$huawei_token}"
        ];
        
        $data = [
            "validate_only" => false,
            "message" => [
                "notification" => [
                    "title" => "Новые штрафы",
                    "body" => "Найдены новые штрафы.",
                    "click_action" => [
                        "type" => 3
                    ]
                ],
                "android" => [
                    "urgency" => "NORMAL",
                    "ttl" => "10000s",
                    "notification" => [
                        "title" => "Новые штрафы",
                        "body" => "Найдены новые штрафы.",
                        "click_action" => [
                            "type" => 3
                        ]
                    ]
                ],
                "token" => [
                    "DEVICE_TOKEN"
                ]
            ]
        ];
        try {
            echo '<pre>';
            print_r($headers);
            echo '</pre>';
            $response = $client->request('POST', 'https://push-api.cloud.huawei.com/v1/112813673/messages:send', [
                'headers' => $headers,
                'body' => json_encode($data)
            ]);
            $response = json_decode($response->getBody(), true);
            if(isset($response['code']) && $response['code'] == '80200003') {
                $huawei_token = self::getToken();
                //echo $huawei_token;
                if($huawei_token) {
                    echo 'repeat send<br>';
                    sleep(2);
                    self::sendNotification($huawei_token);
                }
            }
            return $response;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $res = json_decode($responseBodyAsString, true);

            return $res;;
        }
    }
}