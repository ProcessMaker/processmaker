<?php

namespace ProcessMaker\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Class dynaform
 *
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property int process_id
 * @property string title
 * @property string description
 * @property array content
 * @property string label
 * @property Carbon type
 *
 */
class Dynaform extends Model
{
    use ValidatingTrait;
    use Uuid;

    protected $table = 'dynaform';

    protected $fillable = [
        'uid',
        'title',
        'description',
        'content',
        'label',
        'type',
        'created_at',
        'updated_at',
        'process_id',
    ];

    protected $attributes = [
        'uid' => null,
        'process_id' => '',
        'title' => null,
        'description' => null,
        'content' => null,
        'label' => null,
        'type' => 'form',
    ];

    protected $casts = [
        'uid' => 'string',
        'process_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'content' => 'array',
        'label' => 'string',
        'type' => 'string',
    ];

    protected $rules = [
        'uid' => 'required|max:36',
        'process_id' => 'exists:processes,id',
        'title' => 'required|unique:dynaform,title',
    ];

    protected $validationMessages = [
        'title.unique' => 'A Dynaform with the same name already exists in this process.'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Accessor content to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getContentAttribute($value): ?array
    {
        return json_decode($value, true);
    }

    /**
     * Mutator content json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = empty($value) ? null : json_encode($value);
    }

}
