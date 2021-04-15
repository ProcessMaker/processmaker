<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Laravel\Passport\HasApiTokens;
use ProcessMaker\Query\Traits\PMQL;
use Illuminate\Session\Store as Session;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Traits\HasAuthorization;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use ProcessMaker\Traits\SerializeToIso8601;
use Illuminate\Database\Eloquent\SoftDeletes;
use ProcessMaker\Models\RequestUserPermission;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use ProcessMaker\Traits\HideSystemResources;

class User extends Authenticatable implements HasMedia
{
    use PMQL;
    use HasApiTokens;
    use Notifiable;
    use HasMediaTrait;
    use HasAuthorization;
    use SerializeToIso8601;
    use SoftDeletes;
    use HideSystemResources;

    protected $connection = 'processmaker';

    //Disk
    public const DISK_PROFILE = 'profile';
    //collection media library
    public const COLLECTION_PROFILE = 'profile';
    // Session key to save request ids that the user started
    public const REQUESTS_SESSION_KEY = 'web-entry-request-ids';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     *
     * @OA\Schema(
     *   schema="usersEditable",
     *   @OA\Property(property="email", type="string", format="email"),
     *   @OA\Property(property="firstname", type="string"),
     *   @OA\Property(property="lastname", type="string"),
     *   @OA\Property(property="username", type="string"),
     *   @OA\Property(property="password", type="string"),
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
     *   @OA\Property(property="expires_at", type="string"),
     *   @OA\Property(property="loggedin_at", type="string"),
     *   @OA\Property(property="remember_token", type="string"),
     *   @OA\Property(property="status", type="string", enum={"ACTIVE", "INACTIVE", "SCHEDULED", "OUT_OF_OFFICE"}),
     *   @OA\Property(property="fullname", type="string"),
     *   @OA\Property(property="avatar", type="string"),
     *   @OA\Property(property="media", type="array", @OA\Items(ref="#/components/schemas/media")),
     *   @OA\Property(property="birthdate", type="string", format="date"),
     *   @OA\Property(property="delegation_user_id", type="string", format="id"),
     *   @OA\Property(property="manager_id", type="string", format="id"),
     * ),
     * @OA\Schema(
     *   schema="users",
     *   allOf={
     *      @OA\Schema(ref="#/components/schemas/usersEditable"),
     *      @OA\Schema(
     *          type="object",
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="created_at", type="string", format="date-time"),
     *          @OA\Property(property="updated_at", type="string", format="date-time"),
     *          @OA\Property(property="deleted_at", type="string", format="date-time"),
     *      )
     *   },
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
        'meta',
        'delegation_user_id',
        'manager_id',
        'schedule',
    ];

    protected $appends = [
        'fullname',
        'avatar',
    ];

    protected $casts = [
        'is_administrator' => 'bool',
        'meta' => 'object',
        'active_at' => 'datetime',
        'schedule' => 'array',
    ];

    /**
     * Register any model events
     *
     * @return void
     */
    public static function boot() {
        parent::boot();
        static::deleted(function($user) {
            $user->removeFromGroups();
        });
    }

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

        $checkUserIsDeleted = function ($attribute, $value, $fail) use ($existing) {
            if (!$existing) {
                $user = User::onlyTrashed()->where($attribute, $value)->first();
                if ($user) {
                    $fail(
                        __(
                            'A user with the username :username and email :email was previously deleted.',
                            ['username' => $user->username, 'email' => $user->email]
                        )
                    );
                }
            }
        };

        return [
            'username' => ['required', 'alpha_spaces', 'min:4', 'max:255' , $unique, $checkUserIsDeleted],
            'firstname' => ['required', 'max:50'],
            'lastname' => ['required', 'max:50'],
            'email' => ['required', 'email', $unique, $checkUserIsDeleted],
            'status' => ['required', 'in:ACTIVE,INACTIVE,OUT_OF_OFFICE,SCHEDULED'],
            'password' => $existing ? 'required|sometimes|min:6' : 'required|min:6',
            'birthdate' => 'date|nullable' 
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

    public function hasPermissionsFor(...$resources)
    {
        if ($this->is_administrator) {
            $perms = Permission::all(['name'])->pluck('name');
        } else {
            $perms = collect(session('permissions'));
        }

        $filtered = $perms->filter(function ($value) use ($resources) {
            foreach ($resources as $resource) {
                $match = preg_match("/(.+)-{$resource}/", $value);
                if ($match === 1) {
                    return true;
                }
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
        return $this->morphToMany('ProcessMaker\Models\Group', 'member', 'group_members');
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
        $notifications = Notification::query()
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

    /**
     * Check if the user can do any of the listed permissions.
     * If so, return the permission name, otherwise false
     */
    public function canAny($permissions)
    {
        foreach (explode("|", $permissions) as $permission) {
            if ($this->can($permission)) {
                return $permission;
            }
        }
        return false;
    }

    /**
     * Find the user instance for the given username.
     * This ensures we are utilizing our username field for grants for oauth.
     *
     * @param  string  $username
     * @return \App\User
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Check if the user can self-serve themselves a task
     *
     * @param ProcessRequestToken $task
     * @return boolean
     */
    public function canSelfServe(ProcessRequestToken $task)
    {
        if (!$task->is_self_service) {
            return false;
        }

        if (array_key_exists('users', $task->self_service_groups) && in_array(\Auth::user()->id, $task->self_service_groups['users'])) {
            return true;
        } else if (array_key_exists('groups', $task->self_service_groups)) {
            $groups =  collect($task->self_service_groups['groups'])
                ->intersect(
                    $this->groups()->pluck('groups.id')
                )->count() > 0;
            return $groups;
        } else {
            // For older processes
            return collect($task->self_service_groups)
                ->intersect(
                    $this->groups()->pluck('groups.id')
                )->count() > 0;
        }
    }

    public function updatePermissionsToRequests()
    {
        // Update existing request_user_permissions
        $permissions = RequestUserPermission::with('request')->whereHas('request', function ($query) {
            $query->where('request_user_permissions.user_id', $this->getKey());
            $query->whereRaw('process_requests.updated_at > request_user_permissions.updated_at');
        })->get();
        foreach ($permissions as $permission) {
            $permission->can_view = $this->can('view', $permission->request);
            $permission->save();
        }

        // Add new request_user_permissions

        // Declare these variables once for the sake of speed
        $timestamp = Carbon::now()->toDateTimeString();
        $userId = $this->getKey();

        // Find requests without permissions entries for this user
        // while limiting the select clause to save memory
        $query = ProcessRequest::whereRaw(
            'id not in (select request_id from request_user_permissions where user_id=?)',
            [$this->getKey()]
        )->select(
            'id', 'process_id', 'user_id', 'parent_request_id', 'callable_id'
        );

        // Process the results in chunks
        $query->limit(500);
        while ($query->count() > 0) {
            // Retrieve this chunk
            $requests = $query->get();
            
            // Declare our batch array
            $batch = [];
            
            // Process each request
            foreach ($requests as $request) {
                $batch[] = [
                    'request_id' => $request->id,
                    'user_id' => $userId,
                    'can_view' => $this->can('view', $request),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            // Batch insert the new permissions
            RequestUserPermission::query()->insert($batch);
        }        
    }

    public function removeFromGroups()
    {
        $this->groups()->detach();
    }
    
    public function availableSelfServiceTaskIds()
    {
        $groupIds = $this->groups()->pluck('groups.id');

        $taskQuery = ProcessRequestToken::select(['id'])
        ->where([
            'is_self_service' => true,
            'status' => 'ACTIVE',
            'user_id' => null
        ]);

        $taskQuery->where(function($query) use($groupIds) {
            // Check if `self_service_groups` contains any of the user's groups
            foreach($groupIds as $groupId) {
                $query->orWhereJsonContains('self_service_groups->groups', (int) $groupId);
                // keep compatibility
                $query->orWhereJsonContains('self_service_groups->groups', (string) $groupId);
                $query->orWhereJsonContains('self_service_groups', (int) $groupId);
                $query->orWhereJsonContains('self_service_groups', (string) $groupId);
            }
            $query->orWhereJsonContains('self_service_groups->users', (int) $this->id);
            $query->orWhereJsonContains('self_service_groups->users', (string) $this->id);
        });
        return $taskQuery->pluck('id');
    }

    /**
     * User's Delegation are user associations that allow for automatic reassignment based on specific availability of a user.
     *
     * @return User
     */
    public function delegationUser()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User's Manager are user associations that allow for automatic reassignment based on specific rules in the task assignment.
     *
     * @return User
     */
    public function manager()
    {
        return $this->belongsTo(User::class);
    }
}
