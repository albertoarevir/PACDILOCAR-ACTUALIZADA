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

    'autentificatic' => [
        'url'             => env('AUTENTIFICATIC_API_URL', 'http://autentificaticapi.carabineros.cl/api/auth/validate-token'),
        'host'            => env('AUTENTIFICATIC_API_HOST', 'autentificaticapi.carabineros.cl'),
        'register_url'    => env('AUTENTIFICATIC_REGISTER_URL', 'http://autentificaticapi.carabineros.cl/api/institutional-app-user-from-external-app'),
        'platform_url'    => env('AUTENTIFICATIC_PLATFORM_URL', 'http://pacdilocar.carabineros.cl/'),
        'platform_domain' => env('AUTENTIFICATIC_PLATFORM_DOMAIN', 'pacdilocar.carabineros.cl'),
        'user_data_url'   => env('AUTENTIFICATIC_USER_DATA_URL', 'http://autentificaticapi.carabineros.cl/api/user-data'),
    ],

];
