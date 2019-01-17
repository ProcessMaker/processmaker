<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use ProcessMaker\Models\Permission;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\HasAuthorization;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;
    use Notifiable;
    use HasMediaTrait;
    use HasAuthorization;
    use SerializeToIso8601;

    //Disk
    public const DISK_PROFILE = 'profile';
    //collection media library
    public const COLLECTION_PROFILE = 'profile';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     *
     * @OA\Schema(
     *   schema="usersEditable",
     *   @OA\Property(property="email", type="string", format="email"),
     *   @OA\Property(property="password", type="string"),
     *   @OA\Property(property="firstname", type="string"),
     *   @OA\Property(property="lastname", type="string"),
     *   @OA\Property(property="username", type="string"),
     *   @OA\Property(property="address", type="string"),
     *   @OA\Property(property="city", type="string"),
     *   @OA\Property(property="state", type="string"),
     *   @OA\Property(property="postal", type="string"),
     *   @OA\Property(property="country", type="string"),
     *   @OA\Property(property="phone", type="string"),
     *   @OA\Property(property="fax", type="string"),
     *   @OA\Property(property="cell", type="string"),
     *   @OA\Property(property="title", type="string"),
     *   @OA\Property(property="timezone", type="string"),
     *   @OA\Property(property="datetime_format", type="string"),
     *   @OA\Property(property="language", type="string"),
     *   @OA\Property(property="is_administrator", type="boolean"),
     *   @OA\Property(property="loggedin_at", type="string"),
     *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE"}),
     * ),
     * @OA\Schema(
     *   schema="users",
     *   allOf={@OA\Schema(ref="#/components/schemas/usersEditable")},
     *   @OA\Property(property="id", type="string", format="id"),
     *   @OA\Property(property="created_at", type="string", format="date-time"),
     *   @OA\Property(property="updated_at", type="string", format="date-time"),
     * )
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
        'datetime_format',
        'language',
        'expires_at'

    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'fullname',
        'avatar',
    ];

    protected $dates = [
        'loggedin_at',
    ];

    protected $casts = [
        'is_administrator' => 'bool'
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
        $unique = Rule::unique('users')->ignore($existing);

        return [
            'username' => ['required', $unique],
            'email' => ['required', 'email', $unique]
        ];
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'groupMembersFromMemberable',
        'permissions',
    ];

    /**
     * Scope to only return active users.
     *
     * @var Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

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
    
    public function hasPermissionsFor($resource)
    {
        if ($this->is_administrator) {
            $perms = Permission::all(['name'])->pluck('name');
        } else {
            $perms = collect(session('permissions'));
        }

        $filtered = $perms->filter(function($value) use($resource) {
            $match = preg_match("/(.+)-{$resource}/", $value);
            if ($match === 1) {
                return true;
            } else {
                return false;
            }
        });
        
        return $filtered->values();
    }

    public function groupMembersFromMemberable()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_id');
    }
    
    public function groups()
    {
        return $this->morphToMany('ProcessMaker\Models\Group', 'member', 'group_members', 'group_id', 'member_id', 'id', 'id');
    }

    public function permissions()
    {
        return $this->morphToMany('ProcessMaker\Models\Permission', 'assignable');
    }

    public function processesFromProcessable()
    {
        return $this->morphToMany('ProcessMaker\Models\Process', 'processable');
    }

    /**
     * Get the full name as an attribute.
     *
     * @return string
     */
    public function getFullnameAttribute()
    {
        return $this->getFullName();
    }

    /**
     * Get the avatar URL
     *
     * @return string
     */
    public function getAvatarAttribute()
    {
        return $this->getAvatar();
    }

    /**
     * Define the avatar mutator. Within, we set the avatar attribute only if
     * it is not null. This prevents the model from attempting to send an
     * avatar field to the database on update, which has been known to
     * cause errors from time to time.
     *
     * @return string
     */    
    public function setAvatarAttribute($value = null)
    {
        if ($value) {
            $this->attributes['avatar'] = $value;
        }
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

    /**
     * Returns the list of notifications not read by the user
     *
     * @return \Illuminate\Support\Collection
     */
    public function activeNotifications()
    {
        $notifications = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $this->id)
            ->whereNull('read_at')
            ->get();

        $data = [];
        foreach ($notifications as $notification) {
            $notificationData = json_decode($notification->data, false);
            $notificationData->id = $notification->id;
            $data[] = $notificationData;
        }

        return collect($data);
    }

    /**
     * User as assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function assigned()
    {
        return $this->morphMany(ProcessTaskAssignment::class, 'assigned', 'assignment_type', 'assignment_id');
    }

}
