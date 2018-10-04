<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Process;
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

    public static function rules()
    {
        $rules = [
            'name' => 'required|string|max:100|unique_in_model',
            'status' => 'required|string|in:ACTIVE,INACTIVE'
        ];

        return $rules;
    }

    /**
     * Get processes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        return $this->hasMany(Process::class);
    }
}
