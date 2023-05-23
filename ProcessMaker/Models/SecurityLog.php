<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Traits\ExtendedPMQL;

/**
 * Class SecurityLog
 *
 *
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @OA\Schema(
 *   schema="securityLog",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="event", type="string"),
 *   @OA\Property(property="ip", type="string"),
 *   @OA\Property(property="meta", type="array",
 *      @OA\Items(type="object",
 *         @OA\Property(property="os", type="array",
 *              @OA\Items(type="object",
 *                  @OA\Property(property="name", type="string"),
 *                  @OA\Property(property="version", type="string"),
 *              ),
 *         ),
 *         @OA\Property(property="browser", type="array",
 *              @OA\Items(type="object",
 *                  @OA\Property(property="name", type="string"),
 *                  @OA\Property(property="version", type="string"),
 *              ),
 *         ),
 *         @OA\Property(property="user_agent", type="string"),
 *      ),
 *    ),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="occured_at", type="string"),
 * ),
 */
class SecurityLog extends ProcessMakerModel
{
    use ExtendedPMQL;

    const CREATED_AT = 'occurred_at';

    const UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'object',
        'changes' => 'object',
        'meta' => 'object',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['changes'];

    /**
     * Get the associated user, if any.
     */
    public function user()
    {
        return $this->belongsTo('ProcessMaker\Models\User');
    }

    /**
     * PMQL value alias for fulltext field
     *
     * @param string $value
     *
     * @return callable
     */
    public function valueAliasFullText($value, $expression)
    {
        return function ($query) use ($value) {
            $this->scopeFilter($query, $value);
        };
    }

    /**
     * Filter settings with a string
     *
     * @param $query
     *
     * @param $filter string
     */
    public function scopeFilter($query, $filter)
    {
        $filter = '%' . mb_strtolower($filter) . '%';

        $query->where(function ($query) use ($filter) {
            $query->where(DB::raw('LOWER(`event`)'), 'like', $filter)
                ->orWhere(DB::raw('LOWER(`meta`)'), 'like', $filter)
                ->orWhere('ip', 'like', $filter);
        });

        return $query;
    }

    public static function rules()
    {
        return [
            'event' => ['required', 'max:40'],
            'ip' => ['required', 'max:40'],
            'meta' => ['required', 'array'],
            'meta.user_agent' => ['required', 'string'],
            'meta.browser.name' => ['required', 'string'],
            'meta.browser.version' => ['nullable', 'string'],
            'meta.os.name' => ['required', 'string'],
            'meta.os.version' => ['nullable', 'string'],
            'user_id' => ['required', 'int'],
            'occurred_at' => ['required', 'int'],
            'data' => ['nullable', 'array'],
            'changes' => ['nullable', 'array'],
        ];
    }
}
