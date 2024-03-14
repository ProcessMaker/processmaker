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
    'github' => [
        'base_url' => 'https://raw.githubusercontent.com/processmaker/',
        'pm_block_repo' => env('DEFAULT_PM_BLOCKS_REPO', 'pm-blocks'),
        'pm_block_branch' => env('DEFAULT_PM_BLOCKS_BRANCH', '2023-fall'),
        'pm_block_categories' => env(
            'DEFAULT_PM_BLOCKS_CATEGORIES',
            'finance,math'
        ),
    ],
];
