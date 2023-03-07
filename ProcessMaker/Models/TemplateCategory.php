<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Models\Template;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

/**
 * Represents a business screen category definition.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="ScreenCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ScreenCategory",
 *   allOf={
 *      @OA\Schema(ref="#/components/schemas/ScreenCategoryEditable"),
 *      @OA\Schema(
 *          @OA\Property(property="id", type="string", format="id"),
 *          @OA\Property(property="created_at", type="string", format="date-time"),
 *          @OA\Property(property="updated_at", type="string", format="date-time"),
 *      )
 *   },
 * )
 */
class TemplateCategory extends ProcessMakerModel
{
    use SerializeToIso8601;
    use HideSystemResources;
    use Exportable;

    protected $connection = 'processmaker';

    protected $fillable = [
        'name',
        'status',
        'is_system',
        'description',
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('template_categories')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique, 'alpha_spaces'],
            'status' => 'required|string|in:ACTIVE,INACTIVE',
        ];
    }

    /**
     * Get template
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function template()
    {
        return $this->morphedByMany(Template::class, 'assignable', 'category_assignments', 'category_id');
    }
}
