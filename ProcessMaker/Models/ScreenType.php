<?php

namespace ProcessMaker\Models;

/**
 * Represents a business screen Type definition.
 *
 * @property string $id
 * @property string $name
 *
 *  * @OA\Schema(
 *   schema="ScreenTypeEditable",
 *   @OA\Property(property="name", type="string"),
 * ),
 * @OA\Schema(
 *   schema="ScreenType",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/ScreenTypeEditable"),
 *       @OA\Schema(
 *          type="object",
 *          @OA\Property(property="id", type="string", format="id"),
 *       )
 *   },
 * )
 */
class ScreenType extends ProcessMakerModel
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public static function rules()
    {
        return [
            'name' => 'required|string|max:100|unique:screen_types,name,alpha_spaces',
        ];
    }
}
