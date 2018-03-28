<?php
namespace ProcessMaker\OAuth2;

use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Represents a scope entity.  These can be persisted to our database
 * @package ProcessMaker\OAuth2
 * @see ScopeEntityInterface
 */
class ScopeEntity implements ScopeEntityInterface
{

    /**
     * @var The name of our scope
     */
    private $name;

    /**
     * Create a scope entity
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the identifier for this scope
     * @return string
     */
    public function getIdentifier()
    {
        return $this->name;
    }

    /**
     * Serialize our scope entity, which is just the name
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->name;
    }
}
