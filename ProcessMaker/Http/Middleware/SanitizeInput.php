<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Route;
use Request;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class SanitizeInput extends TransformsRequest
{
    /**
     * The attributes that should not be sanitized.
     *
     * @var array
     */
    public $except = [
        //
    ];

    /**
     * Construct the class.
     */    
    public function __construct()
    {
        // If we have access to the current route...
        if ($route = Route::current()) {
            // Get our controller.
            $controller = $route->controller;
        
            // If the controller has a doNotSanitize property,
            // add it to our exceptions array.
            if ($controller && property_exists($controller, 'doNotSanitize')) {
                if (is_array($controller->doNotSanitize)) {
                    $this->except = array_merge($this->except, $controller->doNotSanitize);
                }
            }
        }
    }
    
    /**
     * Sanitize the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        // If this is a string and is not in the exceptions
        // array, return it after sanitization.
        if (is_string($value) && !in_array($key, $this->except, true)) {
            // Remove most injectable code
            $value = strip_tags($value);
            
            // Return the sanitized string
            return $value;
        }
        
        // Return the original value.
        return $value;
    }
}
