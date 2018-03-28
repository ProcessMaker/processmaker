<?php
namespace ProcessMaker\Model\OAuth2;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use ProcessMaker\Model\User;

/**
 * Represents a valid OAuth2 authorization code in our database
 * @package ProcessMaker\Model\OAuth2
 */
class OAuthAuthorizationCode extends Model implements AuthCodeEntityInterface
{
    // Specify our table name
    protected $table = 'OAUTH_AUTHORIZATION_CODES';

    // Our primary key is not an autoincrementing key
    public $incrementing = false;

    // Our primary key is not ID but AUTHORIZATION_CODE
    protected $primaryKey = 'AUTHORIZATION_CODE';

    // We do not sure create/update timestamps in this table
    public $timestamps = false;

    // Our dates which will auto translate to Carbon instances
    protected $dates = [
        'EXPIRES'
    ];

    /**
     * Returns the relationship of the user this authorization code belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'USER_ID', 'USR_UID');
    }

    /**
     * Returns the relationship of the OAuth2 client this code is good for
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(OAuthClient::class, 'CLIENT_ID', 'CLIENT_ID');
    }

    /**
     * Sets the OAuth2 Redirect URI for this code
     * @see AuthCodeEntityInterface
     * @param string $uri
     */
    public function setRedirectUri($uri)
    {
        $this->REDIRECT_URI = $uri;
    }

    /**
     * Returns the OAuth2 Redirect URI for this code
     * @see AuthCodeEntityInterface
     * @return string The Redirect URI
     */
    public function getRedirectUri()
    {
        return $this->REDIRECT_URI;
    }

    /**
     * Sets the OAuth2 User Identifier for this code
     * @see AuthCodeEntityInterface
     * @param string $identifier
     */
    public function setUserIdentifier($identifier)
    {
        $this->USER_ID = $identifier;
    }

    /**
     * Gets the user identifier for this code
     * @return int|mixed|string
     * @see AuthCodeEntityInterface
     */
    public function getUserIdentifier()
    {
        return $this->user->USR_UID;
    }

    /**
     * Gets the OAuth2 client for this code
     * @see AuthCodeEntityInterface
     * @return OAuthClient
     */
    public function getClient()
    {
        return OAuthClient::where('CLIENT_ID', $this->CLIENT_ID)->first();
    }

    /**
     * Add a scope to this auth code
     * @param ScopeEntityInterface $scope
     * @see AuthCodeEntityInterface
     * @note We store scopes as a space deliminated list in our scope column for this record
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $scopes = explode(" ", $this->SCOPE);
        $scopes[] = $scope->getIdentifier();
        $this->SCOPE = implode(' ', $scopes);
    }

    /**
     * Return the datetime of expiration for this code
     * @see AuthCodeEntityInterface
     * @note Eloquent will return a Carbon instance, but Carbon extends DateTime
     * @return \DateTime Expiration
     */
    public function getExpiryDateTime()
    {
        return $this->EXPIRES;
    }

    /**
     * Set the datetime of expiration for this OAuth code
     * @param DateTime $dateTime
     * @see AuthCodeEntityInterface
     */
    public function setExpiryDateTime(DateTime $dateTime)
    {
        $this->EXPIRES = $dateTime;
    }

    /**
     * Return the unique code identifier
     * @see AuthCodeEntityInterface
     * @return string The unique token identifier
     */
    public function getIdentifier()
    {
        return $this->AUTHORIZATION_CODE;
    }

    /**
     * Set the unique code identifier
     * @param $identifier
     * @see AuthCodeEntityInterface
     */
    public function setIdentifier($identifier)
    {
        $this->AUTHORIZATION_CODE = $identifier;
    }

    /**
     * Returns the scopes that this OAuth token is good for
     * @see AuthCodeEntityInterface
     * @return ScopeEntityInterface[]
     */
    public function getScopes()
    {
        $types = explode(" ", $this->SCOPE);
        return OAuthScope::whereIn('TYPE', $types)->get();
    }

    /**
     * Sets the OAuth2 Client this code is good for
     * @param ClientEntityInterface $client
     */
    public function setClient(ClientEntityInterface $client)
    {
        $this->client()->associate($client);
    }
}
