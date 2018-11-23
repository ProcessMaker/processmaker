<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\SerializeToIso8601;

class ProcessPermission extends Model
{
    use SerializeToIso8601;

    protected $fillable = [
        'process_id',
        'permission_id',
        'assignable_id',
        'assignable_type'
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
            'assignable_type' => 'required|in:' . User::class . ',' . Group::class
        ];
    }
}
