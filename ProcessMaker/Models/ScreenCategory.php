<?php

namespace ProcessMaker\Models;

use Illuminate\Validation\Rule;
use ProcessMaker\Models\Screen;
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
class ScreenCategory extends ProcessMakerModel
{
    use SerializeToIso8601;
    use HideSystemResources;
    use Exportable;

    protected $fillable = [
        'name',
        'status',
        'is_system',
    ];

    public static function rules($existing = null)
    {
        $unique = Rule::unique('screen_categories')->ignore($existing);

        return [
            'name' => ['required', 'string', 'max:100', $unique, 'alpha_spaces'],
            'status' => 'required|string|in:ACTIVE,INACTIVE',
        ];
    }

    /**
     * Get screens
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function screens()
    {
        return $this->morphedByMany(Screen::class, 'assignable', 'category_assignments', 'category_id');
    }

    /**
     * Get Screen Category Names
     * @param string String of ids separated by a custom delimiter.
     * @param string Delimiter to split ids. By default ','
     * @return string A string separated by commas with Screen Category Names
     */
    public static function getNamesByIds(string $ids, string $delimiter = ','): string
    {
        $resultString = '';
        $arrayIds = explode($delimiter, $ids);
        $results = self::whereIn('id', array_map('intval', $arrayIds))->pluck('name');
        $resultString = implode(', ', $results->toArray());

        return $resultString;
    }
}
