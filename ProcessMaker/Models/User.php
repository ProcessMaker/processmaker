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

    protected $dates = [
        'loggedin_at'
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

    public function members()
    {
        // return $this->hasMany(GroupMember::class);
        return GroupMember::where(['member_type' => self::class, 'member_uuid' => $this->uuid])->get();
    }

    public function memberships()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_uuid');
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
}
