<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *   schema="environment_variablesEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 * ),
 * @OA\Schema(
 *   schema="create_environment_variablesEditable",
 *   allOf={@OA\Schema(ref="#/components/schemas/environment_variablesEditable")},
 *   @OA\Property(property="value", type="string"),
 * ),
 * @OA\Schema(
 *   schema="environment_variables",
 *   allOf={@OA\Schema(ref="#/components/schemas/environment_variablesEditable")},
 *   @OA\Property(property="id", type="integer", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 *
 *
 *
 *
 */
class EnvironmentVariable extends Model
{
    protected $connection = 'processmaker';

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
        $validVariableName = '/^[a-zA-Z][a-zA-Z_$0-9]*$/';

        return [
            'description' => 'required',
            'value' => 'nullable',
            'name' => ['required', "regex:${validVariableName}", $unique],
        ];
    }

    public static function messages()
    {
        return [
            'name.regex' => trans('environmentVariables.validation.name.invalid_variable_name'),
        ];
    }
}
