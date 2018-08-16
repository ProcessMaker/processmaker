<?php

namespace ProcessMaker\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Model\Traits\Uuid;
use Watson\Validating\ValidatingTrait;

/**
 * Class Form
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
class Form extends Model
{
    use ValidatingTrait;
    use Uuid;

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

    protected $rules = [
        'uid' => 'required|max:36',
        'process_id' => 'exists:processes,id'
    ];

    protected $validationMessages = [
        'title.unique' => 'A Form with the same name already exists in this process.'
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
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = empty($value) ? null : json_encode($value);
    }

    /**
     * Accessor Label to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getLabelAttribute($value): ?array
    {
        return json_decode($value, true);
    }

    /**
     * Mutator Label json decode
     *
     * @param $value
     */
    public function setLabelAttribute($value)
    {
        $this->attributes['label'] = empty($value) ? null : json_encode($value);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
