<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use WhichBrowser\Parser;

class CSP
{
    const HEADER = 'Content-Security-Policy';

    /**
     * Generate the core menus that are used in web requests for our application
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        /** @var $response Response|ResponseHeaderBag|BinaryFileResponse */
        if (method_exists($response, 'header')) {
            $response->header(static::HEADER, static::getRules());
        } elseif ($response instanceof ResponseHeaderBag) {
            $response->set(static::HEADER, static::getRules());
        } elseif (property_exists($response, 'headers') && $response->headers instanceof ResponseHeaderBag) {
            $response->headers->set(static::HEADER, static::getRules());
        }
        return $response;
    }

    public static function getRules()
    {
        $parsed = new Parser(request()->headers->get('user-agent'));
        $rules = [
            'connect-src' => "*",
            'script-src' => "* 'unsafe-inline' 'unsafe-eval'",
            'object-src' => "'self' 'unsafe-inline' blob: data:",
            'child-src' => "'self' blob:",
            'worker-src' => "'self' blob:",
        ];

        //Recommended by logrocket.
        //Compatible from version 15
        if ($parsed->browser->name === 'Safari' && $parsed->browser->version->toString() < 15) {
            unset($rules['worker-src']);
        }

        $value = '';
        foreach ($rules as $key => $rule){
            if ($value) {
                $value.= ' ';
            }
            $value.= $key . ' ' . $rule . ';';
        }
        return $value;
    }
}
