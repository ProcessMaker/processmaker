<?php

namespace ProcessMaker;

use Illuminate\Support\Facades\Validator;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Screen;

class SanitizeHelper
{
    /**
     * The tags that should always be sanitized, even
     * when the controller specifies doNotSanitize.
     *
     * @var array
     */
    private static $blacklist = [
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
     * Sanitize the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  bool $strip_tags
     * @return mixed
     */
    public static function sanitize($value, $strip_tags = true, $sanitizeExpressions = true)
    {
        if (is_string($value) && $strip_tags) {
            // Remove most injectable code
            $value = self::strip_tags($value);
            if ($sanitizeExpressions) {
                $value = self::sanitizeVueExp($value);
            }

            // Return the sanitized string
            return $value;
        } elseif (is_string($value)) {
            // Remove tags in blacklist, even if $strip_tags is false
            foreach (self::$blacklist as $tag) {
                $regexp = self::convertTagToRegExp($tag);
                $value = preg_replace($regexp, '', $value);
            }

            return $value;
        }

        // Return the original value.
        return $value;
    }

    /**
     * Strip php and html tags
     *
     * @param string $string
     *
     * @return string
     */
    public static function strip_tags($string)
    {
        // strip server side tags
        $string = preg_replace('/(<\?((?!\?>)[\w\W])+\?>)/', '', $string);
        // strip html comments
        $string = preg_replace('/(<!--((?!-->)[\w\W])+-->)/', '', $string);
        // strip html tags
        $string = preg_replace('/<[a-zA-Z0-9]+[^>]*>/', '', $string);
        $string = preg_replace('/<\/[a-zA-Z0-9]+[^>]*>/', '', $string);
        $string = preg_replace('/<[a-zA-Z0-9]+[^>]*\/>/', '', $string);

        return $string;
    }

    /**
     * Convert a <tag> into a regexp.
     *
     * @param string $tag
     *
     * @return string
     */
    private static function convertTagToRegExp($tag)
    {
        return '/' . str_replace(['\<', '\>'], ['<[\s\/]*', '[^>]*>'], preg_quote($tag)) . '/i';
    }

    /**
     * Sanitize each element of an array. Do not sanitize rich text elements
     *
     * @param array $data
     * @param Task $task
     * @param array $except
     *
     * @return array
     */
    public static function sanitizeData($data, $task = null, $except = [])
    {
        if ($task) {
            // Get current and nested screens IDs ..
            $currentScreenExceptions = [];
            $currentScreenAndNestedIds = $task->getScreenAndNestedIds();
            foreach ($currentScreenAndNestedIds as $id) {
                // Find the screen version ..
                $screen = Screen::findOrFail($id);
                $screen = $screen->versionFor($task->processRequest)->toArray();
                // Get exceptions ..
                $exceptions = self::getExceptions((object) $screen);
                if (count($exceptions)) {
                    $currentScreenExceptions = array_unique(array_merge($exceptions, $currentScreenExceptions));
                }
            }

            // Get process request exceptions stored in do_not_sanitize column ..
            $processRequestExceptions = $task->processRequest->do_not_sanitize;
            if (!$processRequestExceptions) {
                $processRequestExceptions = [];
            }

            // Merge (nestedSreensExceptions and currentScreenExceptions) with processRequestExceptions ..
            $exceptTask = array_unique(array_merge($processRequestExceptions, $currentScreenExceptions));
            $except = array_unique(array_merge($except, $exceptTask));
        }

        return self::sanitizeWithExceptions($data, $except);
    }

    /**
     * Get exceptions for a screen. Used for web entry start events when no task exists yet.
     *
     * @param Screen $screen
     *
     * @return array
     */
    public static function getExceptionsForScreen(Screen $screen)
    {
        $screens = collect([$screen]);
        $nestedScreens = collect($screen->nestedScreenIds())->map(fn ($id) => Screen::findOrFail($id));
        $screens = $screens->concat($nestedScreens);

        return $screens->flatMap(fn ($screen) => self::getExceptions($screen))->toArray();
    }

    public static function sanitizeWithExceptions(array $data, array $except, $parent = null)
    {
        foreach ($data as $key => $value) {
            if (!is_int($key)) {
                $searchKey = ($parent ? $parent . '.' . $key : $key);
            } else {
                $searchKey = $parent;
            }
            if (is_array($value)) {
                $data[$key] = self::sanitizeWithExceptions($value, $except, $searchKey);
            } else {
                // Only allow skipping on top-level data for now
                $strip_tags = !in_array($searchKey, $except);
                $data[$key] = self::sanitize($value, $strip_tags);
            }
        }

        return $data;
    }

    private static function getExceptions($screen)
    {
        $except = [];
        if (!$screen) {
            return $except;
        }
        $config = $screen->config;

        if ($config) {
            foreach ($config as $page) {
                if (isset($page['items']) && is_array($page['items'])) {
                    $except = array_merge($except, self::getRichTextElements($page['items']));
                }
            }
        }

        return $except;
    }

    private static function getRichTextElements($items, $parent = null, &$elements = [])
    {
        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // Inside loop ..
                if ($item['component'] == 'FormLoop') {
                    self::getRichTextElements(
                        $item['items'],
                        ($parent ? $parent . '.' . $item['config']['name'] : $item['config']['name']),
                        $elements
                    );
                } elseif ($item['component'] == 'FormMultiColumn') {
                    self::getVariableMultiColumn($item, $parent, $elements);
                } else {
                    self::getVariableExceptions($item, $parent, $elements);
                }
            } else {
                self::getVariableExceptions($item, $parent, $elements);
            }
        }

