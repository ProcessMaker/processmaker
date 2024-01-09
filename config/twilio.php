<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account SID
    |--------------------------------------------------------------------------
    |
    | Security identifier from the Twilio account
    |
    */

    'sid' => env('TWILIO_ACCOUNT_SID', null),

    /*
    |--------------------------------------------------------------------------
    | Auth Token
    |--------------------------------------------------------------------------
    |
    | Authorization token generated to consume Twilio API
    |
    */

    'token' => env('TWILIO_AUTH_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | Active phone number
    |--------------------------------------------------------------------------
    |
    | Registered and active phone number to use their capabilities (Voice, SMS, MMS, Fax, etc.). E.164 format.
    |
    */

    'active_phone_number' => env('TWILIO_ACTIVE_PHONE_NUMBER', null),

];
