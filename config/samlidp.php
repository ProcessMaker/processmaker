<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAML idP configuration file
    |--------------------------------------------------------------------------
    |
    | Use this file to configure the service providers you want to use.
    |
     */
    // Outputs data to your laravel.log file for debugging
    'debug' => false,
    // Define the email address field name in the users table
    'email_field' => 'email',
    // Define the name field in the users table
    'name_field' => 'name',
    // The URI to your login page
    'login_uri' => 'login',
    // Log out of the IdP after SLO
    'logout_after_slo' => env('LOGOUT_AFTER_SLO', false),
    // The URI to the saml metadata file, this describes your idP
    'issuer_uri' => 'saml/metadata',
    // The certificate
    'cert' => env('SAMLIDP_CERT'),
    // Name of the certificate PEM file, ignored if cert is used
    'certname' => 'cert.pem',
    // The certificate key
    'key' => env('SAMLIDP_KEY'),
    // Name of the certificate key PEM file, ignored if key is used
    'keyname' => 'key.pem',
    // Encrypt requests and responses
    'encrypt_assertion' => true,
    // Make sure messages are signed
    'messages_signed' => true,
    // Defind what digital algorithm you want to use
    'digest_algorithm' => \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA1,
    // list of all service providers
    'sp' => [
        // Base64 encoded ACS URL
        base64_encode(env('SAML_SP_DESTINATION', '')) => [
            'destination' => env('SAML_SP_DESTINATION', ''),
            'logout' => '',
            // SP certificate
            'certificate' => '',
            // Turn off auto appending of the idp query param
            'query_params' => false,
            // Turn off the encryption of the assertion per SP
            'encrypt_assertion' => false,
        ],
    ],

    // If you need to redirect after SLO depending on SLO initiator
    // key is beginning of HTTP_REFERER value from SERVER, value is redirect path
    'sp_slo_redirects' => [
        // 'https://example.com' => 'https://example.com',
    ],

    // All of the Laravel SAML IdP event / listener mappings.
    'events' => [
        'CodeGreenCreative\SamlIdp\Events\Assertion' => [],
        'Illuminate\Auth\Events\Logout' => ['CodeGreenCreative\SamlIdp\Listeners\SamlLogout'],
        'Illuminate\Auth\Events\Authenticated' => ['ProcessMaker\Listeners\SamlAuthenticated'],
        'Illuminate\Auth\Events\Login' => ['ProcessMaker\Listeners\SamlLogin'],
    ],

    // List of guards saml idp will catch Authenticated, Login and Logout events
    'guards' => ['web'],
];
