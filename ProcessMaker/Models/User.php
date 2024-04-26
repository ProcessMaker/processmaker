<?php

namespace ProcessMaker\Models;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\HasApiTokens;
use ProcessMaker\Models\EmptyModel;
use ProcessMaker\Notifications\ResetPassword as ResetPasswordNotification;
use ProcessMaker\Query\Traits\PMQL;
use ProcessMaker\Rules\StringHasAtLeastOneUpperCaseCharacter;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasAuthorization;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use PMQL;
    use HasApiTokens;
    use Notifiable;
    use InteractsWithMedia;
    use HasAuthorization;
    use SerializeToIso8601;
    use SoftDeletes;
    use HideSystemResources;
    use HasFactory;
    use Exportable;

    protected $connection = 'processmaker';

    // Disk
    public const DISK_PROFILE = 'profile';

    // collection media library
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
     *   @OA\Property(property="status",type="string",enum={"ACTIVE","INACTIVE","SCHEDULED","OUT_OF_OFFICE","BLOCKED"}),
     *   @OA\Property(property="fullname", type="string"),
     *   @OA\Property(property="avatar", type="string"),
     *   @OA\Property(property="media", type="array", @OA\Items(ref="#/components/schemas/media")),
     *   @OA\Property(property="birthdate", type="string", format="date"),
     *   @OA\Property(property="delegation_user_id", type="string", format="id"),
     *   @OA\Property(property="manager_id", type="string", format="id"),
     *   @OA\Property(property="meta", type="object", additionalProperties=true),
     *   @OA\Property(property="force_change_password", type="boolean"),
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
        'force_change_password',
        'password_changed_at',
        'connected_accounts',
        'preferences_2fa',
    ];

    protected $appends = [
        'fullname',
    ];

    protected $casts = [
        'is_administrator' => 'bool',
        'meta' => 'object',
        'active_at' => 'datetime',
        'loggedin_at' => 'datetime',
        'schedule' => 'array',
        'preferences_2fa' => 'array',
    ];

    /**
     * Register any model events
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::deleted(function ($user) {
            $user->removeFromGroups();
        });
    }

    public function someNewMethod()
    {
        $res = 1 + 1;
        return "testing if this is covered " . $res;
    }

    /**
     * Validation rules
     *
     * @param  \ProcessMaker\Models\User|null  $existing
     *
     * @return array
     */
    public static function rules(self $existing = null)
    {
        $unique = Rule::unique('users')->ignore($existing);

        return [
            // The following characters where not included in the regexp: & %  ' " ? /
            'username' /****/ => ['required', 'regex:/^[a-zA-Z0-9.!#$*+=^_`|~\-@]+$/', 'min:3', 'max:255', $unique],
            'firstname' /***/ => ['required', 'max:50'],
            'lastname' /****/ => ['required', 'max:50'],
            'email' /*******/ => ['required', 'email'],
            'birthdate' /***/ => ['nullable', 'date'],
            'phone' /*******/ => ['nullable', 'regex:/^[+\.0-9x\)\(\-\s\/]*$/'],
            'fax' /*********/ => ['nullable', 'regex:/^[+\.0-9x\)\(\-\s\/]*$/'],
            'cell' /********/ => ['nullable', 'regex:/^[+\.0-9x\)\(\-\s\/]*$/'],
            'status' /******/ => ['required', 'in:ACTIVE,INACTIVE,OUT_OF_OFFICE,SCHEDULED,BLOCKED'],
            'password' /****/ => static::passwordRules($existing),
        ];
    }

    /**
     * Validation rules specifically for the password
     *
     * @param  \ProcessMaker\Models\User|null  $existing
     *
     * @return array
     */
    public static function passwordRules(self $existing = null)
    {
        // Mandatory policies
        $passwordPolicies = [
            'required',
            $existing ? 'sometimes' : '',
        ];
        // Configurable policies
        $passwordRules = Password::min((int) config('password-policies.minimum_length', 8));
        if (config('password-policies.maximum_length', false)) {
            $passwordPolicies[] = 'max:' . config('password-policies.maximum_length');
        }
        if (config('password-policies.numbers', true)) {
            $passwordRules->numbers();
        }
        if (config('password-policies.uppercase', true)) {
            $passwordPolicies[] = new StringHasAtLeastOneUpperCaseCharacter();
        }
        if (config('password-policies.special', true)) {
            $passwordRules->symbols();
        }
        $passwordPolicies[] = $passwordRules;

        return $passwordPolicies;
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
        return implode(' ', [
            $this->firstname,
            $this->lastname,
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

    public function projectMembers()
    {
        if (class_exists('ProcessMaker\Package\Projects\Models\ProjectMember')) {
            return $this->hasMany('ProcessMaker\Package\Projects\Models\ProjectMember', 'member_id', 'id')->where('member_type', self::class);
        } else {
            // Handle the case where the ProjectMember class doesn't exist.
            return $this->hasMany(EmptyModel::class);
        }
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
     * Get url Avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        $media = $this->getMedia(self::COLLECTION_PROFILE);
        $lastUpload = $media->last();
        $url = $lastUpload ? $lastUpload->getFullUrl() : '';

        return $url;
    }

    /**
     * Set the user's timezone.
     */
    public function setTimezoneAttribute(string $value = ''): void
    {
        $this->attributes['timezone'] = $value ?: $this->getDefaultTimezone();
    }

    /**
     * Get default timezone for new users.
     */
    public function getDefaultTimezone(): string
    {
        $setting = Setting::byKey('users.timezone');
        if ($setting === null) {
            return config('app.timezone');
        }

        $config = (object) $setting->config;

        return $config->timezone;
    }

    /**
     * Returns the list of notifications not read by the user
     *
     * @return \Illuminate\Support\Collection
     */
    public function activeNotifications()
    {
        $notifications = Notification::query()
            ->where('notifiable_type', self::class)
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
     */
    public function canAny($permissions, $arguments = []): bool
    {
        return parent::canAny(explode('|', $permissions), $arguments);
    }

    /**
     * Check if the user can do any of the listed permissions.
     * If so, return the permission name, otherwise false
     */
    public function canAnyFirst($permissions): bool|string
    {
        foreach (explode('|', $permissions) as $permission) {
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
     * @return bool
     */
    public function canSelfServe(ProcessRequestToken $task)
    {
        if (!$task->is_self_service) {
            return false;
        }

        if (array_key_exists('users', $task->self_service_groups) && in_array(\Auth::user()->id, $task->self_service_groups['users'])) {
            return true;
        } elseif (array_key_exists('groups', $task->self_service_groups)) {
            return collect($task->self_service_groups['groups'])
                ->intersect(
                    $this->groups()->pluck('groups.id')
                )->count() > 0;
        } else {
            // For older processes
            return collect($task->self_service_groups)
                ->intersect(
                    $this->groups()->pluck('groups.id')
                )->count() > 0;
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
            'user_id' => null,
        ]);

        $taskQuery->where(function ($query) use ($groupIds) {
            // Check if `self_service_groups` contains any of the user's groups
            foreach ($groupIds as $groupId) {
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
        return $this->belongsTo(self::class);
    }

    /**
     * User's Manager are user associations that allow for automatic reassignment based on specific rules in the task assignment.
     *
     * @return User
     */
    public function manager()
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public static function whereFullname($value)
    {
        return self::whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%$value%"]);
    }

    public function removeOldRunScriptTokens()
    {
        // Remove old tokens more that one week old
        return $this->tokens()
            ->where('name', 'script-runner')
            ->where('created_at', '<', now()->subWeek())
            ->delete();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function getValid2FAPreferences(): array
    {
        // Get global and user values
        $global2FAEnabled = config('password-policies.2fa_method', []);
        $user2FAEnabled = !is_null($this->preferences_2fa) ? $this->preferences_2fa : [];

        // Get valid values
        $aux = array_intersect($global2FAEnabled, $user2FAEnabled);

        return !empty($aux) ? array_values($aux) : $global2FAEnabled;
    }

    public function in2FAGroupOrIndependent()
    {
        $userGroups = $this->groups;
        $groupCount = $userGroups->count();

        if ($groupCount === 0) {
            return true;
        }

        $groupsWith2fa = $userGroups->where('enabled_2fa', true);

        // Check if the only group has 2fa enabled, if so, ask for 2fa
        $hasSingleGroupWith2fa = $groupCount === 1 && $groupsWith2fa->count() === 1;
        // Check if at least one group has 2fa enabled, if so, ask for 2fa
        $hasMultipleGroupsWithAtLeastOne2fa = $groupCount > 1 && $groupsWith2fa->count() > 0;
        // Check if all groups don't have 2fa enabled, if so, ask for 2fa if the 2fa setting is enabled
        $independent = $groupCount === 0;

        if ($hasSingleGroupWith2fa || $hasMultipleGroupsWithAtLeastOne2fa || $independent) {
            return true;
        }
        return false;
    }
}
