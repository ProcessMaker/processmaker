<?php

namespace ProcessMaker\Helpers;

use stdClass;

class ArrayHelper
{
    /**
     * @param stdClass $parameter
     * @return array
     */
    public static function stdClassToArray(stdClass $parameter): array
    {
        $arrayConverted = json_decode(json_encode($parameter), true);

        return $arrayConverted;
    }
}
