<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Scopes\NotificationWithValidJson;
use ProcessMaker\Traits\SerializeToIso8601;
use Ramsey\Uuid\Uuid;

/**
 * Represents a notification definition.
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
 * @OA\Schema(
 *   schema="NotificationEditable",
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="notifiable_type", type="string"),
 *   @OA\Property(property="notifiable_id", type="integer"),
 *   @OA\Property(property="data", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="message", type="string"),
 *   @OA\Property(property="processName", type="string"),
 *   @OA\Property(property="userName", type="string"),
 *   @OA\Property(property="request_id", type="string"),
 *   @OA\Property(property="url", type="string"),
 * ),
 * @OA\Schema(
 *   schema="Notification",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/NotificationEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="string"),
 *       @OA\Property(property="read_at", type="string", format="date-time"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     ),
 *   },
 * )
 */
class Notification extends ProcessMakerModel
{
    use SerializeToIso8601;

    public $incrementing = false;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'url',
    ];

    /**
     * Boot function from laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
        });

        // The rest of the app expects that data is a json string, all notification uses this format.
        // We need to exclude any spurious notification that does not have a valid json data
        static::addGlobalScope(new NotificationWithValidJson);
    }

    public static function rules($existing = null)
    {
        $required = Rule::requiredIf(function () use ($existing) {
            return $existing === null;
        });

        $rules = [
            'notifiable_id' => [$required, 'integer'],
            'notifiable_type' => [$required, 'string'],
            'type' => [$required, 'string'],
            'data' => [$required, 'string'],
        ];

        return $rules;
    }
}
