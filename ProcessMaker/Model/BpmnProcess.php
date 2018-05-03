<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

/**
 *
 * @property string $PRO_UID
 * @property string $PRJ_UID
 * @property string $DIA_UID
 * @property string $PRO_NAME
 * @property string $PRO_TYPE
 * @property int $PRO_IS_EXECUTABLE
 * @property int $PRO_IS_CLOSED
 * @property int $PRO_IS_SUBPROCESS
 */
class BpmnProcess extends Model
{

    use ValidatingTrait;
    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'BPMN_PROCESS';
    protected $primaryKey = 'PRO_UID';

    const PROCESS_TYPE_NONE = 'NONE';
    const PROCESS_TYPE_PRIVATE = 'PRIVATE';
    const PROCESS_TYPE_PUBLIC = 'PUBLIC';

    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'PRO_UID',
        'PRJ_UID',
        'DIA_UID',
        'PRO_NAME',
        'PRO_TYPE',
        'PRO_IS_EXECUTABLE',
        'PRO_IS_CLOSED',
        'PRO_IS_SUBPROCESS'
    ];

    /**
     * The model's attributes.
     *
     * @var array $attributes
     */
    protected $attributes = [
        'PRO_UID'           => '',
        'PRJ_UID'           => null,
        'DIA_UID'           => null,
        'PRO_NAME'          => null,
        'PRO_TYPE'          => self::PROCESS_TYPE_NONE,
        'PRO_IS_EXECUTABLE' => false,
        'PRO_IS_CLOSED'     => false,
        'PRO_IS_SUBPROCESS' => false
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array $casts
     */
    protected $casts = [
        'PRO_UID'           => 'string',
        'PRJ_UID'           => 'string',
        'DIA_UID'           => 'string',
        'PRO_NAME'          => 'string',
        'PRO_TYPE'          => 'string',
        'PRO_IS_EXECUTABLE' => 'boolean',
        'PRO_IS_CLOSED'     => 'boolean',
        'PRO_IS_SUBPROCESS' => 'boolean'
    ];

    /**
     * Validation rules.
     *
     * @var array $rules
     */
    protected $rules = [
        'PRO_UID'           => 'required|max:32',
        'PRJ_UID'           => 'required|max:32',
        'DIA_UID'           => 'nullable|max:32',
        'PRO_NAME'          => 'required|max:255',
        'PRO_TYPE'          => 'required|in:' . self::PROCESS_TYPE_NONE . ','
        . self::PROCESS_TYPE_PRIVATE . ',' . self::PROCESS_TYPE_PUBLIC,
        'PRO_IS_EXECUTABLE' => 'required|boolean',
        'PRO_IS_CLOSED'     => 'required|boolean',
        'PRO_IS_SUBPROCESS' => 'required|boolean'
    ];

}
