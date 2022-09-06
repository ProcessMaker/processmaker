<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\ExtendedPMQL;

class SecurityLog extends Model
{
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
    use ExtendedPMQL;

    const CREATED_AT = 'occurred_at';

    const UPDATED_AT = null;

    protected $connection = 'data';

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
        'meta' => 'object',
    ];

    /**
     * Get the associated user, if any.
     */
    public function user()
    {
        return $this->belongsTo('ProcessMaker\Models\User');
    }
}
