<?php

namespace ProcessMaker\Contracts;

use ProcessMaker\Models\ProcessRequestToken;

/**
 * @see \ProcessMaker\Services\ConditionalRedirectService
 * @package ProcessMaker\Contracts
 */
interface ConditionalRedirectServiceInterface
{
    /**
     * Process a set of conditions and return the first that satisfies for an array of data.
     *
     * @param array $conditionalRedirect
     * @param array $data
     *
     * @return array|null
     */
    public function resolve(array $conditionalRedirect, array $data): ?array;

    /**
     * Process a set of conditions and return the first that satisfies for a process request token.
     *
     * @param array $conditionalRedirect
     * @param ProcessRequestToken $token
     *
     * @return array|null
     */
    public function resolveForToken(array $conditionalRedirect, ProcessRequestToken $token): ?array;
}
