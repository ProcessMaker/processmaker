<?php

namespace ProcessMaker\Models;

use Spatie\MediaLibrary\Models\Media as Model;

/**
 * Represents media files stored in the database
 *
 * @property integer 'id',
 * @property integer 'model_id',
 * @property string 'model_type',
 * @property string 'collection_name',
 * @property string 'name',
 * @property string 'file_name',
 * @property string 'mime_type',
 * @property string 'disk',
 * @property string 'size',
 * @property string 'manipulations',
 * @property string 'custom_properties',
 * @property string 'responsive_images',
 * @property string 'order_column',
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="mediaEditable",
 *   @OA\Property(property="id", type="integer", format="id"),
 *   @OA\Property(property="model_id", type="integer", format="id"),
 *   @OA\Property(property="model_type", type="string", format="id"),
 *   @OA\Property(property="collection_name", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="file_name", type="string"),
 *   @OA\Property(property="mime_type", type="string"),
 *   @OA\Property(property="disk", type="string"),
 *   @OA\Property(property="size", type="integer"),
 *   @OA\Property(property="manipulations", type="string"),
 *   @OA\Property(property="custom_properties", type="string"),
 *   @OA\Property(property="responsive_images", type="array", items="string"),
 *   @OA\Property(property="order_column", type="integer"),
 * ),
 * @OA\Schema(
 *   schema="media",
 *   allOf={@OA\Schema(ref="#/components/schemas/mediaEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Media extends Model
{
    protected $connection = 'processmaker';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_id',
        'model_type',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'manipulations',
        'custom_properties',
        'responsive_images',
        'order_column',
    ];

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        return [
            'model_id' => 'required',
            'model_type' => 'required',
            'collection_name' => 'required',
            'name' => 'required',
            'file_name' => 'required',
            'mime_type' => 'required',
            'disk' => 'required',
            'size' => 'required',
            'manipulations' => 'required',
            'custom_properties' => 'required',
            'responsive_images' => 'required',
            'order_column' => 'required'
        ];
    }


    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $ids = [
        'model_id',
    ];

}
