<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use ProcessMaker\Traits\HasAuthorization;

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
        'is_administrator',
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
        'permissionAssignments',
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

    public function groupMembersFromMemberable()
    {
        return $this->morphMany(GroupMember::class, 'member', null, 'member_id');
    }

    public function permissionAssignments()
    {
        return $this->morphMany(PermissionAssignment::class, 'assignable', null, 'assignable_id');
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
     * Hashes the password passed as a clear text
     *
     * @param $pass
     */
    public function setPasswordAttribute($pass)
    {

        $this->attributes['password'] = Hash::make($pass);

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

    public function activeNotifications()
    {
        $tasks = DB::table('notifications')
            ->where('data->user_id', $this->id)
            ->whereNull('read_at')
            ->get();

        $data = [];
        foreach ($tasks as $task) {
            $taskData = json_decode($task->data, false);
            $taskData->id = $task->id;
            $data[] = $taskData;
        }

        return $data;
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

    public function startProcesses()
    {
        $user = Auth::user();
        if (!$user->hasPermission('requests.create')) {
            return [];
        }
        $permission = Permission::byGuardName('requests.create');

        $processUser = ProcessPermission::where('permission_id', $permission->id)
            ->where('assignable_id', $user->id)
            ->where('assignable_type', User::class)
            ->pluck('process_id');

        $processGroup = ProcessPermission::where('permission_id', $permission->id)
            ->whereIn('assignable_id', $user->groupMembersFromMemberable()->pluck('group_id')->toArray())
            ->where('assignable_type', Group::class)
            ->pluck('process_id');

        return array_values(array_unique(array_merge(
            $processUser->toArray(), $processGroup->toArray()
        ), SORT_REGULAR));
    }

}
