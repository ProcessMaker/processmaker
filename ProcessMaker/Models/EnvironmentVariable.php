<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *   schema="environment_variablesEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="value", type="string"),
 * ),
 * @OA\Schema(
 *   schema="environment_variables",
 *   allOf={@OA\Schema(ref="#/components/schemas/environment_variablesEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 */
class EnvironmentVariable extends Model
{

    protected $fillable = [
        'name',
        'description',
        'value'
    ];

    protected $hidden = [
        'value'
    ];

    /**
     * Store the encrypted version of the variable value here
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = encrypt($value);
    }

    /**
     * Fetch the plain text version of the value
     */
    public function getValueAttribute()
    {
        return decrypt($this->attributes['value']);
    }

    public static function rules($existing = null)
    {
        $unique = Rule::unique('environment_variables')->ignore($existing);

        return [
            'description' => 'required',
            'value' => 'nullable',
            'name' => ['required', 'alpha_dash', $unique]
        ];
    }

}
