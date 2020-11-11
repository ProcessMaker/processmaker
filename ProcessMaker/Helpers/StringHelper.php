<?php
namespace ProcessMaker\Helpers;

class StringHelper
{
    public static function normalize($string)
    {
        $string = static::unSnakeSlug($string);
        $string = static::unStudlyCamelCase($string);
        $string = static::removeExtraWhitespace($string);
        $string = mb_convert_case($string, MB_CASE_TITLE);
        
        return $string;
    }
    
    public static function unSnakeSlug($string)
    {
        return trim(preg_replace('/[_-]/', " ", $string));
    }
    
    public static function unStudlyCamelCase($string)
    {
        return trim(preg_replace('/([A-Z])/', " $1", $string));
    }
    
    public static function removeExtraWhitespace($string)
    {
        return trim(preg_replace('/\s{2,}/', " ", $string));
    }
}
