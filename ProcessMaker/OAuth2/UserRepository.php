<?php
namespace ProcessMaker\OAuth2;

use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use ProcessMaker\Model\User;

/**
 * Our OAuth2 User Repository implementation
 * @package ProcessMaker\OAuth2
 * @see UserRepositoryInterface
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Fetches a UserEntity by the user credentials provided
     * @param string $username
     * @param string $password
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @return \Illuminate\Database\Eloquent\Model|\League\OAuth2\Server\Entities\UserEntityInterface|null|static
     * @note We utilize Eloquent to grab a matching user then use the Auth facade to attempt to authenticate the user
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $user = User::where('USR_USERNAME', $username)->first();
        if (!$user) {
            return null;
        }
        // validate the password
        if (Auth::attempt([
            'username' => $user->USR_USERNAME,
            'password' => $password
        ])) {
            return $user;
        }
        return null;
    }
}
