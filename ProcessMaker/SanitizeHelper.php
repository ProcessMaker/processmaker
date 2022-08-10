<?php
namespace ProcessMaker;

use Illuminate\Support\Facades\Validator;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use function GuzzleHttp\json_decode;

class SanitizeHelper {
    /**
     * The tags that should always be sanitized, even
     * when the controller specifies doNotSanitize
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
     * @param  boolean $strip_tags
     * @return mixed
     */
    public static function sanitize($value, $strip_tags = true)
    {
        if (is_string($value) && $strip_tags) {
            // Remove most injectable code
            $value = self::strip_tags($value);
            $value = self::sanitizeVueExp($value);

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
     * @param string $tag
     *
     * @return string
     */
    public static function sanitizeData($data, $task)
    {
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
        $except = array_unique(array_merge($processRequestExceptions, $currentScreenExceptions));

        // Update database with all the exceptions ..
        $task->processRequest->do_not_sanitize = $except;
        $task->processRequest->save();

        return self::sanitizeWithExceptions($data, $except);
    }

    private static function sanitizeWithExceptions(Array $data, Array $except, $parent = null)
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
        foreach ($config as $page) {
            if (isset($page['items']) && is_array($page['items'])) {
                $except = array_merge($except, self::getRichTextElements($page['items']));
            }
        }
        return $except;
    }

    private static function getRichTextElements($items, $parent = null)
    {
        $elements = [];

        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // Inside loop ..
                if ($item['component'] == 'FormLoop') {
                    $elements = array_merge($elements, self::getRichTextElements($item['items'], ($parent ? $parent . '.' . $item['config']['name'] : $item['config']['name'])));
                } else if (isset($item['component']) && $item['component'] === 'FormTextArea' && isset($item['config']['richtext']) && $item['config']['richtext'] === true) {
                    $elements[] = ($parent ? $parent . '.' . $item['config']['name'] : $item['config']['name']);
                // Inside a table ..
                } else if ($item['component'] == 'FormMultiColumn') {
                    foreach ($item['items'] as $cell) {
                        if (
                            isset($cell['component']) &&
                            $cell['component'] === 'FormTextArea' &&
                            isset($cell['config']['richtext']) &&
                            $cell['config']['richtext'] === true
                        ) {
                            $elements[] = $cell['config']['name'];
                        }
                        if (is_array($cell)) {
                            $elements = array_merge($elements, self::getRichTextElements($cell));
                        }
                    }
                }
            } else {
                if (isset($item['component']) && $item['component'] === 'FormTextArea' && isset($item['config']['richtext']) && $item['config']['richtext'] === true) {
                    $elements[] = ($parent ? $parent . '.' . $item['config']['name'] : $item['config']['name']);
                }
            }
        }

        return $elements;
    }

    public static function sanitizeEmail($email)
    {
        $validator = Validator::make(['email' => $email], [
            'email'=>'required|email'
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
}
