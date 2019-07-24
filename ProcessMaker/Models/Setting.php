<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Class Settings
 *
 * @package ProcessMaker\Models
 *
 * @property string id
 * @property string key
 * @property array config
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @OA\Schema(
 *   schema="settingsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="key", type="string"),
 *   @OA\Property(property="config", type="string"),
 * ),
 * @OA\Schema(
 *   schema="settings",
 *   allOf={@OA\Schema(ref="#/components/schemas/settingsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class Setting extends Model
{
    use SerializeToIso8601;

    protected $connection = 'processmaker';

    protected $casts = [
        'config' => 'array',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
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
        $unique = Rule::unique('settings')->ignore($existing);

        return [
            'key' => ['required', $unique],
            'json' => 'required',
        ];
    }

}
