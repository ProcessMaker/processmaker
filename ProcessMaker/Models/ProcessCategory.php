<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Process;

/**
 * Represents a business process category definition.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 *  * @OA\Schema(
 *   schema="ProcessCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ProcessCategory",
 *   allOf={@OA\Schema(ref="#/components/schemas/ProcessCategoryEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ProcessCategory extends Model
{    

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
