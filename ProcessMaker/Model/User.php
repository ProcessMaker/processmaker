<?php

namespace ProcessMaker\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

use League\OAuth2\Server\Entities\UserEntityInterface;
use ProcessMaker\Model\Traits\Uuid;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * Represents an Eloquent model of a User
 * @package ProcessMaker\Model
 *
 * @property \ProcessMaker\Model\Role $role
 */
class User extends Authenticatable implements UserEntityInterface, CanResetPassword, HasMedia
{
    use Notifiable;
    use Uuid;
    use CanResetPasswordTrait;
    use HasMediaTrait;

    const TYPE = 'USER';

    protected $hidden = [
        'id',
        'password'
    ];

    protected $fillable = [
        'uid',
        'username',
        'password',
        'firstname',
        'lastname',
        'email',
        'expires_at',
        'created_at',
        'updated_at',
        'status',
        'country',
        'city',
        'location',
        'address',
        'phone',
        'fax',
        'cell',
        'postal',
        'title',
        'birthdate',
        'role_id',
        'time_zone',
        'lang',
        'last_login'
    ];

    protected $appends = [
        'fullname'
    ];

    /**
     * The key to use in routes to fetch a user
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Return the full name for this user which is the first name and last name separated with a space
     * @return string
     */
    public function getFullName()
    {
        return implode(" ", [
            $this->firstname,
            $this->lastname
        ]);
    }

    /**
     * Returns the relationship of groups this user belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_users');
    }

    /**
     * Returns our unique authentication identifier, which is our id in our database record
     * @see https://laravel.com/docs/5.4/authentication#the-authenticatable-contract
     * @return integer
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * Returns the column that represents our authentication identifier
     * @see https://laravel.com/docs/5.4/authentication#the-authenticatable-contract
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Sets a remember token, which we don't currently support but needs to be implemented by interface
     * @param string $value
     */
    public function setRememberToken($value)
    {
        /**
         * At this time, we do not support remember tokens
         */
    }

    /**
     * Gets our remember token, which we don't currently support but needs to be implemented by interface
     * @return null
     */
    public function getRememberToken()
    {
         /**
         * At this time, we do not support remember tokens
         */
        return null;
    }

    /**
     * Returns the password field for our user.  In our case, represented by the USR_PASSWORD column
     * @return mixed
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Return the time zone label for this user.  If no user specific time zone is set, then we'll
     * retrieve the workspace default.
     * @return mixed|null
     * @todo Use a facade for system configuration, or use the config helper always
     */
    public function getTimeZone()
    {
        if ($this->time_zone) {
            // Return our user specific timezone
            return $this->time_zone;
        } else {
            // Let's return our system default timezone
            return app()->getTimezone();
        }
    }

    /**
     * Returns the OAuth2 Unique Identifier for this user
     *
     * For this, we are returning the unique guid for this user
     * @see UserEntityInterface
     * @return string The unique identifier for this OAuth2 based user
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * Role of the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the full name as an attribute.
     *
     * @return string
     */
    public function getFullnameAttribute() {
        return $this->getFullName();
    }
}
