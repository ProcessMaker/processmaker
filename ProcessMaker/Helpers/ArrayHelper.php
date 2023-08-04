<?php

namespace ProcessMaker\Helpers;

use ProcessMaker\Traits\FormatSecurityLogChanges;
use stdClass;

class ArrayHelper
{
    use FormatSecurityLogChanges;

    /**
     * This method parse and stdClass Object to an Associative Array
     * @param stdClass $parameter
     * @return array
     */
    public static function stdClassToArray(stdClass $parameter): array
    {
        $arrayConverted = json_decode(json_encode($parameter), true);

        return $arrayConverted;
    }

    /**
     * This method compares the keys of two arrays(new Values, old values) and adds (+) and (-) prefix
     * to the keys which are different between the arrays with "formatChanges" Trait function.
     * The method returns an array with different keys values, and new values only with (+) prefix.
     * @param array $changedArray
     * @param array $originalArray
     * @return array
     */
    public static function getArrayDifferencesWithFormat(array $changedArray, array $originalArray): array
    {
        $arrayDiff = [];
        $displayChanges = self::customArrayDiffAssoc($changedArray, $originalArray);
        $displayOriginal = self::customArrayDiffAssoc($originalArray, $changedArray);

        $arrayHelper = new self();
        $arrayDiff = $arrayHelper->formatChanges($displayChanges, $displayOriginal);

        return $arrayDiff;
    }

    /**
     * This method replace an old key with a new key in Array given.
     * @param array $array
     * @param string $oldKey
     * @param string $newKey
     * @return array
     */
    public static function replaceKeyInArray(array $array, string $oldKey, string $newKey)
    {
        if (array_key_exists($oldKey, $array)) {
            $array[$newKey] = $array[$oldKey];
            unset($array[$oldKey]);
        }

        return $array;
    }

    /**
     * This method is analogous to the function array_diff_assoc, with the difference 
     * that it supports the comparison of array-type elements in the content of the 
     * compared arrays.
     * 
     * @param array $array1
     * @param array $arrays
     * @return array
     */
    public static function customArrayDiffAssoc(array $array1, ...$arrays): array
    {
        if (empty($arrays) === 0) {
            return $array1;
        }
        $diff = [];
        foreach ($array1 as $key => $value) {
            $found = false;
            foreach ($arrays as $array) {
                $sw = is_array($array) && isset($array[$key]) && $array[$key] === $value;
                if ($sw) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }
}
