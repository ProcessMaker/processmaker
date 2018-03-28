<?php
namespace ProcessMaker\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        /**
         * Add any routes where submissions may occur but we don't want to protect via CSRF. This should be used
         * sparingly.
         */
    ];
}
