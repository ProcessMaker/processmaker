<?php
namespace ProcessMaker\Model\OAuth2;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * Represents an OAuth2 Refresh token in our database
 * @package ProcessMaker\Model\OAuth2
 */
class OAuthRefreshToken extends Model implements RefreshTokenEntityInterface
{
    // Specify our table name
    protected $table = 'OAUTH_REFRESH_TOKENS';

    // Our primary key is not an autoincrementing key
    public $incrementing = false;

    // Our primary key is not ID but REFRESH_TOKEN
    protected $primaryKey = 'REFRESH_TOKEN';

    // We do not sure create/update timestamps in this table
    public $timestamps = false;

    /**
     * Return the unique OAuth token identifier
     * @see RefreshTokenEntityInterface
     * @return string The unique token identifier
     */
    public function getIdentifier()
    {
        return $this->REFRESH_TOKEN;
    }

    /**
     * Sets the unique OAuth token identifier
     * @see RefreshTokenEntityInterface
     * @param $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->REFRESH_TOKEN = $identifier;
    }

    /**
     * Returns the relationship of the access token this refresh token belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accessToken()
    {
        return $this->belongsTo(OAuthAccessToken::class, "ACCESS_TOKEN", "ACCESS_TOKEN");
    }

    /**
     * Return the datetime of expiration for this OAuth token
     * @see RefreshTokenEntityInterface
     * @note Eloquent will return a Carbon instance, but Carbon extends DateTime
     * @return \DateTime Expiration
     */
    public function getExpiryDateTime()
    {
        return $this->EXPIRES;
    }

    /**
     * Set the datetime of expiration for this OAuth token
     * @see RefreshTokenEntityInterface
     */
    public function setExpiryDateTime(\DateTime $dateTime)
    {
        $this->EXPIRES = $dateTime;
    }

    /**
     * Sets the access token this refresh token belongs to
     * @param AccessTokenEntityInterface $accessToken
     * @see RefreshTokenEntityInterface
     */
    public function setAccessToken(AccessTokenEntityInterface $accessToken)
    {
        $this->accessToken()->associate($accessToken);
    }

    /**
     * Returns the access token this refresh token belongs to
     * @return AccessTokenEntityInterface|mixed
     * @note This implements the required interface but uses the accessToken() relationship defined above
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
