<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\BinaryUuid\HasBinaryUuid;


class EnvironmentVariable extends Model
{
    use HasBinaryUuid;

    public $incrementing = false;

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
        $rules = [
        'description' => 'nullable',
        'value' => 'nullable',
        ];
        if($existing) {
            $rules['name'] = [
                'required',
                'alpha_dash',
                Rule::unique('environment_variables')->ignore($existing->id)
            ];
        } else {
            $rules['name'] = 'required|alpha_dash|unique:environment_variables';
        }
        return $rules;
    }

}
