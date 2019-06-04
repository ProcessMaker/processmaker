<?php

namespace ProcessMaker\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;
use ProcessMaker\Http\Controllers\Api\ScreenController;
use ProcessMaker\Models\Screen;
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
     * The tags that should be sanitized.
     *
     * @var array
     */
    public $backlist = [
        '<form>',
        '<input>',
        '<textarea>',
        '<button>',
        '<select>',
        '<option>',
        '<optgroup>',
        '<fieldset>',
        '<label>',
        '<output>',
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
    protected function transform($key, $value)
    {
        // If this is a string and is not in the exceptions
        // array, return it after sanitization.
        if (is_string($value) && !in_array($key, $this->except, true)) {
            // Remove most injectable code
            $value = strip_tags($value);

            // Return the sanitized string
            return $value;
        } elseif (is_string($value)) {
            // Remove tags in backlist
            foreach ($this->backlist as $tag) {
                $regexp = $this->convertTagToRegExp($tag);
                $value = preg_replace($regexp, '', $value);
            }
            return $value;
        }

        // Return the original value.
        return $value;
    }

    /**
     * Convert a <tag> into a regexp.
     *
     * @param string $tag
     *
     * @return string
     */
    private function convertTagToRegExp($tag)
    {
        return '/' . str_replace(['\<', '\>'], ['<[\s\/]*', '[^>]*>'], preg_quote($tag)) . '/i';
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
