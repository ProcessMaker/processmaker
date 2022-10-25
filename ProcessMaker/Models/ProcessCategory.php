<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Models\Process;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a business process category definition.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ProcessCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ProcessCategory",
 *   allOf={
 *      @OA\Schema(ref="#/components/schemas/ProcessCategoryEditable"),
 *      @OA\Schema(
 *          type = "object",
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *      )
 *   }
 * )
 */
class ProcessCategory extends ProcessMakerModel
{
    use SerializeToIso8601;
    use HideSystemResources;
    use Exportable;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'status',
        'is_system',
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('process_categories')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique, 'alpha_spaces'],
            'status' => 'required|string|in:ACTIVE,INACTIVE',
        ];
    }

    /**
     * Get processes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processes()
    {
        return $this->morphedByMany(Process::class, 'assignable', 'category_assignments', 'category_id');
    }
}
