<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;

/**
 * Transformer for the the fields of a pmTable or report table
 */
class PmTableColumnTransformer extends TransformerAbstract
{
    /**
     * Transform the fields of a PmTable model to format used in api
     * @return array Associative array of expected fields
     */
    public function transform($field)
    {
        return (array)$field;
    }
}
