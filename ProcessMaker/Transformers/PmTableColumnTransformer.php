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
        return [
            'fld_name' => $field->FLD_NAME,
            'fld_description' => $field->FLD_DESCRIPTION,
            'fld_type' => $field->FLD_TYPE,
            'fld_size' => $field->FLD_SIZE,
            'fld_null' => $field->FLD_NULL,
            'fld_auto_increment' => $field->FLD_AUTO_INCREMENT,
            'fld_key' => $field->FLD_KEY
        ];
    }
}
