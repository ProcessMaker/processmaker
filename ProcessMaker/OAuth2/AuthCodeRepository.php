<?php
namespace ProcessMaker\OAuth2;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use ProcessMaker\Model\OAuth2\OAuthAuthorizationCode;

/**
 * Our Aut Code Repository Implementation
 * @see AuthCodeRepositoryInterface
 * @package ProcessMaker\OAuth2
 */
class AuthCodeRepository implements AuthCodeRepositoryInterface
{

    /**
     * Persist a new auth code to our database by saving it
     * @param AuthCodeEntityInterface $authCodeEntity
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $authCodeEntity->save();
    }

    /**
     * Create a new AuthCode instance
     * @return AuthCodeEntityInterface|OAuthAuthorizationCode
     * @see ProcessMaker\Model\OAuth2\OAuthAuthorizationCode
     */
    public function getNewAuthCode()
    {
        return new OAuthAuthorizationCode();
    }

    /**
     * Revoke an auth code by deleting it from our database
     * @param string $codeId
     * @throws \Exception
     */
    public function revokeAuthCode($codeId)
    {
        OAuthAuthorizationCode::where('AUTHORIZATION_CODE', $codeId)->delete();
    }

    /**
     * Determines if an auth code is revoked
     * @param string $codeId
     * @return bool
     */
    public function isAuthCodeRevoked($codeId)
    {
        $token = OAuthAuthorizationCode::where('AUTHORIZATION_CODE', $codeId)->first();
        if (!$token) {
            return true;
        }
        // Token is still "valid", although may be expired
        return false;
    }
}
