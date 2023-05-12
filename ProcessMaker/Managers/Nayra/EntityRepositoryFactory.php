<?php

namespace ProcessMaker\Managers\Nayra;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EntityRepositoryFactory
{
    public static function createRepository(string $entity): EntityRepository
    {
        switch ($entity) {
            case 'request':
                return new ProcessRequestRepository();
            case 'task':
                return new ProcessRequestTokenRepository();
            default:
                throw new InvalidArgumentException("Invalid entity: $entity");
        }
    }
}
