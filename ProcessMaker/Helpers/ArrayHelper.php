<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\Schema;
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
        $displayChanges = array_diff_assoc($changedArray, $originalArray);
        $displayOriginal = array_diff_assoc($originalArray, $changedArray);

        $arrayHelper = new ArrayHelper();
        $arrayDiff = $arrayHelper->formatChanges($displayChanges, $displayOriginal);
        return $arrayDiff;
    }

    /**
     * This method swaps groups of id numbers "1,2,3" for specific column names values "'A','B','C'" 
     * using the Tables information through Models
     * The method returns a String value
     * The packageName parameter is optional, only use with Packages. Default value Null
     * @param string $modelName
     * @param string $ids
     * @param string $columnName
     * @param string $packageName
     * @return string
     */
    public static function getNamesByIds(string $modelName, string $ids, string $columnName, string $packageName = null): string
    {
        $arrayIds = explode(',', $ids);
        $resultString = '';
        if (is_null($packageName)) {
            $modelClass = 'ProcessMaker\\Models\\' . $modelName;
        } else {
            $modelClass = 'ProcessMaker\\Package\\' . $packageName . '\\Models\\' . $modelName;
        }

        if (class_exists($modelClass)) {
            $tableName = (new $modelClass)->getTable();
            if((Schema::hasColumn($tableName, $columnName))){
                $results = $modelClass::whereIn('id', array_map('intval', $arrayIds))->pluck($columnName);
                $resultString = implode(', ', $results->toArray());
            }
        }
        return $resultString;
    }
}
