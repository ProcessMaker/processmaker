<?php

return [
    'minimum_length' => env('PASSWORD_POLICY_MINIMUM_LENGTH', 8),
    'maximum_length' => env('PASSWORD_POLICY_MAXIMUM_LENGTH', null),
    'numbers' => env('PASSWORD_POLICY_NUMBERS', true),
    'uppercase' => env('PASSWORD_POLICY_UPPERCASE', true),
    'special' => env('PASSWORD_POLICY_SPECIAL', true),
    //'expiration_days' => env('PASSWORD_POLICY_EXPIRATION_DAYS', 0), // 0 never expires
    'login_attempts' => env('PASSWORD_POLICY_LOGIN_ATTEMPTS', 5),
];
