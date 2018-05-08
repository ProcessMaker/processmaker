<?php
namespace ProcessMaker\OAuth2;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use ProcessMaker\Model\OAuth2\OAuthRefreshToken;

/**
 * Our OAuth2 Refresh Token Repository Implementation
 * @package ProcessMaker\OAuth2
 * @see RefreshTokenRepositoryInterface
 */
class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * Revokes a refresh token by it's identifier
     * @param string $tokenId
     * @throws \Exception
     */
    public function revokeRefreshToken($tokenId)
    {
        OAuthRefreshToken::where('refresh_token', $tokenId)->delete();
    }

    /**
     * Determines if a refresh token is revoked
     * @param string $tokenId
     * @return bool
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $token = OAuthRefreshToken::where('refresh_token', $tokenId)->first();
        if (!$token) {
            return true;
        }
        return false;
    }

    /**
     * Persist a new refresh token by saving it to our database
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $refreshTokenEntity->save();
    }

    /**
     * Create a new Refresh Token instance
     * @return RefreshTokenEntityInterface|OAuthRefreshToken
     */
    public function getNewRefreshToken()
    {
        return new OAuthRefreshToken();
    }
}
