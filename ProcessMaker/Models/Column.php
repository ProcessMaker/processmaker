<?php

namespace ProcessMaker\Models;

class Column
{
    public $label;

    public $field;

    public $sortable;

    public $default;

    public $format;

    public $mask;

    public $isSubmitButton;

    /**
     * @OA\Schema(
     *   schema="columns",
     *   @OA\Property(property="label", type="string"),
     *   @OA\Property(property="field", type="string"),
     *   @OA\Property(property="sortable", type="boolean"),
     *   @OA\Property(property="default", type="boolean"),
     *   @OA\Property(property="format", type="string"),
     *   @OA\Property(property="mask", type="string"),
     * )
     */
    public function __construct($properties = [])
    {
        foreach ($properties as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
