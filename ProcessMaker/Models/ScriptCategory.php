<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Script;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a business script category definition.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ScriptCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ScriptCategory",
 *   allOf={@OA\Schema(ref="#/components/schemas/ScriptCategoryEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ScriptCategory extends Model
{
    use SerializeToIso8601;
    use HideSystemResources;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'status',
        'is_system'
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('script_categories')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique, 'alpha_spaces'],
            'status' => 'required|string|in:ACTIVE,INACTIVE'
        ];
    }

    /**
     * Get scripts
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scripts()
    {
        return $this->morphedByMany(Script::class, 'assignable', 'category_assignments', 'category_id');
    }
}
