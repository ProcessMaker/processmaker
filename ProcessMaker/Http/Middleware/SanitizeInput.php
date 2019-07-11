<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use ProcessMaker\Http\Controllers\Api\ScreenController;
use ProcessMaker\Models\Screen;
use ProcessMaker\SanitizeHelper;
use Route;

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
            if ($controller instanceof ScreenController) {
                $screen = isset($route->parameters['screen']) ? $route->parameters['screen'] : null;
                if ($screen && $screen instanceof Screen) {
                    $this->ScreenControllerRules($route, $screen);
                }
            }

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
    protected function cleanValue($key, $value)
    {
        // If this is a string and is not in the exceptions
        // array, return it after sanitization.
        return SanitizeHelper::sanitize($value, !in_array($key, $this->except, true));
    }

    /**
     * Rules to validate a Screen model
     *
     * @param \Illuminate\Routing\Route $route
     */
    private function ScreenControllerRules($route, Screen $screen)
    {
        if ($screen->type === 'DISPLAY') {
            // Add label to exception list
            $route->controller->doNotSanitize[] = 'label';
        }
    }
}
