<?php

namespace ProcessMaker\Helpers;

use Carbon\Carbon;

class DataTypeHelper
{
    private static function isDate($value)
    {
        if (is_string($value)) {
            if (strlen($value) > 5) {
                try {
                    $parsed = Carbon::parse($value);
                    if ($parsed->isMidnight()) {
                        return 'date';
                    } else {
                        return 'datetime';
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        
        return false;
    }
    
    private static function isInteger($value)
    {
        if (is_numeric($value)) {
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private static function isFloat($value)
    {
        if (is_numeric($value)) {
            if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function isBoolean($value)
    {
        if ($value === true || $value === false) {
            return true;
        }

        return false;
    }
    
    private static function isArray($value)
    {
        if (is_array($value)) {
            return true;
        }
        
        if (is_object($value)) {
            return true;
        }        
        
        try {
            $json = json_decode($value);
            if ($json !== null && is_numeric($json)) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return false;
    }
    
    private static function isPrimaryKey($key, $value)
    {
        $names = ['id', 'ID', '#'];
        return in_array($key, $names) && self::isTypeNumber($value);
    }

    public static function determineType($key, $value = null, $values = null)
    {
        if ($values !== null) {
            $types = [];

            $value = array_filter($values, function($item) {
                return $item !== null;
            });
            
            if (is_array($value) && count($value)) {
                foreach ($value as $singleValue) {
                    $type = self::determineType($key, $singleValue);
                    isset($types[$type]) ? $types[$type]++ : $types[$type] = 1;
                }
                arsort($types);
                return array_key_first($types);
            }
            
            return 'string';
        } elseif ($value !== null) {       
            if (self::isInteger($value)) return 'int';
            if (self::isFloat($value)) return 'float';
            if (self::isBoolean($value)) return 'boolean';
            if (self::isArray($value)) return 'array';
            if ($date = self::isDate($value)) return $date;
        }
        
        return 'string';
    }
}
