<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms_agent' => [
        'login' => env('SMS_AGENT_LOGIN'),
        'password' => env('SMS_AGENT_PASSWORD'),
    ],

    'dadata' => [
        'token' => env('DADATA_TOKEN', '1aae835b4ef406e670f2fed34e0e1f44a7a2fc46'),
        'secret' => env('DADATA_SECRET', '12b85f4474f0fab219a2307f13a33c05f8418355'),
    ],

    'yandex' => [
        'geocoder_key' => env('YANDEX_GEOCODER_KEY', '10946c08-3ea9-4fac-95d1-c833ee44dd6b'),
    ],

];
