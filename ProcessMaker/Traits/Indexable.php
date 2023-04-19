<?php

namespace ProcessMaker\Traits;

use Facades\ProcessMaker\JsonColumnIndex;

trait Indexable
{
    public function getFieldsFromPmql($pmql)
    {
        return ExtendedPMQL::getFields($pmql);
    }

    public function addJsonColumnsIndex($fields)
    {
        foreach ($fields as $field) {
            $exploded = explode('.', $field);

            // Only columns in format data.field
            if (count($exploded) > 1) {
                $column = $exploded[0];
                array_shift($exploded);
                $path = '$.' . implode('.', $exploded);
                JsonColumnIndex::add($this->table, $column, $path);
            }
        }
    }
}
