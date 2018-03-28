<?php
namespace ProcessMaker\OAuth2;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * Our OAuth2 Scope Repository Implementation
 * @package ProcessMaker\OAuth2
 * @see ScopeRepositoryInterface
 */
class ScopeRepository implements ScopeRepositoryInterface
{

    /**
     * Finalize our scopes, however we do not use scopes at this time
     * @param array $scopes
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @param null $userIdentifier
     * @return array|\League\OAuth2\Server\Entities\ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        // We don't do anything here because we're not expanding the scopes.  We utilize
        // Gating and other ACL methods
        return $scopes;
    }


    /**
     * Return a dummy scope since we don't really handle scopes in our system
     * @param string $identifier
     * @return \League\OAuth2\Server\Entities\ScopeEntityInterface|ScopeEntity
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        // Just return a quick and dirty scope
        return new ScopeEntity($identifier);
    }
}
