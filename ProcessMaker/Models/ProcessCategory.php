<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Spatie\BinaryUuid\HasBinaryUuid;


class ProcessCategory extends Model
{
    use HasBinaryUuid;

    //values for status
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'name',
        'status'
    ];

    public static function rules($existing = null)
    {
        $rules = [
            'name' => 'required|string|max:100|unique:process_categories,name',
            'status' => 'required|string|in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE
        ];

        if ($existing) {
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                Rule::unique('process_categories')->ignore($existing->id)
            ];
        }

        return $rules;
    }

}
