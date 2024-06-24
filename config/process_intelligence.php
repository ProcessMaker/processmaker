<?php

/**
 * Process Intelligence Configuration
 *
 * This file defines the configuration options for the Process Intelligence integration.
 */
return [
    // The secret key used to sign the JWT. This should be at least 256 bits (32 bytes) for HS256 base64-encoded.
    'secret_key' => env('PROCESS_INTELLIGENCE_JWT_SIGNATURE_SECRET_KEY'),

    'encryption_key' => env('PROCESS_INTELLIGENCE_JWE_ENCRYPTION_KEY'),

    'company_name' => env('PROCESS_INTELLIGENCE_COMPANY_NAME', 'Company Name'),

    'company_bucket' => env('PROCESS_INTELLIGENCE_COMPANY_DATABASE', 'company-database'),
];
