<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

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
 */
class Media extends Model
{

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
