<?php
namespace ProcessMaker;

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
    public static function sanitizeData($data, $screen)
    {
        $except = self::getExceptions($screen);
        return self::sanitizeWithExceptions($data, $except);
    }

    private static function sanitizeWithExceptions(Array $data, Array $except, $level = 0)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeWithExceptions($value, $except, $level + 1);
            } else {
                // Only allow skipping on top-level data for now
                $strip_tags = $level !== 0 || !in_array($key, $except);
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

    private static function getRichTextElements($items)
    {
        $elements = [];
        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // Inside a table
                foreach ($item['items'] as $cell) {
                    if (is_array($cell)) {
                        $elements = array_merge($elements, self::getRichTextElements($cell));
                    }
                }
            } else {
                if (
                    isset($item['component']) &&
                    $item['component'] === 'FormTextArea' &&
                    isset($item['config']['richtext']) &&
                    $item['config']['richtext'] === true
                ) {
                    $elements[] = $item['config']['name'];
                }
            }
        }
        return $elements;
    }
}