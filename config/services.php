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

    'processmakerpost' => [
        'secret' => env('PROCESSMAKERPOST_SECRET'),
    ],

    'open_ai' => [
        'secret' => env('OPEN_AI_SECRET'),
    ],

    'github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker/',
        'template_repo' => env('DEFAULT_TEMPLATE_REPO', 'process-templates'),
        'template_branch' => env('DEFAULT_TEMPLATE_BRANCH', 'main'),
        'template_categories' => env('DEFAULT_TEMPLATE_CATEGORIES', 'accounting-and-finance,customer-success,human-resources,marketing-and-sales,operations,it'),
    ],

];