        return $elements;
    }

    private static function getVariableMultiColumn($item, $parent, &$elements)
    {
        foreach ($item['items'] as $cell) {
            self::getVariableExceptions($cell, $parent, $elements);
            if (is_array($cell)) {
                self::getRichTextElements($cell, $parent, $elements);
            }
        }
    }

    private static function getVariableExceptions($item, $parent, &$elements)
    {
        if (!isset($item['component'])) {
            return;
        }
        if (self::renderHtmlIsEnabled($item, 'FormTextArea', 'richtext')) {
            $elements[] = ($parent ? $parent . '.' . $item['config']['name'] : $item['config']['name']);
        } elseif (self::renderHtmlIsEnabled($item, 'FormHtmlViewer', 'renderVarHtml')) {
            preg_match_all("/{{([^{}]*)}}/", $item['config']['content'], $matches);
            if ($matches && $matches[1]) {
                foreach ($matches[1] as $variable) {
                    $elements[] = ($parent ? $parent . '.' . $variable : $variable);
                }
            }
        }
    }

    private static function renderHtmlIsEnabled($item, $type, $field)
    {
        return isset($item['config'])
            && $item['component'] === $type
            && isset($item['config'][$field])
            && $item['config'][$field] === true;
    }

    public static function sanitizeEmail($email)
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return '';
        } else {
            return $email;
        }
    }

    public static function sanitizePhoneNumber($number)
    {
        $regexp = "/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/";
        if (preg_match($regexp, $number)) {
            return $number;
        } else {
            return '';
        }
    }

    public static function sanitizeVueExp($string)
    {
        // strip {{, }}
        $codes = [
            '{{' => '',
            '}}' => '',
        ];

        return str_replace(array_keys($codes), array_values($codes), $string);
    }

    public static function getDoNotSanitizeFields($process)
    {
        $manager = app(ExportManager::class);
        $screenIds = $manager->getDependenciesOfType(Screen::class, $process, []);
        $doNotSanitizeFields = [];

        foreach ($screenIds as $screenId) {
            $doNotSanitizeFieldsForScreen = self::findFieldsInScreen($screenId);
            $doNotSanitizeFields = array_unique(array_merge($doNotSanitizeFieldsForScreen, $doNotSanitizeFields));
        }

        return $doNotSanitizeFields;
    }

    public static function findFieldsInScreen($screenId)
    {
        $screen = Screen::find($screenId);
        $doNotSanitizeFields = [];
        if ($screen) {
            $doNotSanitizeFields = self::getExceptions((object) $screen);
        }

        return $doNotSanitizeFields;
    }
}
