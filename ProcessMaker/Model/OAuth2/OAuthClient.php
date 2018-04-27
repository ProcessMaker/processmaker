<?php
namespace ProcessMaker\Model\OAuth2;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * Represents an OAuth2 Client in our database
 * @package ProcessMaker\Model\OAuth2
 */
class OAuthClient extends Model implements ClientEntityInterface
{
    // Our table name
    protected $table = 'oauth_clients';

    // Our primary key is not an autoincrementing key
    public $incrementing = false;

    // We do not sure create/update timestamps in this table
    public $timestamps = false;

    /**
     * Returns the OAuth2 Client Name
     * @see ClientEntityInterface
     * @return string The name of the OAuth2 Client
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the unique OAuth2 Client Identifier
     * @see ClientEntityInterface
     * @return string the Unique Identifier
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * Returns the Redirect URI for this OAuth2 Client
     * @see ClientEntityInterface
     * @return string The redirect URI for handling OAuth2 client token requests
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }
}
