<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, ProcessmakerPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'processmakerpost' => [
        'secret' => env('PROCESSMAKERPOST_SECRET'),
    ],

    'gmail' => [
        'access_token' => env('GMAIL_API_ACCESS_TOKEN'),
        'expires_in' => env('GMAIL_API_TOKEN_EXPIRATION'),
        'scope' => env('GMAIL_API_SCOPE'),
        'token_type' => env('GMAIL_API_TOKEN_TYPE'),
        'created' => env('GMAIL_API_TOKEN_CREATED'),
    ],
];
