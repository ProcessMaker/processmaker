<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a business process task assignment definition.
 *
 * @property string $id
 * @property string process_task_id
 * @property string assignment_id
 * @property string assignment_type
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessTaskAssignment extends Model
{    

    protected $fillable = [
        'process_id',
        'process_task_id',
        'assignment_id',
        'assignment_type'
    ];

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $ids = [
        'process_id',
        'assignment_id',
    ];

    /**
     * Validation rules
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'process_task_id' => 'required|exists:processes,id',
            'assignment_id' => 'required',
            'assignment_type' => 'required|in:USER,GROUP',
        ];
    }

}
