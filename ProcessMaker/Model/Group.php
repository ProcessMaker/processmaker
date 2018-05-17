<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents an Eloquent model of a Group
 *
 * @package ProcessMaker\Model
 *
 * @property int id
 * @property string uid
 * @property string title
 * @property string status
 * @property string ldap_dn
 * @property string ux
 */
class Group extends Model
{

    // Specify our table and our primary key
    protected $table = 'groups';

    /**
     * Relation
     */
    const TYPE = 'GROUP';

    /**
     * values for status
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    /**
     * values for ux
     */
    const UX_NORMAL = 'NORMAL';
    const UX_MOBILE = 'SIMPLIFIED';
    const UX_SWITCHABLE = 'SWITCHABLE';
    const UX_SINGLE = 'SINGLE';

    protected $fillable = [
        'uid',
        'title',
        'status',
        'ldap_dn',
        'ux'
    ];

    protected $attributes = [
        'uid' => null,
        'title' => '',
        'status' => '',
        'ldap_dn' => self::STATUS_ACTIVE,
        'ux' => self::UX_NORMAL
    ];
    protected $casts = [
        'uid' => 'string',
        'title' => 'string',
        'status' => 'string',
        'ldap_dn' => 'string',
        'ux' => 'string'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'title' => 'required|unique:groups,title',
        'ldap_dn' => 'required',
        'ux' => 'required|in:' . self::UX_NORMAL . ',' . self::UX_MOBILE . ',' . self::UX_SWITCHABLE . ',' . self::UX_SINGLE,
        'status' => 'required|in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE
    ];

    /**
     * Returns the relationship of users that belong to this group
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user');
    }
}
