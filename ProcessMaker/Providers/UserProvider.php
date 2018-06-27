<?php
namespace ProcessMaker\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use ProcessMaker\Model\User;

/**
 * Our User Provider which assists in finding users in our database
 * @package ProcessMaker\Providers
 */
class UserProvider extends EloquentUserProvider
{

    /**
     * Create our user provider, with the hashing implementation needed
     * @param HasherContract $hasher
     */
    public function __construct(HasherContract $hasher)
    {
        parent::__construct($hasher, User::class);
    }

    /**
     * Retrieve a user by a remember me token, which we currently do not support
     * @param mixed $identifier
     * @param string $token
     * @return UserContract|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // We currently do not support a remember me token
        return null;
    }

    /**
     * Update a remember token, which we currently do not support
     * @param UserContract $user
     * @param string $token
     * @return null|void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        // We currently do not support a remember me token
        return null;
    }

    /**
     * Retrieve a user by passed in credentials
     * If it's by email address, let's try to first get by email
     * @param array $credentials
     * @return UserContract|\Illuminate\Database\Eloquent\Model|null|static
     */
    public function retrieveByCredentials(array $credentials)
    {
        if(isset($credentials['email'])) {
            return User::where('email', $credentials['email'])->first();
        } else if(isset($crendentials['username'])) {
            return User::where('username', $credentials['username'])->first();
        }
        // No valid credential to find, let's return nothing
        return null;
    }
}
