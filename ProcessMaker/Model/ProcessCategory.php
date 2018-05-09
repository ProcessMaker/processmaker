<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 * Categories are used to classify and group similar processes within different
 * categories. Only one category may be assigned by process.
 *
 * @property int $CATEGORY_ID
 * @property string $CATEGORY_UID
 * @property string $CATEGORY_NAME
 * @property \Carbon\Carbon $CREATED_AT
 * @property \Carbon\Carbon $UPDATED_AT
 */
class ProcessCategory extends Model
{

    use ValidatingTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'PROCESS_CATEGORY';
    protected $primaryKey = 'CATEGORY_ID';

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'CATEGORY_NAME' => 'required|string|max:100|unique:PROCESS_CATEGORY,CATEGORY_NAME',
    ];

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'CREATED_AT';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'UPDATED_AT';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'CATEGORY_UID',
        'CATEGORY_NAME',
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'CATEGORY_UID'    => '',
        'CATEGORY_NAME'   => '',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'CATEGORY_UID'    => 'string',
        'CATEGORY_NAME'   => 'string',
        'CREATED_AT'      => 'datetime',
        'UPDATED_AT'      => 'datetime',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'CATEGORY_UID';
    }

    /**
     * Processes of the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        return $this->hasMany(Process::class, 'category_id', 'CATEGORY_ID');
    }
}
