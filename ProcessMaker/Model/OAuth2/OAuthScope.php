<?php
namespace ProcessMaker\Model\OAuth2;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * This represents an OAuth2 Scope in our database
 * @package ProcessMaker\Model\OAuth2
 */
class OAuthScope extends Model implements ScopeEntityInterface
{
    // Our table name
    protected $table = 'oauth_scopes';

    // Our primary key is not an autoincrementing key
    public $incrementing = false;

    // Our primary key is not ID but TYPE
    protected $primaryKey = 'type';

    // We do not sure create/update timestamps in this table
    public $timestamps = false;

    /**
     * Returns the unique identifier for this scope
     * @return mixed|string
     * @see ScopeEntityInterface
     */
    public function getIdentifier()
    {
        return $this->type;
    }
}
