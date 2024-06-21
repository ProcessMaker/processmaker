<?php

/**
 * Configuration file for Process Intelligence.
 *
 * This file contains the configuration options for Process Intelligence.
 */
return [
    // The secret key used to sign the JWT. This should at least 256 bits (32 bytes) for HS256.
    'secret_key' => env('PROCESS_INTELLIGENCE_JWT_SIGNATURE_SECRET_KEY'),

    'encryption_key' => env('PROCESS_INTELLIGENCE_JWE_ENCRYPTION_KEY'),

    'company_name' => env('PROCESS_INTELLIGENCE_COMPANY_NAME'),

    'company_bucket' => env('PROCESS_INTELLIGENCE_COMPANY_BUCKET'),
];
