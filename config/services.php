<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
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
        'template_branch' => env('DEFAULT_TEMPLATE_BRANCH', '2023-fall'),
        'template_categories' => env('DEFAULT_TEMPLATE_CATEGORIES', 'accounting-and-finance,customer-success,human-resources,marketing-and-sales,operations,it'),
    ],

    'guided_templates_github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker/',
        'template_repo' => env('GUIDED_TEMPLATE_REPO', 'wizard-templates'),
        'template_branch' => env('GUIDED_TEMPLATE_BRANCH', '2023-winter'),
        'template_categories' => env('GUIDED_TEMPLATE_CATEGORIES', 'all'),
    ],

    'screen_templates_github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker/',
        'template_repo' => env('SCREEN_TEMPLATE_REPO', 'screen-templates'),
        'template_branch' => env('SCREEN_TEMPLATE_BRANCH', 'spring-2024'),
        'template_categories' => env('SCREEN_TEMPLATE_CATEGORIES', 'all'),
    ],

    'userway' => [
        'account_id' => env('USERWAY_ACCOUNT_ID'),
    ],

    'recommendations_github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker',
        'repo' => env('RECOMMENDATIONS_REPO', 'pm4-recommendations'),
        'branch' => env('RECOMMENDATIONS_BRANCH', 'develop'),
        'token' => env('RECOMMENDATIONS_GITHUB_TOKEN'),
    ],

];
