<?php

namespace ProcessMaker\Nayra\Repositories;

use InvalidArgumentException;

class EntityRepositoryFactory
{
    /**
     * Return data repository according to entity type
     *
     * @param string $entity
     * @return EntityRepository
     *
     * @throws InvalidArgumentException
     */
    public static function createRepository(string $entity): EntityRepository
    {
        switch ($entity) {
            case 'request':
                return new ProcessRequestRepository();
            case 'task':
                return new ProcessRequestTokenRepository();
            default:
                throw new InvalidArgumentException("Invalid entity: {$entity}");
        }
    }
}
