<?php

namespace ProcessMaker\Models;

use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a Process permission.
 *
 * @property int $id
 * @property int $process_id
 * @property int $permission_id
 * @property int $assignable_id
 * @property string $assignable_type
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *    schema="processPermissionsEditable",
 *    @OA\Property(property="id", type="integer", format="id"),
 *    @OA\Property(property="process_id", type="integer", format="id"),
 *    @OA\Property(property="permission_id", type="integer", format="id"),
 *    @OA\Property(property="assignable_id", type="integer", format="id"),
 *    @OA\Property(property="assignable_type", type="string"),
 * ),
 * @OA\Schema(
 *    schema="processPermissions",
 *    allOf={@OA\Schema(ref="#/components/schemas/processPermissionsEditable")},
 *    @OA\Property(property="created_at", type="string", format="date-time"),
 *    @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ProcessPermission extends ProcessMakerModel
{
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    protected $fillable = [
        'process_id',
        'permission_id',
        'assignable_id',
        'assignable_type',
    ];

    public function assignable()
    {
        return $this->morphTo(null, null, 'assignable_id');
    }

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'process_id' => 'nullable|exists:processes,id',
            'permission_id' => 'nullable|exists:permissions,id',
            'assignable_id' => 'required',
            'assignable_type' => 'required|in:' . User::class . ',' . Group::class,
        ];
    }
}
