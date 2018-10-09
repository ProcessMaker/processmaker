<?php

namespace ProcessMaker\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Spatie\BinaryUuid\HasBinaryUuid;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;
    use Notifiable;
    use HasBinaryUuid;
    use HasMediaTrait;

    //Disk
    public const DISK_PROFILE = 'profile';
    //collection media library
    public const COLLECTION_PROFILE = 'profile';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'firstname',
        'lastname',
        'status',
        'address',
        'city',
        'state',
        'postal',
        'country',
        'phone',
        'fax',
        'cell',
        'title',
        'birthdate',
        'timezone',
        'language',
        'expires_at'

    ];

    protected $guarded = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'fullname',
        'avatar',
    ];

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $rules = [
            'username' => 'required|unique:users,username',
            'email' => 'required|email',
        ];
        if ($existing) {
            // ignore the unique rule for this id
            $rules['username'] .= ',' . $existing->uuid . ',uuid';
        }
        return $rules;
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Return the full name for this user which is the first name and last
     * name separated with a space.
     *
     * @return string
     */
    public function getFullName()
    {
        return implode(" ", [
            $this->firstname,
            $this->lastname
        ]);
    }

    public function groupMembers()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_uuid');
    }

    public function permissionAssignments()
    {
        return $this->morphMany(PermissionAssignment::class, 'assignable', null, 'assignable_uuid');
    }

    /**
     * Get the full name as an attribute.
     *
     * @return string
     */
    public function getFullnameAttribute() {
        return $this->getFullName();
    }

    /**
     * Get the avatar URL
     *
     * @return string
     */
    public function getAvatarAttribute() {
        return $this->getAvatar();
    }

    /**
     * Get url Avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        $mediaFile = $this->getMedia(self::COLLECTION_PROFILE);
        $url = '';
        foreach ($mediaFile as $media) {
            $url = $media->getFullUrl();
        }
        return $url;
    }

    public function hasPermission($permissionString)
    {
        // TODO: cache
        $permissions = [];
        foreach ($this->groupMembers as $gm) {
            $group = $gm->group;
            $permissions =
                array_merge($permissions, $group->permissions());
        }
        foreach ($this->permissionAssignments as $pa) {
            $permissions[] = $pa->permission;
        }
        
        $permissionStrings = array_map(
            function($p) { return $p->guard_name; },
            $permissions
        );
        eval(\Psy\sh());
        return in_array($permissionString, $permissionStrings);
    }
}
