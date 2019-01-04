<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Screen;
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
 *  * @OA\Schema(
 *   schema="ScreenCategoryEditable",
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
 * ),
 * @OA\Schema(
 *   schema="ScreenCategory",
 *   allOf={@OA\Schema(ref="#/components/schemas/ScreenCategoryEditable")},
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class ScreenCategory extends Model
{
    use SerializeToIso8601;

    protected $fillable = [
        'name',
        'status'
    ];

    public static function rules()
    {
        return [
            'name' => 'required|string|max:100|unique:screen_categories,name',
            'status' => 'required|string|in:ACTIVE,INACTIVE'
        ];
    }

    /**
     * Get screens
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function screens()
    {
        return $this->hasMany(Screen::class);
    }
}
