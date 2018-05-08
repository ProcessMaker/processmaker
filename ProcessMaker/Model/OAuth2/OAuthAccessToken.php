<?php
namespace ProcessMaker\Model\OAuth2;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use ProcessMaker\Model\User;

/**
 * Represents an OAuth2 Access Token in our database
 * @package ProcessMaker\Model\OAuth2
 */
class OAuthAccessToken extends Model implements AccessTokenEntityInterface
{
    use AccessTokenTrait;

    // Our table name
    protected $table = 'oauth_access_tokens';

    // Our primary key is not an autoincrementing key
    public $incrementing = false;

    // Our primary key is not ID but ACCESS_TOKEN
    protected $primaryKey = 'access_token';

    // We do not sure create/update timestamps in this table
    public $timestamps = false;

    // Our dates which will auto translate to Carbon instances
    protected $dates = [
        'expires'
    ];

    /**
     * Returns the relationship of the OAuth2 client this access token is good for
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(OAuthClient::class, "client_id");
    }

    /**
     * Returns the relationship of the User this access token belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @see ProcessMaker\Model\User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sets the user identifier for this access token
     * @param int|string $identifier
     * @see AccessTokenEntityInterface
     */
    public function setUserIdentifier($identifier)
    {
        $this->user_id = $identifier;
    }

    /**
     * Gets the user identifier for this access token
     * @return int|mixed|string
     * @see AccessTokenEntityInterface
     */
    public function getUserIdentifier()
    {
        return $this->user_id;
    }

    /**
     * Returns the OAuth2 client this access token is good for
     * @return ClientEntityInterface|mixed
     * @see AccessTokenEntityInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Add a scope to this auth token
     * @see AccessTokenEntityInterface
     * @note We store scopes as a space deliminated list in our scope column for this record
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $scopes = explode(" ", $this->scope);
        $scopes[] = $scope->getIdentifier();
        $this->scope = implode(' ', $scopes);
    }

    /**
     * Returns the scopes that this OAuth token is good for
     * @see AccessTokenEntityInterface
     * @return ScopeEntityInterface[]
     */
    public function getScopes()
    {
        $types = explode(" ", $this->scope);
        return OAuthScope::whereIn('type', $types)->get();
    }

    /**
     * Return the datetime of expiration for this OAuth token
     * @see AccessTokenEntityInterface
     * @note Eloquent will return a Carbon instance, but Carbon extends DateTime
     * @return \DateTime Expiration
     */
    public function getExpiryDateTime()
    {
        return $this->expires;
    }

    /**
     * Set the datetime of expiration for this OAuth token
     * @see AccessTokenEntityInterface
     */
    public function setExpiryDateTime(\DateTime $dateTime)
    {
        $this->expires = $dateTime;
    }

    /**
     * Return the unique OAuth token identifier
     * @see AccessTokenEntityInterface
     * @return string The unique token identifier
     */
    public function getIdentifier()
    {
        return $this->access_token;
    }

    /**
     * Set the unique OAuth token identifier
     * @param $identifier
     * @see AccessTokenEntityInterface
     */
    public function setIdentifier($identifier)
    {
        $this->access_token = $identifier;
    }

    /**
     * Set the OAuth2 client for this access token
     * @param ClientEntityInterface $client
     * @see AccessTokenEntityInterface
     * @see ProcessMaker\Model\OAuth2\OAuthClient
     */
    public function setClient(ClientEntityInterface $client)
    {
        $this->client()->associate($client);
    }
}
