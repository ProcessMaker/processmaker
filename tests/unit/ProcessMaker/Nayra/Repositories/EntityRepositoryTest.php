<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Nayra\Repositories;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Nayra\Repositories\EntityRepository;
use ReflectionProperty;
use Tests\TestCase;

class EntityRepositoryTest extends TestCase
{
    /**
     * Create a concrete implementation of EntityRepository for testing.
     */
    private function createRepository(): EntityRepository
    {
        return new class extends EntityRepository {
            public function create(array $transaction): ?Model
            {
                return null;
            }

            public function update(array $transaction): ?Model
            {
                return null;
            }

            public function save(array $transaction): ?Model
            {
                return null;
            }
        };
    }

    /**
     * Test that $uid2id is NOT a static property (fix for Octane data leak).
     *
     * In Octane, static properties persist across requests. The critical fix
     * changes $uid2id from "private static" to "private" so that each instance
     * has its own isolated cache, preventing data leaks between requests.
     */
    public function test_uid2id_is_not_static(): void
    {
        $reflection = new ReflectionProperty(EntityRepository::class, 'uid2id');

        $this->assertFalse(
            $reflection->isStatic(),
            'uid2id must NOT be static to prevent data leaks between requests in Octane'
        );
    }

    /**
     * Test that $uid2id is a private property.
     */
    public function test_uid2id_is_private(): void
    {
        $reflection = new ReflectionProperty(EntityRepository::class, 'uid2id');

        $this->assertTrue(
            $reflection->isPrivate(),
            'uid2id should be private'
        );
    }
}
