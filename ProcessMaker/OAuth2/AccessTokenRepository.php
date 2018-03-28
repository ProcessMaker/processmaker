<?php
namespace ProcessMaker\OAuth2;

use Exception;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use ProcessMaker\Model\OAuth2\OAuthAccessToken;

/**
 * Our Access Token Repository Implementation
 * @see AccessTokenRepositoryInterface
 * @package ProcessMaker\OAuth2
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * Return a new Access Token instance
     * @param ClientEntityInterface $clientEntity
     * @param array $scopes
     * @param null $userIdentifier
     * @return AccessTokenEntityInterface|OAuthAccessToken
     * @see ProcessMaker\Models\OAuth2\OAuthAccessToken
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        return new OAuthAccessToken();
    }

    /**
     * Determines if the token represented by a passed id is valid or not
     * @param string $tokenId
     * @return bool
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $token = OAuthAccessToken::where('ACCESS_TOKEN', $tokenId)->first();
        if (!$token) {
            return true;
        }
        // Token is still "valid", although may be expired
        return false;
    }

    /**
     * Persist the access token to the database
     * @param AccessTokenEntityInterface $accessTokenEntity
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $accessTokenEntity->save();
    }

    /**
     * Revoke the access token by deleting it from the database
     * @param string $tokenId
     * @throws Exception
     */
    public function revokeAccessToken($tokenId)
    {
        OAuthAccessToken::where('ACCESS_TOKEN', $tokenId)->delete();
    }
}
