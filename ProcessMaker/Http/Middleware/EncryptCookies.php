<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

/**
 * Encrypts cookies to ensure security.  This is an empty extended class from Laravel's EncryptCookies
 * middleware.  However, if we need to add any cookie names that should not be encrypted, they can go
 * here.
 */
class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     * @var array
     */
    protected $except = [
        /**
         * Add any cookie names where we should not encrypt it's data here
         */
        'device_id',
        'fromTriggerStartEvent',
        'language'
    ];
}
