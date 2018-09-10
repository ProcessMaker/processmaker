<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\BinaryUuid\HasBinaryUuid;

/**
 * Represents a business process category definition.
 *
 * @property string $uuid
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 */
class ProcessCategory extends Model
{
    use HasBinaryUuid;

    protected $fillable = [
        'name',
        'status'
    ];

    public static function rules($existing = null)
    {
        $rules = [
            'name' => 'required|string|max:100|unique:process_categories,name',
            'status' => 'required|string|in:ACTIVE,INACTIVE'
        ];

        if ($existing) {
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                Rule::unique('process_categories')->ignore($existing->uuid)
            ];
        }

        return $rules;
    }

}
