<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\SerializeToIso8601;
use Ramsey\Uuid\Uuid;

/**
 * Represents a group definition.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property string $notifiable_id
 * @property string $data
 * @property \Carbon\Carbon $read_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 *   @OA\Schema(
 *   schema="groupsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="groups",
 *   allOf={@OA\Schema(ref="#/components/schemas/groupsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Notification extends Model
{
    use SerializeToIso8601;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    /**
     * Boot function from laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::uuid4();
        });
    }

    public static function rules($existing = null)
    {
        $rules = [
            'notifiable_id' => 'required|integer',
            'notifiable_type' => 'required|string',
            'type' => 'required|string',
            'data' => 'required|string',
        ];

        if ($existing) {
//            $rules['name'] = [
//                'required',
//                'string',
//                Rule::unique('groups')->ignore($existing->id, 'id')
//            ];
        }

        return $rules;
    }
}
