<?php

namespace ProcessMaker\Models;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use ProcessMaker\Traits\Exportable;

/**
 * @OA\Schema(
 *   schema="EnvironmentVariableEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="value", type="string"),
 * ),
 * @OA\Schema(
 *   schema="EnvironmentVariable",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/EnvironmentVariableEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="integer", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     ),
 *   },
 * )
 */
class EnvironmentVariable extends ProcessMakerModel
{
    use Exportable;

    protected $fillable = [
        'name',
        'description',
        'value',
    ];

    protected $hidden = [
        'value',
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
        try {
            return decrypt($this->attributes['value']);
        } catch (Exception $e) {
            Log::error(
                'Can not decrypt environment variable: ' .
                $this->attributes['name'] .
                "\n" . $e->getMessage() .
                "\n" . $e->getTraceAsString()
            );

            return null;
        }
    }

    public static function rules($existing = null)
    {
        $unique = Rule::unique('environment_variables')->ignore($existing);
        $validVariableName = '/^[a-zA-Z][a-zA-Z_$0-9]*$/';

        return [
            'description' => 'required',
            'value' => 'nullable',
            'name' => ['required', "regex:{$validVariableName}", $unique],
        ];
    }

    public static function messages()
    {
        return [
            'name.regex' => trans('environmentVariables.validation.name.invalid_variable_name'),
        ];
    }
}
