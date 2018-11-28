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
 * @OA\Schema(
 *   schema="taskAssignmentsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="process_id", type="string", format="id"),
 *   @OA\Property(property="process_task_id", type="string", format="id"),
 *   @OA\Property(property="assignment_id", type="string", format="id"),
 *   @OA\Property(property="assignment_type", type="string", enum={"ProcessMaker\Models\User", "ProcessMaker\Models\Group"})
 * ),
 * @OA\Schema(
 *   schema="taskAssignments",
 *   allOf={@OA\Schema(ref="#/components/schemas/taskAssignmentsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
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
            'process_id' => 'required|exists:processes,id',
            'assignment_id' => 'required',
            'assignment_type' => 'required|in:' . User::class . ',' . Group::class
        ];
    }

    /**
     * Assigned user or group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function assigned()
    {
        return $this->morphTo('assigned', 'assignment_type', 'assignment_id');
    }
}
